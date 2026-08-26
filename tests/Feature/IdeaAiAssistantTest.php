<?php

namespace Tests\Feature;

use App\AI\Services\ConfirmedAiRelationService;
use App\Models\AiProviderConfig;
use App\Models\AiUsageLog;
use App\Models\Category;
use App\Models\CategoryDimension;
use App\Models\Idea;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdeaAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_an_encrypted_provider_key_and_feature_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.ai.update'), $this->settingsPayload('project-secret-key-with-enough-length'));

        $response->assertRedirect();
        $this->assertSame('project-secret-key-with-enough-length', AiProviderConfig::first()->api_key);
        $this->assertNotSame('project-secret-key-with-enough-length', DB::table('ai_provider_configs')->value('api_key'));
        $this->assertDatabaseHas('ai_feature_settings', [
            'feature' => 'idea_organization',
            'model' => 'gpt-5.6-luna',
            'fallback_model' => 'gpt-5.6-terra',
        ]);
        $this->actingAs($admin)->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('Inteligencia artificial');
    }

    public function test_non_admin_cannot_open_ai_administration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.ai.index'))->assertRedirect(route('home'));
    }

    public function test_api_key_is_not_flashed_back_after_a_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = $this->settingsPayload('secret-that-must-not-enter-the-session');
        $payload['features']['transcription']['model'] = 'modelo-no-permitido';

        $this->actingAs($admin)->from(route('admin.ai.index'))->put(route('admin.ai.update'), $payload)
            ->assertRedirect(route('admin.ai.index'))
            ->assertSessionHasErrors('features.transcription.model');

        $this->assertNull(session()->getOldInput('api_key'));
    }

    public function test_audio_is_sent_transiently_to_the_transcription_endpoint(): void
    {
        $user = User::factory()->create();
        $this->enableOpenAi($user);
        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response(['text' => 'Crear un sistema para reutilizar materiales de talleres.'], 200, ['x-request-id' => 'req_audio']),
        ]);

        $response = $this->actingAs($user)->post(route('api.ai.ideas.transcribe'), [
            'audio' => UploadedFile::fake()->create('idea.webm', 150, 'audio/webm'),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('data.transcript', 'Crear un sistema para reutilizar materiales de talleres.');
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/audio/transcriptions');
        $this->assertDatabaseHas('ai_usage_logs', ['feature' => 'transcription', 'success' => true, 'request_id' => 'req_audio']);
    }

    public function test_organization_uses_strict_output_and_only_the_authenticated_users_ideas(): void
    {
        [$user, $category, $tag, $candidate] = $this->ideaContext();
        $other = User::factory()->create();
        Idea::factory()->for($other)->create([
            'category_id' => $category->id,
            'title' => 'CONTENIDO PRIVADO DE OTRO USUARIO',
            'workspace_status' => 'capturada',
        ]);
        $this->enableOpenAi($user);

        $modelOutput = [
            'title' => 'Reutilizar materiales sobrantes entre talleres',
            'description' => 'Crear un catálogo interno para registrar materiales sobrantes y permitir que otros talleres los soliciten antes de comprar nuevos insumos.',
            'problem_opportunity' => 'Los talleres desechan materiales aprovechables mientras otros necesitan comprar insumos equivalentes.',
            'primary_category_id' => $category->id,
            'classifications' => [['dimension_id' => $category->category_dimension_id, 'category_ids' => [$category->id]]],
            'tags' => [['name' => $tag->name, 'existing_tag_id' => $tag->id, 'action' => 'reuse_existing', 'confidence' => 0.95]],
            'parent_suggestion' => ['idea_id' => $candidate->id, 'confidence' => 0.84, 'rationale' => 'Amplía el sistema de economía circular existente.'],
            'missing_information' => [],
            'confidence' => 0.91,
        ];

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_organization',
                'status' => 'completed',
                'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($modelOutput)]]]],
                'usage' => ['input_tokens' => 500, 'output_tokens' => 180],
            ], 200, ['x-request-id' => 'req_organization']),
        ]);

        $response = $this->actingAs($user)->postJson(route('api.ai.ideas.organize'), [
            'transcript' => 'Quiero aprovechar sobrantes de materiales entre los talleres.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', $modelOutput['title'])
            ->assertJsonPath('data.parent_suggestion.idea_title', $candidate->title)
            ->assertJsonPath('data._meta.escalated', false);

        Http::assertSent(function (Request $request) use ($candidate): bool {
            if ($request->url() !== 'https://api.openai.com/v1/responses') {
                return false;
            }

            $context = $request['input'][0]['content'];

            return $request['store'] === false
                && $request['text']['format']['strict'] === true
                && $request['text']['format']['type'] === 'json_schema'
                && str_contains($context, $candidate->title)
                && ! str_contains($context, 'CONTENIDO PRIVADO DE OTRO USUARIO');
        });

        $this->assertDatabaseHas('ai_usage_logs', [
            'feature' => 'idea_organization',
            'model' => 'gpt-5.6-luna',
            'success' => true,
            'input_units' => 500,
            'output_units' => 180,
            'estimated_cost_usd' => 0.000316,
        ]);
        $this->assertFalse(in_array('transcript', (new AiUsageLog)->getFillable(), true));
    }

    public function test_relation_analysis_can_validly_return_no_candidates(): void
    {
        [$user] = $this->ideaContext();
        $this->enableOpenAi($user);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'status' => 'completed',
                'output' => [['content' => [['type' => 'output_text', 'text' => json_encode([
                    'relations' => [],
                    'confidence' => 0.9,
                ])]]]],
                'usage' => ['input_tokens' => 150, 'output_tokens' => 20],
            ]),
        ]);

        $this->actingAs($user)->postJson(route('api.ai.ideas.relations'), [
            'title' => 'Digitalizar la lista de asistencia',
            'description' => 'Registrar la asistencia de participantes mediante un formulario digital para evitar transcribir planillas impresas.',
            'problem_opportunity' => 'La transcripción manual consume tiempo y genera errores.',
        ])->assertOk()->assertExactJson([
            'data' => [
                'relations' => [],
                'confidence' => 0.9,
                '_meta' => [
                    'escalated' => false,
                    'prompt_version' => 'idea-relations-v1',
                ],
            ],
        ]);
    }

    public function test_relation_suggestions_do_not_mutate_the_graph_until_the_form_is_saved(): void
    {
        [$user, $category, , $candidate] = $this->ideaContext();
        $this->enableOpenAi($user);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'status' => 'completed',
                'output' => [['content' => [['type' => 'output_text', 'text' => json_encode([
                    'relations' => [[
                        'target_idea_id' => $candidate->id,
                        'type' => 'complements',
                        'rationale' => 'Comparte el flujo de reutilización y agrega trazabilidad logística.',
                        'confidence' => 0.93,
                    ]],
                    'confidence' => 0.93,
                ])]]]],
                'usage' => ['input_tokens' => 200, 'output_tokens' => 80],
            ]),
        ]);

        $this->actingAs($user)->postJson(route('api.ai.ideas.relations'), [
            'title' => 'Coordinar transporte de sobrantes',
            'description' => 'Crear un flujo para coordinar el transporte de materiales sobrantes entre centros y confirmar cada entrega.',
            'problem_opportunity' => 'No existe trazabilidad logística para los intercambios.',
        ])->assertOk()->assertJsonPath('data.relations.0.target_idea_id', $candidate->id);

        $this->assertDatabaseCount('idea_relations', 0);

        $source = Idea::factory()->for($user)->create(['category_id' => $category->id]);
        app(ConfirmedAiRelationService::class)->createApproved($source, $user, [[
            'target_idea_id' => $candidate->id,
            'type' => 'complements',
            'rationale' => 'Relación confirmada por el usuario desde el formulario.',
        ]]);

        $this->assertDatabaseHas('idea_relations', [
            'source_idea_id' => $source->id,
            'target_idea_id' => $candidate->id,
            'type' => 'complements',
            'status' => 'approved',
        ]);

        DB::table('idea_relations')->update(['status' => 'pending']);
        app(ConfirmedAiRelationService::class)->createApproved($source, $user, [[
            'target_idea_id' => $candidate->id,
            'type' => 'complements',
            'rationale' => 'La persona volvió a incorporar explícitamente la sugerencia.',
        ]]);
        $this->assertDatabaseCount('idea_relations', 1);
        $this->assertDatabaseHas('idea_relations', ['status' => 'approved']);

        $archived = Idea::factory()->for($user)->create([
            'category_id' => $category->id,
            'workspace_status' => 'archivada',
        ]);
        app(ConfirmedAiRelationService::class)->createApproved($source, $user, [[
            'target_idea_id' => $archived->id,
            'type' => 'related_to',
            'rationale' => 'Esta idea no debe formar parte del contexto positivo.',
        ]]);
        $this->assertDatabaseMissing('idea_relations', ['target_idea_id' => $archived->id]);
    }

    public function test_low_confidence_organization_escalates_to_the_configured_fallback(): void
    {
        [$user, $category, $tag] = $this->ideaContext();
        $this->enableOpenAi($user);
        $baseOutput = [
            'title' => 'Crear un inventario reutilizable de materiales',
            'description' => 'Crear un inventario interno para registrar materiales disponibles y facilitar su reutilización entre talleres antes de comprar nuevos insumos.',
            'problem_opportunity' => 'Los sobrantes aprovechables no son visibles para otros talleres.',
            'primary_category_id' => $category->id,
            'classifications' => [['dimension_id' => $category->category_dimension_id, 'category_ids' => [$category->id]]],
            'tags' => [['name' => $tag->name, 'existing_tag_id' => $tag->id, 'action' => 'reuse_existing', 'confidence' => 0.9]],
            'parent_suggestion' => ['idea_id' => null, 'confidence' => 0.9, 'rationale' => 'Puede operar como iniciativa independiente.'],
            'missing_information' => [],
        ];
        Http::fakeSequence('https://api.openai.com/v1/responses')
            ->push([
                'status' => 'completed',
                'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($baseOutput + ['confidence' => 0.6])]]]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 60],
            ])
            ->push([
                'status' => 'completed',
                'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($baseOutput + ['confidence' => 0.92])]]]],
                'usage' => ['input_tokens' => 110, 'output_tokens' => 65],
            ]);

        $this->actingAs($user)->postJson(route('api.ai.ideas.organize'), [
            'transcript' => 'Quiero compartir sobrantes útiles entre talleres para evitar compras repetidas.',
        ])->assertOk()->assertJsonPath('data._meta.escalated', true);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-5.6-terra'
            && $request['reasoning']['effort'] === 'medium');
        $this->assertDatabaseHas('ai_usage_logs', ['model' => 'gpt-5.6-terra', 'escalated' => true, 'success' => true]);
    }

    public function test_another_users_current_idea_is_rejected_before_calling_the_provider(): void
    {
        [$user, $category] = $this->ideaContext();
        $otherIdea = Idea::factory()->create(['category_id' => $category->id]);
        $this->enableOpenAi($user);
        Http::fake();

        $this->actingAs($user)->postJson(route('api.ai.ideas.organize'), [
            'transcript' => 'Una idea suficientemente clara para analizar.',
            'current_idea_id' => $otherIdea->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('current_idea_id');

        Http::assertNothingSent();
    }

    private function settingsPayload(?string $apiKey = null): array
    {
        return [
            'provider_enabled' => '1',
            'api_key' => $apiKey,
            'features' => [
                'transcription' => ['enabled' => '1', 'model' => 'gpt-transcribe', 'reasoning_effort' => null, 'fallback_model' => null, 'fallback_reasoning_effort' => null],
                'idea_organization' => ['enabled' => '1', 'model' => 'gpt-5.6-luna', 'reasoning_effort' => 'low', 'fallback_model' => 'gpt-5.6-terra', 'fallback_reasoning_effort' => 'medium', 'ambiguity_threshold' => 0.72],
                'idea_relations' => ['enabled' => '1', 'model' => 'gpt-5.6-luna', 'reasoning_effort' => 'low', 'fallback_model' => 'gpt-5.6-terra', 'fallback_reasoning_effort' => 'medium', 'ambiguity_threshold' => 0.72],
            ],
        ];
    }

    private function enableOpenAi(User $actor): void
    {
        AiProviderConfig::create([
            'provider' => 'openai',
            'api_key' => 'test-project-key-with-enough-length',
            'enabled' => true,
            'configured_by_user_id' => $actor->id,
        ]);
    }

    private function ideaContext(): array
    {
        $user = User::factory()->create();
        $dimension = CategoryDimension::query()->where('slug', 'area-de-innovacion')->firstOrFail();
        $dimension->update([
            'selection_mode' => 'single',
            'is_required' => true,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $category = Category::create([
            'category_dimension_id' => $dimension->id,
            'name' => 'Sostenibilidad',
            'description' => 'Reducción de residuos e impacto ambiental.',
            'is_active' => true,
        ]);
        $tag = Tag::create(['name' => 'Economía Circular']);
        $candidate = Idea::factory()->for($user)->create([
            'category_id' => $category->id,
            'title' => 'Intercambio de materiales entre talleres',
            'description' => 'Catálogo para compartir materiales sobrantes entre los talleres de la institución.',
            'problem_opportunity' => 'Se desperdician materiales que pueden reutilizar otros centros.',
            'workspace_status' => 'capturada',
        ]);
        $candidate->tags()->attach($tag);

        return [$user, $category, $tag, $candidate];
    }
}

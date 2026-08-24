<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Regional;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaTagExplorerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $regional = Regional::create([
            'code' => 'ONA',
            'name' => 'Oficina Nacional',
            'order' => 1,
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'regional_id' => $regional->id,
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Tecnología',
            'slug' => 'tecnologia',
            'icon' => 'memory',
            'color' => '#231fb5',
            'description' => 'Soluciones digitales e innovación.',
        ]);
    }

    public function test_create_idea_screen_displays_tag_explorer_data(): void
    {
        Tag::create(['name' => 'Inteligencia Artificial', 'slug' => 'inteligencia-artificial']);
        Tag::create(['name' => 'Automatización', 'slug' => 'automatizacion']);

        $response = $this->actingAs($this->user)->get(route('ideas.create'));

        $response->assertStatus(200);
        $response->assertSee('Explorador de Etiquetas');
        $response->assertSee('Ver todas las etiquetas');
        $response->assertSee('Inteligencia Artificial');
        $response->assertSee('Automatización');
    }

    public function test_user_can_create_idea_with_selected_and_new_tags(): void
    {
        $existingTag = Tag::create(['name' => 'Automatización', 'slug' => 'automatizacion']);

        $payload = [
            'title' => 'Sistema de monitoreo de laboratorios',
            'description' => 'Proponemos instalar sensores inteligentes en talleres y aulas técnicas.',
            'problem_opportunity' => 'Falta de visibilidad de uso de equipos.',
            'category_id' => $this->category->id,
            'visibility' => 'public',
            'tags' => ['Automatización', 'Sensores IoT'],
        ];

        $response = $this->actingAs($this->user)->post(route('ideas.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('ideas', [
            'title' => 'Sistema de monitoreo de laboratorios',
            'category_id' => $this->category->id,
        ]);

        $idea = Idea::where('title', 'Sistema de monitoreo de laboratorios')->first();
        $this->assertNotNull($idea);
        $this->assertCount(2, $idea->tags);
        $this->assertTrue($idea->tags->contains('name', 'Automatización'));
        $this->assertTrue($idea->tags->contains('name', 'Sensores IoT'));
    }

    public function test_edit_idea_screen_displays_tag_explorer_and_existing_tags(): void
    {
        $idea = Idea::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => "Plataforma d'Innovación y 'Talleres' INFOTEP",
            'summary' => 'Resumen breve de prueba',
            'description' => "Primera línea de la propuesta.\nSegunda línea con \"comillas\" y 'apóstrofes'.\nTercera línea.",
            'problem_opportunity' => "Oportunidad con saltos\nde línea.",
            'status' => 'nueva',
            'visibility' => 'public',
        ]);

        $tag = Tag::create(['name' => 'E-learning', 'slug' => 'e-learning']);
        $idea->tags()->attach($tag->id);

        $response = $this->actingAs($this->user)->get(route('ideas.edit', $idea->id));

        $response->assertStatus(200);
        $response->assertSee('Explorador de Etiquetas');
        $response->assertSee('Ver todas las etiquetas');
        $response->assertSee('E-learning');
        $response->assertSee('Plataforma d&#039;Innovación y &#039;Talleres&#039; INFOTEP', false);
    }

    public function test_user_can_update_existing_idea_tags(): void
    {
        $idea = Idea::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Idea en Evolución',
            'summary' => 'Resumen',
            'description' => 'Descripción inicial de la propuesta.',
            'status' => 'en_revision',
            'visibility' => 'public',
        ]);

        $initialTag = Tag::create(['name' => 'Inicial', 'slug' => 'inicial']);
        $idea->tags()->attach($initialTag->id);

        $payload = [
            'title' => 'Idea en Evolución Actualizada',
            'description' => 'Descripción mejorada con nuevas etiquetas.',
            'category_id' => $this->category->id,
            'visibility' => 'public',
            'tags' => ['Inicial', 'NuevaEtiqueta1', 'NuevaEtiqueta2'],
        ];

        $response = $this->actingAs($this->user)->put(route('ideas.update', $idea->id), $payload);

        $response->assertRedirect(route('ideas.show', $idea->slug));
        $idea->refresh();
        $this->assertCount(3, $idea->tags);
        $this->assertTrue($idea->tags->contains('name', 'NuevaEtiqueta1'));
        $this->assertTrue($idea->tags->contains('name', 'NuevaEtiqueta2'));
    }

    public function test_comma_separated_tags_are_split_and_saved_individually(): void
    {
        $payload = [
            'title' => 'Gestión Automatizada con IA',
            'description' => 'Descripción con múltiples etiquetas separadas por comas.',
            'category_id' => $this->category->id,
            'visibility' => 'public',
            'tags' => ['Inteligencia Artificial, Robótica, Machine Learning', '#BigData, Ciberseguridad'],
        ];

        $response = $this->actingAs($this->user)->post(route('ideas.store'), $payload);

        $response->assertRedirect();
        $idea = Idea::where('title', 'Gestión Automatizada con IA')->first();
        $this->assertNotNull($idea);
        $this->assertCount(5, $idea->tags);
        $this->assertTrue($idea->tags->contains('name', 'Inteligencia Artificial'));
        $this->assertTrue($idea->tags->contains('name', 'Robótica'));
        $this->assertTrue($idea->tags->contains('name', 'Machine Learning'));
        $this->assertTrue($idea->tags->contains('name', 'BigData'));
        $this->assertTrue($idea->tags->contains('name', 'Ciberseguridad'));
    }
}

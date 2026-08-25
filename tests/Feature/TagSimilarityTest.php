<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Regional;
use App\Models\Tag;
use App\Models\User;
use App\Services\TagSimilarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagSimilarityTest extends TestCase
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

    public function test_tag_normalization_removes_accents_symbols_and_lowercases(): void
    {
        $this->assertEquals('tecnologia', TagSimilarityService::normalize('  #TECNOLOGÍA  '));
        $this->assertEquals('inteligencia artificial', TagSimilarityService::normalize('#Inteligencia-Artificial!'));
        $this->assertEquals('educacion virtual', TagSimilarityService::normalize('Educación Virtual'));
    }

    public function test_spanish_stemming_normalizes_plurals(): void
    {
        $this->assertEquals('sensor', TagSimilarityService::stem('Sensores'));
        $this->assertEquals('sensor', TagSimilarityService::stem('Sensor'));
        $this->assertEquals('taller', TagSimilarityService::stem('Talleres'));
        $this->assertEquals('innovacion', TagSimilarityService::stem('Innovaciones'));
        $this->assertEquals('capacitacion', TagSimilarityService::stem('Capacitaciones'));
        $this->assertEquals('herramienta', TagSimilarityService::stem('Herramientas'));
    }

    public function test_similarity_calculation_identifies_close_matches(): void
    {
        // Exact normalized
        $this->assertEquals(1.0, TagSimilarityService::calculateSimilarity('automatizacion', 'Automatización'));

        // Plural / singular
        $this->assertGreaterThanOrEqual(0.90, TagSimilarityService::calculateSimilarity('Sensor', 'Sensores'));
        $this->assertGreaterThanOrEqual(0.90, TagSimilarityService::calculateSimilarity('Capacitación', 'Capacitaciones'));

        // Typo (Levenshtein distance 1-2)
        $this->assertGreaterThanOrEqual(0.85, TagSimilarityService::calculateSimilarity('Inteligencia Artifical', 'Inteligencia Artificial'));
        $this->assertGreaterThanOrEqual(0.80, TagSimilarityService::calculateSimilarity('Robótica', 'Robotica'));

        // Dissimilar
        $this->assertLessThan(0.50, TagSimilarityService::calculateSimilarity('Finanzas', 'Tecnología'));
    }

    public function test_find_similar_returns_relevant_existing_tags(): void
    {
        Tag::create(['name' => 'Automatización', 'slug' => 'automatizacion']);
        Tag::create(['name' => 'Sensores IoT', 'slug' => 'sensores-iot']);
        Tag::create(['name' => 'Inteligencia Artificial', 'slug' => 'inteligencia-artificial']);

        $similar = TagSimilarityService::findSimilar('automatizar');
        $this->assertTrue($similar->contains('name', 'Automatización'));

        $similarSensor = TagSimilarityService::findSimilar('Sensor');
        $this->assertTrue($similarSensor->contains('name', 'Sensores IoT'));
    }

    public function test_find_or_create_normalized_reuses_canonical_tag(): void
    {
        $canonical = Tag::create(['name' => 'Robótica', 'slug' => 'robotica']);

        // Different casing and without accent
        $matched = Tag::findOrCreateNormalized('robotica');
        $this->assertEquals($canonical->id, $matched->id);
        $this->assertEquals('Robótica', $matched->name);
        $this->assertEquals(1, Tag::count());

        // With hashtags and whitespace
        $matchedWithHash = Tag::findOrCreateNormalized('#ROBÓTICA ');
        $this->assertEquals($canonical->id, $matchedWithHash->id);
        $this->assertEquals(1, Tag::count());
    }

    public function test_idea_creation_uses_canonical_tags_and_prevents_duplicate_tag_records(): void
    {
        $existingTag = Tag::create(['name' => 'Inteligencia Artificial', 'slug' => 'inteligencia-artificial']);

        $payload = [
            'title' => 'Plataforma con IA',
            'description' => 'Descripción detallada de la propuesta.',
            'category_id' => $this->category->id,
            'visibility' => 'private',
            'tags' => ['#inteligencia-artificial', 'Nueva Etiqueta'],
        ];

        $response = $this->actingAs($this->user)->post(route('ideas.store'), $payload);

        $response->assertRedirect();
        $idea = Idea::where('title', 'Plataforma con IA')->first();
        $this->assertNotNull($idea);

        // Verify that the existing tag was reused and not duplicated
        $this->assertEquals(2, Tag::count());
        $this->assertTrue($idea->tags->contains('id', $existingTag->id));
        $this->assertTrue($idea->tags->contains('name', 'Inteligencia Artificial'));
    }
}

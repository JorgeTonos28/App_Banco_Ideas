<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ClassificationGuidanceComponentTest extends TestCase
{
    public function test_form_guidance_explains_the_classification_path(): void
    {
        $html = Blade::render('<x-classification-guidance />');

        $this->assertStringContainsString('Clasifica una vez, conecta muchas ideas', $html);
        $this->assertStringContainsString('Categoría: el tema principal', $html);
        $this->assertStringContainsString('Usa entre 4 y 7 términos concretos', $html);
        $this->assertStringNotContainsString('Criterios para mantener una taxonomía útil', $html);
    }

    public function test_admin_guidance_explains_taxonomy_governance(): void
    {
        $html = Blade::render('<x-classification-guidance context="admin" />');

        $this->assertStringContainsString('Criterios para mantener una taxonomía útil', $html);
        $this->assertStringContainsString('debería servir para cinco ideas', $html);
        $this->assertStringContainsString('Usa entre 4 y 7 etiquetas por idea', $html);
        $this->assertStringNotContainsString('Piensa de lo general a lo específico', $html);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class M7DesignSystemComponentsTest extends TestCase
{
    public function test_table_component_renders_accessible_responsive_table_contract(): void
    {
        $this->assertTrue(View::exists('components.table'), 'M7 table component must exist.');

        $html = Blade::render(<<<'BLADE'
            <x-table label="Execuções operacionais">
                <thead><tr><th scope="col">Cenário</th></tr></thead>
                <tbody><tr><td>Alpha</td></tr></tbody>
            </x-table>
        BLADE);

        $this->assertStringContainsString('aria-label="Execuções operacionais"', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('overflow-x-auto', $html);
        $this->assertStringContainsString('Alpha', $html);
    }

    public function test_section_nav_component_uses_real_anchor_navigation_and_current_state(): void
    {
        $this->assertTrue(View::exists('components.section-nav'), 'M7 section navigation component must exist.');

        $html = Blade::render(
            '<x-section-nav label="Seções da avaliação" :items="$items" />',
            ['items' => [
                ['label' => 'Resumo', 'href' => '#summary', 'state' => 'current'],
                ['label' => 'Rubrica', 'href' => '#rubric'],
            ]],
        );

        $this->assertStringContainsString('aria-label="Seções da avaliação"', $html);
        $this->assertStringContainsString('href="#summary"', $html);
        $this->assertStringContainsString('href="#rubric"', $html);
        $this->assertStringContainsString('aria-current="location"', $html);
        $this->assertStringNotContainsString('role="button"', $html);
    }

    public function test_attention_item_component_preserves_semantic_variant_and_optional_link(): void
    {
        $this->assertTrue(View::exists('components.attention-item'), 'M7 attention item component must exist.');

        $html = Blade::render(<<<'BLADE'
            <x-attention-item
                title="Ação corretiva vencida"
                metadata="Prazo 08/08/2026"
                variant="emergency"
                href="/actions/1"
            >
                Reavaliar prontidão da equipe.
            </x-attention-item>
        BLADE);

        $this->assertStringContainsString('href="/actions/1"', $html);
        $this->assertStringContainsString('data-variant="emergency"', $html);
        $this->assertStringContainsString('Ação corretiva vencida', $html);
        $this->assertStringContainsString('Prazo 08/08/2026', $html);
        $this->assertStringContainsString('Reavaliar prontidão da equipe.', $html);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class R2PublicHeroHeaderTest extends TestCase
{
    public function test_public_home_exposes_the_approved_d2_product_story(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('PLATAFORMA DE SIMULAÇÃO, AVALIAÇÃO E DEBRIEFING')
            ->assertSee('Treine decisões. Avalie a execução. Transforme cada cenário em aprendizado.')
            ->assertSee('O Tactical Scenario Lab estrutura todo o ciclo de treinamento baseado em cenários — do planejamento à execução, da avaliação objetiva ao debriefing — reunindo métricas, histórico e relatórios para apoiar a melhoria contínua.')
            ->assertSee('href="#recursos"', false)
            ->assertSee('Conhecer a plataforma')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Acessar o ambiente')
            ->assertSee('Dados ilustrativos')
            ->assertSee('Treinamento de tomada de decisão')
            ->assertSee('AVALIAR')
            ->assertSee('Indicadores objetivos')
            ->assertSee('Pendente');
    }

    public function test_public_header_has_valid_accessible_navigation_markup(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('aria-controls="public-navigation"', false)
            ->assertSee('x-bind:aria-expanded="open.toString()"', false)
            ->assertSee('href="#visao-geral"', false)
            ->assertSee('href="#como-funciona"', false)
            ->assertSee('href="#recursos"', false);

        $this->assertDoesNotMatchRegularExpression(
            '/<a\b[^>]*>\s*<a\b/i',
            $response->getContent(),
            'The rendered public page must not contain nested anchors.',
        );
    }
}

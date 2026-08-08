<?php

namespace App\Services;

final class ScenarioGenerator
{
    public function generate(array $data): array
    {
        $threat = $data['threat_level'];
        $mechanism = $data['mechanism'];
        $environment = $data['environment'];
        $casualties = (int) ($data['estimated_casualty_count'] ?? $data['casualties']);

        return [
            'title' => "Cenário {$mechanism} — {$environment}",
            'environment' => $environment,
            'threat_level' => $threat,
            'casualties' => $casualties,
            'estimated_casualty_count' => $casualties,
            'mechanism' => $mechanism,
            'resources' => $data['resources'] ?? [],
            'learning_objectives' => [
                'Reconhecer riscos e estabelecer prioridades de atendimento.',
                'Aplicar avaliação MARCH de forma sequencial e documentada.',
                'Comunicar achados, intervenções e necessidade de evacuação.',
            ],
            'expected_actions' => $this->expectedActions($threat),
            'critical_errors' => [
                'Entrar em área não segura sem coordenação.',
                'Não identificar ou controlar hemorragia maciça.',
                'Omitir reavaliação após intervenção.',
                'Não comunicar prioridade de evacuação.',
            ],
            'status' => 'draft',
        ];
    }

    private function expectedActions(string $threat): array
    {
        $actions = ['Avaliar segurança da cena e usar EPI.', 'Executar MARCH.', 'Definir prioridade e método de evacuação.'];
        if ($threat === 'ativa') {
            array_unshift($actions, 'Coordenar movimentação para cobertura e reduzir exposição.');
        }

        return $actions;
    }
}

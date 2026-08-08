<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de execução {{ $report['execution']['uuid'] }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1f2937; font-size: 10px; line-height: 1.45; }
        h1 { margin: 0; color: #102a43; font-size: 20px; }
        h2 { margin: 20px 0 8px; color: #102a43; font-size: 13px; border-bottom: 1px solid #d9e2ec; padding-bottom: 5px; }
        h3 { margin: 12px 0 5px; color: #334e68; font-size: 11px; }
        p { margin: 3px 0; }
        .muted { color: #627d98; }
        .header { border-bottom: 2px solid #102a43; padding-bottom: 12px; margin-bottom: 14px; }
        .meta { margin-top: 8px; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 7px; }
        .grid th, .grid td { border: 1px solid #d9e2ec; padding: 6px; vertical-align: top; text-align: left; }
        .grid th { background: #f0f4f8; color: #243b53; font-weight: 700; }
        .pill { display: inline-block; border: 1px solid #bcccdc; border-radius: 10px; padding: 2px 6px; margin: 1px 2px 1px 0; }
        .score { font-size: 18px; font-weight: 700; color: #102a43; }
        .break { page-break-inside: avoid; }
        .entry { border-left: 3px solid #829ab1; padding-left: 8px; margin: 7px 0; }
        .fact { border-color: #486581; }
        .interpretation { border-color: #d69e2e; }
        .recommendation { border-color: #2f855a; }
        .legacy_unstructured { border-color: #718096; }
        .footer { margin-top: 22px; padding-top: 8px; border-top: 1px solid #d9e2ec; color: #829ab1; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="muted">TACTICAL SCENARIO LAB · RELATÓRIO INSTITUCIONAL</p>
        <h1>{{ $report['scenario']['title'] }}</h1>
        <div class="meta">
            <strong>{{ $report['organization']['name'] }}</strong><br>
            Execução #{{ $report['execution']['sequence'] }} · versão {{ $report['scenario']['version'] }} · {{ $report['execution']['status'] }}<br>
            Referência pública: {{ $report['execution']['uuid'] }}
        </div>
    </div>

    <h2>Contexto da execução</h2>
    <table class="grid">
        <tr><th>Ambiente</th><td>{{ $report['scenario']['environment'] }}</td><th>Ameaça</th><td>{{ $report['scenario']['threat_level'] }}</td></tr>
        <tr><th>Mecanismo</th><td>{{ $report['scenario']['mechanism'] }}</td><th>Estimativa de vítimas</th><td>{{ $report['scenario']['estimated_casualty_count'] }}</td></tr>
        <tr><th>Início</th><td>{{ $report['execution']['started_at'] ?? 'Não iniciado' }}</td><th>Conclusão</th><td>{{ $report['execution']['completed_at'] ?? 'Não concluída' }}</td></tr>
    </table>

    <h2>Participantes e equipes</h2>
    @if (empty($report['participants']))
        <p class="muted">Nenhum participante registrado.</p>
    @else
        <table class="grid">
            <thead><tr><th>Participante</th><th>Função</th><th>Equipe</th><th>Unidade histórica</th><th>Cargo histórico</th></tr></thead>
            <tbody>
            @foreach ($report['participants'] as $participant)
                <tr>
                    <td>{{ $participant['name'] }}</td>
                    <td>{{ $participant['role'] ?? '—' }}</td>
                    <td>{{ $participant['team'] ?? '—' }}</td>
                    <td>{{ $participant['unit'] }}</td>
                    <td>{{ $participant['position'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Avaliação</h2>
    @if ($report['assessment'] === null)
        <p class="muted">Avaliação/debriefing ainda não disponível para esta execução.</p>
    @else
        <p><span class="score">{{ $report['assessment']['final_score'] ?? '—' }}</span> / 100</p>
        <p>
            Estado: <strong>{{ $report['assessment']['status'] }}</strong> ·
            Resultado: <strong>{{ $report['assessment']['result'] ?? 'Sem classificação histórica' }}</strong> ·
            Automatic fail: <strong>{{ $report['assessment']['automatic_fail'] ? 'sim' : 'não' }}</strong>
        </p>
        <p class="muted">
            Nota-base {{ $report['assessment']['base_score'] ?? '—' }} · penalidades {{ $report['assessment']['penalty_points'] ?? '—' }} · ajuste {{ $report['assessment']['evaluator_adjustment'] }}
        </p>
        @if ($report['assessment']['adjustment_justification'])
            <p>Justificativa do ajuste: {{ $report['assessment']['adjustment_justification'] }}</p>
        @endif

        <div class="break">
            <h3>Rubrica e evidências</h3>
            @if (empty($report['assessment']['criteria']))
                <p class="muted">Sem critérios estruturados.</p>
            @else
                <table class="grid">
                    <thead><tr><th>Critério</th><th>Peso</th><th>Nota</th><th>Evidências</th></tr></thead>
                    <tbody>
                    @foreach ($report['assessment']['criteria'] as $criterion)
                        <tr>
                            <td><strong>{{ $criterion['label'] }}</strong>@if ($criterion['description'])<br><span class="muted">{{ $criterion['description'] }}</span>@endif</td>
                            <td>{{ $criterion['weight'] }}%</td>
                            <td>{{ $criterion['score'] ?? '—' }}</td>
                            <td>
                                @forelse ($criterion['evidence'] as $evidence)
                                    <p>{{ $evidence['statement'] }} @if ($evidence['observed_at'])<span class="muted">({{ $evidence['observed_at'] }})</span>@endif</p>
                                @empty
                                    <span class="muted">Sem evidência registrada.</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="break">
            <h3>Erros críticos observados</h3>
            @forelse ($report['assessment']['critical_errors'] as $error)
                <p><strong>{{ $error['label'] }}</strong> · {{ $error['rule'] }} @if ((float) $error['penalty_points'] > 0) · -{{ $error['penalty_points'] }} ponto(s) @endif</p>
            @empty
                <p class="muted">Nenhum erro crítico observado.</p>
            @endforelse
        </div>

        <div class="break">
            <h3>Tempos-chave</h3>
            @forelse ($report['assessment']['key_times'] as $keyTime)
                <p><strong>{{ $keyTime['label'] }}</strong> · {{ $keyTime['elapsed_seconds'] }} s @if ($keyTime['reference_seconds'] !== null) · referência {{ $keyTime['reference_seconds'] }} s @endif</p>
            @empty
                <p class="muted">Nenhum tempo-chave registrado.</p>
            @endforelse
        </div>

        <h2>Debriefing</h2>
        @if ($report['assessment']['debrief'] === null)
            <p class="muted">Debriefing não registrado.</p>
        @else
            @forelse ($report['assessment']['debrief']['entries'] as $entry)
                <div class="entry {{ $entry['kind'] }}">
                    <strong>{{ match ($entry['kind']) {
                        'fact' => 'Fato',
                        'interpretation' => 'Interpretação',
                        'recommendation' => 'Recomendação',
                        default => 'Registro histórico',
                    } }}</strong>
                    <p>{{ $entry['content'] }}</p>
                </div>
            @empty
                <p class="muted">Nenhuma entrada de debriefing registrada.</p>
            @endforelse

            <h3>Plano de ação</h3>
            @if (empty($report['assessment']['debrief']['actions']))
                <p class="muted">Nenhuma ação corretiva registrada.</p>
            @else
                <table class="grid">
                    <thead><tr><th>Ação</th><th>Responsável</th><th>Prazo</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($report['assessment']['debrief']['actions'] as $action)
                        <tr>
                            <td>{{ $action['action'] }}</td>
                            <td>{{ $action['responsible'] ?? '—' }}</td>
                            <td>{{ $action['due_date'] ?? '—' }}</td>
                            <td>{{ $action['status'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    @endif

    <div class="footer">
        Gerado em {{ $report['generated_at'] }} · Tactical Scenario Lab · uso institucional de treinamento.
    </div>
</body>
</html>

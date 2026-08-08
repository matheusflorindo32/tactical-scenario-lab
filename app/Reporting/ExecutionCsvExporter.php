<?php

namespace App\Reporting;

use App\Models\ScenarioExecution;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExecutionCsvExporter
{
    private const HEADERS = [
        'execution_uuid',
        'execution_sequence',
        'scenario_uuid',
        'scenario_title',
        'scenario_version',
        'unit_uuids',
        'unit_names',
        'execution_status',
        'started_at',
        'completed_at',
        'assessment_status',
        'final_score',
        'result',
        'automatic_fail',
        'critical_error_count',
        'open_action_count',
    ];

    public function __construct(private readonly ExecutionHistoryQuery $history) {}

    public function headers(): array
    {
        return self::HEADERS;
    }

    public function neutralizeForSpreadsheet(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'{$value}" : $value;
    }

    public function row(ScenarioExecution $execution): array
    {
        $unitUuids = $execution->participants
            ->map(fn ($participant) => $participant->unitSnapshot?->uuid)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $unitNames = $execution->participants
            ->pluck('unit_name_snapshot')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $assessment = $execution->assessment;
        $scenario = $execution->scenarioVersion->scenario;

        return [
            $execution->uuid,
            (string) $execution->sequence_number,
            $scenario->uuid,
            $this->neutralizeForSpreadsheet($scenario->title),
            (string) $execution->scenarioVersion->version_number,
            $unitUuids->implode(';'),
            $this->neutralizeForSpreadsheet(
                $unitNames->isEmpty() ? 'Sem unidade histórica' : $unitNames->implode(';'),
            ),
            $this->neutralizeForSpreadsheet($execution->status),
            $execution->started_at?->toIso8601String() ?? '',
            $execution->completed_at?->toIso8601String() ?? '',
            $this->neutralizeForSpreadsheet($assessment?->status),
            $assessment?->final_score === null ? '' : (string) $assessment->final_score,
            $this->neutralizeForSpreadsheet($assessment?->result),
            $assessment === null ? '' : ($assessment->automatic_fail ? '1' : '0'),
            (string) ((int) ($execution->getAttribute('critical_error_count') ?? 0)),
            (string) ((int) ($execution->getAttribute('open_action_count') ?? 0)),
        ];
    }

    public function stream(InstitutionalFilter $filter): StreamedResponse
    {
        return response()->streamDownload(function () use ($filter): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $this->headers());

            foreach ($this->history->cursor($filter) as $execution) {
                fputcsv($handle, $this->row($execution));
            }

            fclose($handle);
        }, 'executions.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}

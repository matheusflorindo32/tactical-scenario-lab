<?php

namespace App\Reporting;

use App\Models\Scenario;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class InstitutionalFilter
{
    public function __construct(
        public int $organizationId,
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?int $unitId = null,
        public ?int $scenarioId = null,
        public ?string $status = null,
    ) {}

    public static function fromRequest(
        Request $request,
        int $organizationId,
        ?array $allowedStatuses = null,
    ): self {
        $validated = Validator::make($request->only([
            'date_from',
            'date_to',
            'unit_uuid',
            'scenario_uuid',
            'status',
        ]), [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'unit_uuid' => ['nullable', 'uuid'],
            'scenario_uuid' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:40'],
        ])->validate();

        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $dateTo = isset($validated['date_to'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['date_to'], config('app.timezone'))->endOfDay()
            : $today->endOfDay();
        $dateFrom = isset($validated['date_from'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['date_from'], config('app.timezone'))->startOfDay()
            : $dateTo->startOfDay()->subDays(89);

        if ($dateFrom->gt($dateTo)) {
            throw ValidationException::withMessages([
                'date_from' => 'A data inicial não pode ser posterior à data final.',
            ]);
        }

        if ($dateFrom->startOfDay()->diffInDays($dateTo->startOfDay()) > 365) {
            throw ValidationException::withMessages([
                'date_from' => 'O período consultado deve ter no máximo 366 dias corridos, contando as duas datas.',
            ]);
        }

        $unitId = null;
        if (isset($validated['unit_uuid'])) {
            $unitId = Unit::query()
                ->where('uuid', $validated['unit_uuid'])
                ->where('organization_id', $organizationId)
                ->value('id');

            if (! $unitId) {
                throw ValidationException::withMessages([
                    'unit_uuid' => 'A unidade selecionada não pertence à organização ativa.',
                ]);
            }
        }

        $scenarioId = null;
        if (isset($validated['scenario_uuid'])) {
            $scenarioId = Scenario::query()
                ->where('uuid', $validated['scenario_uuid'])
                ->where('organization_id', $organizationId)
                ->value('id');

            if (! $scenarioId) {
                throw ValidationException::withMessages([
                    'scenario_uuid' => 'O cenário selecionado não pertence à organização ativa.',
                ]);
            }
        }

        $status = $validated['status'] ?? null;
        if ($status !== null && $allowedStatuses !== null && ! in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'O status selecionado não é válido para esta consulta.',
            ]);
        }

        return new self(
            organizationId: $organizationId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            unitId: $unitId ? (int) $unitId : null,
            scenarioId: $scenarioId ? (int) $scenarioId : null,
            status: $status,
        );
    }
}

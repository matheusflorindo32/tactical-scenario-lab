<?php

namespace App\Http\Controllers;

use App\Models\CriticalErrorOccurrence;
use App\Models\ExecutionAssessment;
use App\Models\ExecutionEvent;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;

class CriticalErrorOccurrenceController extends Controller
{
    public function store(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $assessment->loadMissing('execution.scenarioVersion');
        $catalog = $assessment->execution->scenarioVersion->critical_errors ?? [];

        $validated = $request->validate([
            'catalog_label_snapshot' => ['required', 'string', Rule::in(is_array($catalog) ? $catalog : [])],
            'rule' => ['required', Rule::in(['record', 'penalty', 'automatic_fail'])],
            'penalty_points' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'execution_event_uuid' => ['nullable', 'uuid'],
            'observed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $rule = $validated['rule'];
        $penalty = (float) ($validated['penalty_points'] ?? 0);

        if ($rule === 'penalty' && ($penalty <= 0 || $penalty > 100)) {
            throw ValidationException::withMessages([
                'penalty_points' => 'Uma penalidade deve ser maior que zero e no máximo 100 pontos.',
            ]);
        }

        if ($rule !== 'penalty') {
            $penalty = 0;
        }

        $eventId = null;

        if (! empty($validated['execution_event_uuid'])) {
            $event = ExecutionEvent::query()->where('uuid', $validated['execution_event_uuid'])->first();

            if (! $event || $event->scenario_execution_id !== $assessment->scenario_execution_id) {
                throw ValidationException::withMessages([
                    'execution_event_uuid' => 'O evento selecionado não pertence a esta execução.',
                ]);
            }

            $eventId = $event->id;
        }

        $assessment->criticalErrorOccurrences()->create([
            'catalog_label_snapshot' => $validated['catalog_label_snapshot'],
            'rule' => $rule,
            'penalty_points' => $penalty,
            'execution_event_id' => $eventId,
            'observed_at' => $validated['observed_at'],
            'notes' => $validated['notes'] ?? null,
            'source' => 'm4',
        ]);

        return back()->with('success', 'Erro crítico observado registrado.');
    }

    public function destroy(
        Request $request,
        CriticalErrorOccurrence $occurrence,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $occurrence->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $occurrence->delete();

        return back()->with('success', 'Ocorrência crítica removida.');
    }

    private function authorizeMutation(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): void {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        if (! $assessment->isDraft()) {
            throw new LogicException('Finalized critical error occurrences are immutable.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AssessmentCriterion;
use App\Models\AssessmentEvidence;
use App\Models\ExecutionEvent;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LogicException;

class AssessmentEvidenceController extends Controller
{
    public function store(
        Request $request,
        AssessmentCriterion $criterion,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $criterion->assessment()->with('execution')->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);

        $validated = $request->validate([
            'execution_event_uuid' => ['nullable', 'uuid'],
            'statement' => ['required', 'string', 'max:5000'],
            'observed_at' => ['required', 'date'],
        ]);

        $eventId = null;

        if (! empty($validated['execution_event_uuid'])) {
            $event = ExecutionEvent::query()
                ->where('uuid', $validated['execution_event_uuid'])
                ->first();

            if (! $event || $event->scenario_execution_id !== $assessment->execution->id) {
                throw ValidationException::withMessages([
                    'execution_event_uuid' => 'O evento selecionado não pertence a esta execução.',
                ]);
            }

            $eventId = $event->id;
        }

        $criterion->evidence()->create([
            'execution_event_id' => $eventId,
            'statement' => $validated['statement'],
            'observed_at' => $validated['observed_at'],
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Evidência adicionada ao critério.');
    }

    public function destroy(
        Request $request,
        AssessmentEvidence $evidence,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $evidence->criterion()->firstOrFail()->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $evidence->delete();

        return back()->with('success', 'Evidência removida.');
    }

    private function authorizeMutation(Request $request, $assessment, ActiveOrganization $activeOrganization): void
    {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        if (! $assessment->isDraft()) {
            throw new LogicException('Finalized assessment evidence is immutable.');
        }
    }
}

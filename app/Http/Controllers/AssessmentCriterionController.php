<?php

namespace App\Http\Controllers;

use App\Models\AssessmentCriterion;
use App\Models\ExecutionAssessment;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class AssessmentCriterionController extends Controller
{
    public function store(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $validated = $this->validateCriterion($request);
        $position = ((int) $assessment->criteria()->max('position')) + 1;

        $assessment->criteria()->create([
            ...$validated,
            'position' => $position,
        ]);

        return back()->with('success', 'Critério adicionado à rubrica.');
    }

    public function update(
        Request $request,
        AssessmentCriterion $criterion,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $criterion->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $criterion->update($this->validateCriterion($request));

        return back()->with('success', 'Critério atualizado.');
    }

    public function destroy(
        Request $request,
        AssessmentCriterion $criterion,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $criterion->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $criterion->delete();

        return back()->with('success', 'Critério removido.');
    }

    private function validateCriterion(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'weight' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'evaluator_notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function authorizeMutation(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): void {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        if (! $assessment->isDraft()) {
            throw new LogicException('Finalized assessment criteria are immutable.');
        }
    }
}

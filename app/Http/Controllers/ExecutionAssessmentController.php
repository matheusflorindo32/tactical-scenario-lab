<?php

namespace App\Http\Controllers;

use App\Models\ExecutionAssessment;
use App\Models\Person;
use App\Models\ScenarioExecution;
use App\Services\Auth\ActiveOrganization;
use App\Services\ExecutionAssessmentManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutionAssessmentController extends Controller
{
    public function store(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
        ExecutionAssessmentManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        $this->ensureOrganization($execution->organization_id, $organizationId);

        $assessment = $execution->assessment()->first()
            ?? $manager->createForExecution($execution);

        return redirect()
            ->route('assessments.show', $assessment)
            ->with('success', 'Avaliação da execução disponível.');
    }

    public function show(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_VIEW);
        $this->ensureOrganization($assessment->organization_id, $organizationId);

        $assessment->load([
            'execution.scenarioVersion.scenario',
            'criteria.evidence.event',
            'criticalErrorOccurrences.event',
            'keyTimes',
            'debrief.entries',
            'debrief.actionItems.responsiblePerson',
            'finalizer',
        ]);

        $access = $request->user()
            ->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->first();
        $canEvaluate = in_array(AccessAbility::EVALUATIONS_MANAGE, $access?->abilities ?? [], true);

        $people = $canEvaluate
            ? Person::query()
                ->where('status', 'active')
                ->whereHas('memberships', fn ($query) => $query
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->whereNull('ended_at'))
                ->orderBy('display_name')
                ->get(['id', 'uuid', 'display_name', 'social_name'])
            : collect();

        $events = $canEvaluate
            ? $assessment->execution->events()
                ->orderBy('occurred_at')
                ->get(['id', 'uuid', 'kind', 'occurred_at', 'summary'])
            : collect();

        $criticalCatalog = is_array($assessment->execution->scenarioVersion->critical_errors)
            ? $assessment->execution->scenarioVersion->critical_errors
            : [];

        return view('assessments.show', [
            'assessment' => $assessment,
            'execution' => $assessment->execution,
            'canEvaluate' => $canEvaluate,
            'people' => $people,
            'events' => $events,
            'criticalCatalog' => $criticalCatalog,
        ]);
    }

    public function adjustment(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
        ExecutionAssessmentManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        $this->ensureOrganization($assessment->organization_id, $organizationId);

        $validated = $request->validate([
            'evaluator_adjustment' => ['required', 'integer', 'min:-10', 'max:10'],
            'adjustment_justification' => ['nullable', 'string', 'max:2000'],
        ]);

        $manager->setAdjustment(
            $assessment,
            (int) $validated['evaluator_adjustment'],
            $validated['adjustment_justification'] ?? null,
        );

        return back()->with('success', 'Ajuste do avaliador atualizado.');
    }

    public function finalize(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
        ExecutionAssessmentManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        $this->ensureOrganization($assessment->organization_id, $organizationId);

        $manager->finalize($assessment, $request->user());

        return back()->with('success', 'Avaliação finalizada e congelada como registro institucional.');
    }

    private function ensureOrganization(int $resourceOrganizationId, int $activeOrganizationId): void
    {
        abort_unless(
            $resourceOrganizationId === $activeOrganizationId,
            403,
            'A avaliação solicitada pertence a outra organização.',
        );
    }
}

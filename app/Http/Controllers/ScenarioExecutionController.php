<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Services\Auth\ActiveOrganization;
use App\Services\ScenarioExecutionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScenarioExecutionController extends Controller
{
    public function store(
        Request $request,
        ScenarioVersion $scenarioVersion,
        ActiveOrganization $activeOrganization,
        ScenarioExecutionManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $scenario = $scenarioVersion->scenario()->firstOrFail();

        $this->ensureOrganization($scenario->organization_id, $organizationId);

        $execution = $manager->create($scenarioVersion);

        return redirect()
            ->route('executions.show', $execution)
            ->with('success', 'Execução criada como rascunho.');
    }

    public function show(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_VIEW);
        $this->ensureOrganization($execution->organization_id, $organizationId);

        $execution->load([
            'scenarioVersion.scenario',
            'teams.participants.person',
            'participants.person',
            'events.team',
            'events.participant.person',
            'injects',
            'resources',
        ]);

        $access = $request->user()
            ->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->first();
        $canManage = in_array(AccessAbility::SCENARIOS_MANAGE, $access?->abilities ?? [], true);

        $people = $canManage
            ? Person::query()
                ->where('status', 'active')
                ->whereHas('memberships', fn ($query) => $query
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->whereNull('ended_at'))
                ->orderBy('display_name')
                ->get(['id', 'uuid', 'display_name', 'social_name'])
            : collect();

        return view('executions.show', compact('execution', 'canManage', 'people'));
    }

    public function start(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
        ScenarioExecutionManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $this->ensureOrganization($execution->organization_id, $organizationId);
        $manager->start($execution);

        return back()->with('success', 'Execução iniciada.');
    }

    public function complete(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
        ScenarioExecutionManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $this->ensureOrganization($execution->organization_id, $organizationId);
        $manager->complete($execution);

        return back()->with('success', 'Execução concluída.');
    }

    public function cancel(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
        ScenarioExecutionManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $this->ensureOrganization($execution->organization_id, $organizationId);
        $manager->cancel($execution);

        return back()->with('success', 'Execução cancelada.');
    }

    private function ensureOrganization(int $resourceOrganizationId, int $activeOrganizationId): void
    {
        abort_unless(
            $resourceOrganizationId === $activeOrganizationId,
            403,
            'A execução solicitada pertence a outra organização.',
        );
    }
}

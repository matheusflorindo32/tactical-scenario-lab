<?php

namespace App\Http\Controllers;

use App\Models\ExecutionInject;
use App\Models\ScenarioExecution;
use App\Services\Auth\ActiveOrganization;
use App\Services\ExecutionInjectManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExecutionInjectController extends Controller
{
    public function store(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        abort_unless($execution->organization_id === $organizationId, 403);
        abort_unless($execution->canConfigure(), 409, 'A execução encerrada não pode receber novos injects.');

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'planned_offset_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $execution->injects()->create([
            ...$validated,
            'status' => 'planned',
        ]);

        return back()->with('success', 'Inject planejado para a execução.');
    }

    public function deliver(
        Request $request,
        ExecutionInject $inject,
        ActiveOrganization $activeOrganization,
        ExecutionInjectManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $execution = $inject->execution()->firstOrFail();
        abort_unless($execution->organization_id === $organizationId, 403);

        $manager->deliver($inject);

        return back()->with('success', 'Inject entregue e registrado na timeline.');
    }

    public function cancel(
        Request $request,
        ExecutionInject $inject,
        ActiveOrganization $activeOrganization,
        ExecutionInjectManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $execution = $inject->execution()->firstOrFail();
        abort_unless($execution->organization_id === $organizationId, 403);

        $manager->cancel($inject);

        return back()->with('success', 'Inject cancelado.');
    }
}

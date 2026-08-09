<?php

namespace App\Http\Controllers;

use App\Models\ScenarioTemplate;
use App\Models\ScenarioVersion;
use App\Services\Auth\ActiveOrganization;
use App\Services\ScenarioTemplateManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScenarioTemplateController extends Controller
{
    public function index(Request $request, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_VIEW);
        $access = $request->user()
            ->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->first();

        return view('scenario-templates.index', [
            'templates' => ScenarioTemplate::query()
                ->where('organization_id', $organizationId)
                ->with(['sourceVersion.scenario', 'creator'])
                ->latest()
                ->paginate(20),
            'canManage' => in_array(AccessAbility::SCENARIOS_MANAGE, $access?->abilities ?? [], true),
        ]);
    }

    public function store(
        Request $request,
        ScenarioVersion $scenarioVersion,
        ActiveOrganization $activeOrganization,
        ScenarioTemplateManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $scenario = $scenarioVersion->scenario()->firstOrFail();
        abort_unless($scenario->organization_id === $organizationId, 403, 'A versão solicitada pertence a outra organização.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $manager->create(
            $scenarioVersion,
            $organizationId,
            $request->user(),
            $validated['name'],
            $validated['description'] ?? null,
        );

        return redirect()
            ->route('scenario-templates.index')
            ->with('success', 'Template institucional criado com sucesso.');
    }

    public function use(
        Request $request,
        ScenarioTemplate $scenarioTemplate,
        ActiveOrganization $activeOrganization,
        ScenarioTemplateManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        abort_unless($scenarioTemplate->organization_id === $organizationId, 403, 'O template solicitado pertence a outra organização.');

        $scenario = $manager->use($scenarioTemplate, $request->user());

        return redirect()
            ->route('scenarios.show', $scenario)
            ->with('success', 'Novo cenário rascunho criado a partir do template.');
    }

    public function archive(
        Request $request,
        ScenarioTemplate $scenarioTemplate,
        ActiveOrganization $activeOrganization,
        ScenarioTemplateManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        abort_unless($scenarioTemplate->organization_id === $organizationId, 403, 'O template solicitado pertence a outra organização.');

        $manager->archive($scenarioTemplate);

        return redirect()
            ->route('scenario-templates.index')
            ->with('success', 'Template arquivado.');
    }
}

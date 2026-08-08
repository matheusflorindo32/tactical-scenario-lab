<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Services\Auth\ActiveOrganization;
use App\Services\ScenarioGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function index(Request $request, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensureAbility($request, 'scenarios.view');

        return view('scenarios.index', [
            'scenarios' => Scenario::query()
                ->where('organization_id', $organizationId)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(Request $request, ActiveOrganization $activeOrganization): View
    {
        $activeOrganization->ensureAbility($request, 'scenarios.manage');

        return view('scenarios.create');
    }

    public function store(
        Request $request,
        ScenarioGenerator $generator,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, 'scenarios.manage');

        $validated = $request->validate([
            'environment' => ['required', 'string', 'max:100'],
            'threat_level' => ['required', Rule::in(['controlada', 'potencial', 'ativa'])],
            'casualties' => ['nullable', 'integer', 'min:1', 'required_without:estimated_casualty_count'],
            'estimated_casualty_count' => ['nullable', 'integer', 'min:1', 'required_without:casualties'],
            'mechanism' => ['required', 'string', 'max:150'],
            'resources' => ['nullable', 'array', 'max:20'],
            'resources.*' => ['string', 'max:80', 'distinct'],
        ]);

        $estimatedCasualtyCount = (int) ($validated['estimated_casualty_count'] ?? $validated['casualties']);
        $validated['casualties'] = $estimatedCasualtyCount;
        $validated['estimated_casualty_count'] = $estimatedCasualtyCount;
        $definition = $generator->generate($validated);

        $scenario = DB::transaction(function () use ($definition, $organizationId): Scenario {
            $scenario = Scenario::create([
                ...$definition,
                'organization_id' => $organizationId,
            ]);

            $scenario->versions()->create([
                'version_number' => 1,
                'environment' => $definition['environment'],
                'threat_level' => $definition['threat_level'],
                'mechanism' => $definition['mechanism'],
                'estimated_casualty_count' => $definition['estimated_casualty_count'],
                'resources' => $definition['resources'],
                'learning_objectives' => $definition['learning_objectives'],
                'expected_actions' => $definition['expected_actions'],
                'critical_errors' => $definition['critical_errors'],
                'publication_status' => 'draft',
            ]);

            return $scenario;
        });

        return redirect()
            ->route('scenarios.show', $scenario)
            ->with('success', 'Cenário criado como rascunho.');
    }

    public function show(
        Request $request,
        Scenario $scenario,
        ActiveOrganization $activeOrganization,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, 'scenarios.view');
        $this->ensureScenarioInOrganization($scenario, $organizationId);

        return view('scenarios.show', compact('scenario'));
    }

    /**
     * Inicia a execução de um cenário em rascunho.
     * Idempotente: se já está em `running` ou `completed`, não muta e
     * devolve mensagem clara.
     */
    public function execute(
        Request $request,
        Scenario $scenario,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, 'scenarios.manage');
        $this->ensureScenarioInOrganization($scenario, $organizationId);

        if (! $scenario->canBeStarted()) {
            return back()->with('error', 'Este cenário não pode ser iniciado (status atual: '.$scenario->status.').');
        }

        DB::transaction(function () use ($scenario) {
            $scenario->update([
                'status' => 'running',
                'started_at' => now(),
            ]);
        });

        return back()->with('success', 'Execução iniciada.');
    }

    /**
     * Registra avaliação e fecha o cenário.
     *
     * - Aceita reeavaliação enquanto `completed` (edição controlada).
     * - Rejeita se ainda estiver em `draft` — não é possível avaliar
     *   sem antes iniciar a execução.
     * - `observed_critical_errors` é livre em conteúdo, mas cada item
     *   precisa vir do catálogo gerado (`critical_errors`) para evitar
     *   inserção arbitrária vinda do formulário.
     */
    public function evaluate(
        Request $request,
        Scenario $scenario,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, 'evaluations.manage');
        $this->ensureScenarioInOrganization($scenario, $organizationId);

        if (! $scenario->canBeEvaluated()) {
            return back()->with('error', 'Inicie a execução antes de avaliar.');
        }

        $catalog = is_array($scenario->critical_errors) ? $scenario->critical_errors : [];

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'debrief_notes' => ['nullable', 'string', 'max:5000'],
            'observed_critical_errors' => ['nullable', 'array'],
            'observed_critical_errors.*' => ['string', 'distinct', Rule::in($catalog)],
        ]);

        DB::transaction(function () use ($scenario, $validated) {
            $scenario->update([
                'score' => $validated['score'],
                'debrief_notes' => $validated['debrief_notes'] ?? null,
                'observed_critical_errors' => $validated['observed_critical_errors'] ?? [],
                'status' => 'completed',
                'completed_at' => $scenario->completed_at ?? now(),
            ]);
        });

        return back()->with('success', 'Avaliação registrada e cenário concluído.');
    }

    private function ensureScenarioInOrganization(Scenario $scenario, int $organizationId): void
    {
        abort_unless(
            $scenario->organization_id === $organizationId,
            403,
            'O cenário solicitado pertence a outra organização.',
        );
    }
}

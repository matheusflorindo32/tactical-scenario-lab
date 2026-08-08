<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Services\ScenarioGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function index(): View
    {
        return view('scenarios.index', [
            'scenarios' => Scenario::latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('scenarios.create');
    }

    public function store(Request $request, ScenarioGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'environment' => ['required', 'string', 'max:100'],
            'threat_level' => ['required', Rule::in(['controlada', 'potencial', 'ativa'])],
            'casualties' => ['required', 'integer', 'min:1', 'max:10'],
            'mechanism' => ['required', 'string', 'max:150'],
            'resources' => ['nullable', 'array', 'max:20'],
            'resources.*' => ['string', 'max:80', 'distinct'],
        ]);

        $scenario = Scenario::create($generator->generate($validated));

        return redirect()
            ->route('scenarios.show', $scenario)
            ->with('success', 'Cenário criado como rascunho.');
    }

    public function show(Scenario $scenario): View
    {
        return view('scenarios.show', compact('scenario'));
    }

    /**
     * Inicia a execução de um cenário em rascunho.
     * Idempotente: se já está em `running` ou `completed`, não muta e
     * devolve mensagem clara.
     */
    public function execute(Scenario $scenario): RedirectResponse
    {
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
    public function evaluate(Request $request, Scenario $scenario): RedirectResponse
    {
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
}

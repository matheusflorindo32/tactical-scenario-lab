<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Services\ScenarioGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function index(): View
    {
        return view('scenarios.index', ['scenarios' => Scenario::latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('scenarios.create');
    }

    public function store(Request $request, ScenarioGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'environment' => ['required', 'string', 'max:100'],
            'threat_level' => ['required', 'in:controlada,potencial,ativa'],
            'casualties' => ['required', 'integer', 'min:1', 'max:10'],
            'mechanism' => ['required', 'string', 'max:150'],
            'resources' => ['nullable', 'array'],
            'resources.*' => ['string', 'max:80'],
        ]);
        $scenario = Scenario::create($generator->generate($validated));

        return redirect()->route('scenarios.show', $scenario)->with('success', 'Cenário criado com sucesso.');
    }

    public function show(Scenario $scenario): View
    {
        return view('scenarios.show', compact('scenario'));
    }

    public function execute(Scenario $scenario): RedirectResponse
    {
        $scenario->update(['status' => 'running']);

        return back()->with('success', 'Execução iniciada.');
    }

    public function evaluate(Request $request, Scenario $scenario): RedirectResponse
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'debrief_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $scenario->update([...$validated, 'status' => 'completed']);

        return back()->with('success', 'Avaliação registrada.');
    }
}

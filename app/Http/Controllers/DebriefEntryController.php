<?php

namespace App\Http\Controllers;

use App\Models\DebriefEntry;
use App\Models\ExecutionAssessment;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LogicException;

class DebriefEntryController extends Controller
{
    public function store(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $this->authorizeMutation($request, $assessment, $activeOrganization);

        $validated = $request->validate([
            'kind' => ['required', Rule::in(['fact', 'interpretation', 'recommendation'])],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $debrief = $assessment->debrief()->firstOrCreate([]);
        $position = ((int) $debrief->entries()->max('position')) + 1;

        $debrief->entries()->create([
            ...$validated,
            'position' => $position,
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Registro de debriefing adicionado.');
    }

    public function update(
        Request $request,
        DebriefEntry $entry,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $entry->debrief()->firstOrFail()->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);

        $entry->update($request->validate([
            'kind' => ['required', Rule::in(['fact', 'interpretation', 'recommendation'])],
            'content' => ['required', 'string', 'max:5000'],
        ]));

        return back()->with('success', 'Registro de debriefing atualizado.');
    }

    public function destroy(
        Request $request,
        DebriefEntry $entry,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $entry->debrief()->firstOrFail()->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $entry->delete();

        return back()->with('success', 'Registro de debriefing removido.');
    }

    private function authorizeMutation(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): void {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        if (! $assessment->isDraft()) {
            throw new LogicException('Finalized assessment debrief is immutable.');
        }
    }
}

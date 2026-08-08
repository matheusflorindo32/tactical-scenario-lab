<?php

namespace App\Http\Controllers;

use App\Models\ExecutionAssessment;
use App\Models\KeyTimeRecord;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class KeyTimeRecordController extends Controller
{
    public function store(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $this->authorizeMutation($request, $assessment, $activeOrganization);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'occurred_at' => ['required', 'date'],
            'reference_seconds' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $assessment->keyTimes()->create($validated);

        return back()->with('success', 'Tempo-chave registrado.');
    }

    public function destroy(
        Request $request,
        KeyTimeRecord $keyTime,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $keyTime->assessment()->firstOrFail();
        $this->authorizeMutation($request, $assessment, $activeOrganization);
        $keyTime->delete();

        return back()->with('success', 'Tempo-chave removido.');
    }

    private function authorizeMutation(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): void {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        if (! $assessment->isDraft()) {
            throw new LogicException('Finalized key time records are immutable.');
        }
    }
}

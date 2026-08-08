<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\ExecutionAssessment;
use App\Models\Person;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;

class ActionItemController extends Controller
{
    public function store(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $this->authorizeContentMutation($request, $assessment, $activeOrganization);
        $validated = $this->validateContent($request);
        $personId = $this->resolveResponsiblePerson($assessment, $validated['responsible_person_uuid'] ?? null);
        $debrief = $assessment->debrief()->firstOrCreate([]);

        $debrief->actionItems()->create([
            'action' => $validated['action'],
            'responsible_person_id' => $personId,
            'responsible_label' => $validated['responsible_label'] ?? null,
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Ação adicionada ao plano.');
    }

    public function update(
        Request $request,
        ActionItem $actionItem,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $this->assessmentFor($actionItem);
        $this->authorizeContentMutation($request, $assessment, $activeOrganization);
        $validated = $this->validateContent($request);
        $personId = $this->resolveResponsiblePerson($assessment, $validated['responsible_person_uuid'] ?? null);

        $actionItem->update([
            'action' => $validated['action'],
            'responsible_person_id' => $personId,
            'responsible_label' => $validated['responsible_label'] ?? null,
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Ação atualizada.');
    }

    public function destroy(
        Request $request,
        ActionItem $actionItem,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $this->assessmentFor($actionItem);
        $this->authorizeContentMutation($request, $assessment, $activeOrganization);
        $actionItem->delete();

        return back()->with('success', 'Ação removida.');
    }

    public function transition(
        Request $request,
        ActionItem $actionItem,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $assessment = $this->assessmentFor($actionItem);
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'completed', 'cancelled'])],
        ]);

        $actionItem->transitionTo($validated['status'], $request->user());

        return back()->with('success', 'Status da ação atualizado.');
    }

    private function validateContent(Request $request): array
    {
        return $request->validate([
            'action' => ['required', 'string', 'max:5000'],
            'responsible_person_uuid' => ['nullable', 'uuid', 'required_without:responsible_label'],
            'responsible_label' => ['nullable', 'string', 'max:200', 'required_without:responsible_person_uuid'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function resolveResponsiblePerson(ExecutionAssessment $assessment, ?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        $person = Person::query()
            ->where('uuid', $uuid)
            ->where('status', 'active')
            ->whereHas('memberships', fn ($query) => $query
                ->where('organization_id', $assessment->organization_id)
                ->where('status', 'active')
                ->whereNull('ended_at'))
            ->first();

        if (! $person) {
            throw ValidationException::withMessages([
                'responsible_person_uuid' => 'A pessoa responsável não pertence ativamente a esta organização.',
            ]);
        }

        return $person->id;
    }

    private function assessmentFor(ActionItem $actionItem): ExecutionAssessment
    {
        return $actionItem->debrief()->firstOrFail()->assessment()->firstOrFail();
    }

    private function authorizeContentMutation(
        Request $request,
        ExecutionAssessment $assessment,
        ActiveOrganization $activeOrganization,
    ): void {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::EVALUATIONS_MANAGE);
        abort_unless($assessment->organization_id === $organizationId, 403, 'A avaliação pertence a outra organização.');

        if (! $assessment->isDraft()) {
            throw new LogicException('Finalized assessment action content is immutable.');
        }
    }
}

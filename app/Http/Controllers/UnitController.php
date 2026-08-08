<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Organization;
use App\Models\Unit;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function create(Request $request, Organization $organization, ActiveOrganization $activeOrganization): View
    {
        $activeOrganization->ensure($request, $organization->id);

        $parents = $organization->units()->where('status', 'active')->orderBy('name')->get();

        return view('units.create', compact('organization', 'parents'));
    }

    public function store(StoreUnitRequest $request, AuditLogger $audit, ActiveOrganization $activeOrganization): RedirectResponse
    {
        $data = $request->validated();
        $activeOrganization->ensure($request, (int) $data['organization_id']);
        $this->ensureParentIsValid($data['parent_unit_id'] ?? null, (int) $data['organization_id']);

        $unit = Unit::create($data);

        $audit->record(
            'unit.created',
            $unit,
            $unit->organization_id,
            [
                'kind' => $unit->kind,
                'status' => $unit->status,
                'parent_unit_id' => $unit->parent_unit_id,
            ],
            $request,
        );

        return redirect()
            ->route('organizations.show', $unit->organization)
            ->with('success', 'Unidade cadastrada com sucesso.');
    }

    public function edit(Request $request, Unit $unit, ActiveOrganization $activeOrganization): View
    {
        $activeOrganization->ensure($request, $unit->organization_id);

        $parents = $unit->organization
            ->units()
            ->whereKeyNot($unit->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('units.edit', compact('unit', 'parents'));
    }

    public function update(
        UpdateUnitRequest $request,
        Unit $unit,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $activeOrganization->ensure($request, $unit->organization_id);

        $data = $request->validated();
        $this->ensureParentIsValid($data['parent_unit_id'] ?? null, $unit->organization_id, $unit);

        $previous = $unit->only(['kind', 'status', 'parent_unit_id']);
        $unit->update($data);

        $changedFields = collect($unit->getChanges())
            ->keys()
            ->reject(fn (string $field) => $field === 'updated_at')
            ->values()
            ->all();

        $audit->record(
            'unit.updated',
            $unit,
            $unit->organization_id,
            [
                'changed_fields' => $changedFields,
                'previous_kind' => $previous['kind'],
                'current_kind' => $unit->kind,
                'previous_status' => $previous['status'],
                'current_status' => $unit->status,
                'previous_parent_unit_id' => $previous['parent_unit_id'],
                'current_parent_unit_id' => $unit->parent_unit_id,
            ],
            $request,
        );

        return redirect()
            ->route('organizations.show', $unit->organization)
            ->with('success', 'Unidade atualizada com sucesso.');
    }

    public function deactivate(
        Request $request,
        Unit $unit,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $activeOrganization->ensure($request, $unit->organization_id);

        if ($unit->status !== 'inactive') {
            $unit->update(['status' => 'inactive']);

            $audit->record(
                'unit.deactivated',
                $unit,
                $unit->organization_id,
                ['status' => 'inactive'],
                $request,
            );
        }

        return redirect()
            ->route('organizations.show', $unit->organization)
            ->with('success', 'Unidade inativada sem apagar o histórico institucional.');
    }

    private function ensureParentIsValid(?int $parentId, int $organizationId, ?Unit $unit = null): void
    {
        if ($parentId === null) {
            return;
        }

        if ($unit && $parentId === $unit->id) {
            throw ValidationException::withMessages([
                'parent_unit_id' => 'Uma unidade não pode ser superior de si mesma.',
            ]);
        }

        $parent = Unit::query()
            ->whereKey($parentId)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_unit_id' => 'A unidade superior precisa estar ativa e pertencer à mesma organização.',
            ]);
        }

        if ($unit && $this->wouldCreateCycle($unit, $parent)) {
            throw ValidationException::withMessages([
                'parent_unit_id' => 'A hierarquia selecionada criaria um ciclo entre unidades.',
            ]);
        }
    }

    private function wouldCreateCycle(Unit $unit, Unit $parent): bool
    {
        $current = $parent;

        while ($current) {
            if ($current->id === $unit->id) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }
}

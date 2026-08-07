<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function create(Organization $organization): View
    {
        $parents = $organization->units()->where('status', 'active')->orderBy('name')->get();

        return view('units.create', compact('organization', 'parents'));
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['parent_unit_id'])) {
            $parentBelongsToOrganization = Unit::query()
                ->whereKey($data['parent_unit_id'])
                ->where('organization_id', $data['organization_id'])
                ->exists();

            if (! $parentBelongsToOrganization) {
                throw ValidationException::withMessages([
                    'parent_unit_id' => 'A unidade superior precisa pertencer à mesma organização.',
                ]);
            }
        }

        $unit = Unit::create($data);

        return redirect()
            ->route('organizations.show', $unit->organization)
            ->with('success', 'Unidade cadastrada com sucesso.');
    }
}

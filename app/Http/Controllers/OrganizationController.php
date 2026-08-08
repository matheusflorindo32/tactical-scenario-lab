<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        $organizations = Organization::query()
            ->withCount(['units', 'memberships'])
            ->orderBy('name')
            ->paginate(15);

        return view('organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        return view('organizations.create');
    }

    public function store(StoreOrganizationRequest $request, AuditLogger $audit): RedirectResponse
    {
        $organization = Organization::create($request->validated());

        $audit->record(
            'organization.created',
            $organization,
            $organization->id,
            [
                'kind' => $organization->kind,
                'status' => $organization->status,
            ],
            $request,
        );

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organização cadastrada com sucesso.');
    }

    public function show(Organization $organization): View
    {
        $organization->load([
            'units' => fn ($query) => $query->orderBy('name'),
            'memberships.person',
        ]);

        return view('organizations.show', compact('organization'));
    }

    public function edit(Organization $organization): View
    {
        return view('organizations.edit', compact('organization'));
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        AuditLogger $audit,
    ): RedirectResponse {
        $before = $organization->only(['kind', 'status']);
        $organization->update($request->validated());

        $audit->record(
            'organization.updated',
            $organization,
            $organization->id,
            [
                'changed_fields' => array_keys($organization->getChanges()),
                'previous_kind' => $before['kind'],
                'current_kind' => $organization->kind,
                'previous_status' => $before['status'],
                'current_status' => $organization->status,
            ],
            $request,
        );

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organização atualizada com sucesso.');
    }

    public function deactivate(
        Organization $organization,
        AuditLogger $audit,
    ): RedirectResponse {
        if ($organization->status !== 'inactive') {
            $organization->update(['status' => 'inactive']);

            $audit->record(
                'organization.deactivated',
                $organization,
                $organization->id,
                ['status' => 'inactive'],
            );
        }

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organização inativada sem excluir o histórico institucional.');
    }
}

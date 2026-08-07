<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
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
}

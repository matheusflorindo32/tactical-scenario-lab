<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveOrganizationController extends Controller
{
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasOrganizationAccess($organization->id),
            403,
        );

        $request->session()->put('active_organization_id', $organization->id);

        return back()->with('success', 'Contexto institucional atualizado com segurança.');
    }
}

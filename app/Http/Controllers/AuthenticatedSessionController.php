<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $firstAccess = $request->user()
            ?->activeOrganizationAccesses()
            ->orderBy('id')
            ->first();

        if ($firstAccess) {
            $request->session()->put('active_organization_id', $firstAccess->organization_id);
        } else {
            $request->session()->forget('active_organization_id');
        }

        $audit->record(
            'auth.login.succeeded',
            $request->user(),
            $firstAccess?->organization_id,
            [
                'organization_context_initialized' => $firstAccess !== null,
            ],
            $request,
        );

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $organizationId = (int) $request->session()->get('active_organization_id', 0);

        $audit->record(
            'auth.logout',
            $request->user(),
            $organizationId > 0 ? $organizationId : null,
            [
                'organization_context_present' => $organizationId > 0,
            ],
            $request,
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sessão encerrada com segurança.');
    }
}

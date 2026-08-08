<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccessAdministrationController extends Controller
{
    public function index(Request $request, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $organization = Organization::query()->findOrFail($organizationId);

        $accesses = UserOrganizationAccess::query()
            ->where('organization_id', $organizationId)
            ->currentlyValid()
            ->with('user:id,name,email,status')
            ->orderBy('role')
            ->orderBy('user_id')
            ->paginate(20);

        return view('access.index', [
            'organization' => $organization,
            'accesses' => $accesses,
            'abilityLabels' => AccessAbility::labels(),
        ]);
    }

    public function create(Request $request, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);

        return view('access.create', [
            'organization' => Organization::query()->findOrFail($organizationId),
            'abilityLabels' => AccessAbility::labels(),
        ]);
    }

    public function store(
        Request $request,
        ActiveOrganization $activeOrganization,
        AuditLogger $audit,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $validated = $this->validateGrant($request);
        $this->ensureAdministrativeAccessDoesNotExpire($validated['abilities'], $validated['expires_at'] ?? null);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($validated['email']))])
            ->firstOrFail();

        $access = DB::transaction(function () use ($organizationId, $validated, $user): UserOrganizationAccess {
            $existing = UserOrganizationAccess::query()
                ->where('user_id', $user->id)
                ->where('organization_id', $organizationId)
                ->where('role', $validated['role'])
                ->first();

            if ($existing && $existing->isActive()) {
                throw ValidationException::withMessages([
                    'email' => 'Esta conta já possui uma concessão ativa com este papel na organização.',
                ]);
            }

            if ($existing) {
                $existing->update([
                    'abilities' => $validated['abilities'],
                    'granted_at' => now(),
                    'expires_at' => $validated['expires_at'] ?? null,
                    'revoked_at' => null,
                ]);

                return $existing->fresh();
            }

            return UserOrganizationAccess::create([
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'role' => $validated['role'],
                'abilities' => $validated['abilities'],
                'granted_at' => now(),
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
        });

        $audit->record(
            'access.granted',
            $access,
            $organizationId,
            [
                'role' => $access->role,
                'abilities' => $access->abilities ?? [],
                'expires_at' => $access->expires_at?->toIso8601String(),
                'regrant' => $access->wasRecentlyCreated === false,
            ],
            $request,
        );

        return redirect()
            ->route('access.index')
            ->with('success', 'Acesso institucional concedido com segurança.');
    }

    public function edit(
        Request $request,
        UserOrganizationAccess $access,
        ActiveOrganization $activeOrganization,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $this->ensureManagedAccess($access, $organizationId);

        return view('access.edit', [
            'organization' => Organization::query()->findOrFail($organizationId),
            'access' => $access->load('user:id,name,email,status'),
            'abilityLabels' => AccessAbility::labels(),
        ]);
    }

    public function update(
        Request $request,
        UserOrganizationAccess $access,
        ActiveOrganization $activeOrganization,
        AuditLogger $audit,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $this->ensureManagedAccess($access, $organizationId);

        $validated = $request->validate([
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'distinct', Rule::in(AccessAbility::all())],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        $this->ensureAdministrativeAccessDoesNotExpire($validated['abilities'], $validated['expires_at'] ?? null);

        $removesAccessManagement = in_array(AccessAbility::ACCESS_MANAGE, $access->abilities ?? [], true)
            && ! in_array(AccessAbility::ACCESS_MANAGE, $validated['abilities'], true);

        if ($removesAccessManagement) {
            $this->ensureAnotherAdministratorExists($organizationId, $access->id);
        }

        $before = [
            'abilities' => $access->abilities ?? [],
            'expires_at' => $access->expires_at?->toIso8601String(),
        ];
        $access->update([
            'abilities' => $validated['abilities'],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $audit->record(
            'access.updated',
            $access,
            $organizationId,
            [
                'role' => $access->role,
                'previous_abilities' => $before['abilities'],
                'current_abilities' => $access->abilities ?? [],
                'previous_expires_at' => $before['expires_at'],
                'current_expires_at' => $access->expires_at?->toIso8601String(),
            ],
            $request,
        );

        return redirect()
            ->route('access.index')
            ->with('success', 'Habilidades e validade do acesso atualizadas.');
    }

    public function revoke(
        Request $request,
        UserOrganizationAccess $access,
        ActiveOrganization $activeOrganization,
        AuditLogger $audit,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $this->ensureManagedAccess($access, $organizationId);

        if (in_array(AccessAbility::ACCESS_MANAGE, $access->abilities ?? [], true)) {
            $this->ensureAnotherAdministratorExists($organizationId, $access->id);
        }

        $access->update(['revoked_at' => now()]);

        $audit->record(
            'access.revoked',
            $access,
            $organizationId,
            [
                'role' => $access->role,
                'abilities' => $access->abilities ?? [],
                'expires_at' => $access->expires_at?->toIso8601String(),
            ],
            $request,
        );

        return redirect()
            ->route('access.index')
            ->with('success', 'Acesso revogado sem excluir o histórico da concessão.');
    }

    public function deactivateAccount(
        Request $request,
        User $user,
        ActiveOrganization $activeOrganization,
        AuditLogger $audit,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $this->ensureManagedAccount($user, $organizationId);

        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'account' => 'A própria conta administrativa não pode ser inativada por este fluxo.',
            ]);
        }

        if ($user->isActive()) {
            $hasAdministrativeGrant = $user->organizationAccesses()
                ->where('organization_id', $organizationId)
                ->currentlyValid()
                ->get()
                ->contains(fn (UserOrganizationAccess $access): bool => in_array(
                    AccessAbility::ACCESS_MANAGE,
                    $access->abilities ?? [],
                    true,
                ));

            if ($hasAdministrativeGrant) {
                $this->ensureAnotherAdministratorUserExists($organizationId, $user->id);
            }

            $user->update(['status' => 'inactive']);

            $audit->record(
                'account.deactivated',
                $user,
                $organizationId,
                ['status' => 'inactive'],
                $request,
            );
        }

        return redirect()
            ->route('access.index')
            ->with('success', 'Conta inativada. Sessões existentes serão bloqueadas no próximo acesso.');
    }

    public function reactivateAccount(
        Request $request,
        User $user,
        ActiveOrganization $activeOrganization,
        AuditLogger $audit,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $this->ensureManagedAccount($user, $organizationId);

        if (! $user->isActive()) {
            $user->update(['status' => 'active']);

            $audit->record(
                'account.reactivated',
                $user,
                $organizationId,
                ['status' => 'active'],
                $request,
            );
        }

        return redirect()
            ->route('access.index')
            ->with('success', 'Conta reativada com segurança.');
    }

    private function validateGrant(Request $request): array
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        return $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::exists('users', 'email')],
            'role' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'distinct', Rule::in(AccessAbility::all())],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
    }

    private function ensureManagedAccess(UserOrganizationAccess $access, int $organizationId): void
    {
        abort_if(
            $access->organization_id !== $organizationId || ! $access->isActive(),
            403,
            'A concessão solicitada não pertence ao contexto institucional ativo ou já expirou.',
        );
    }

    private function ensureManagedAccount(User $user, int $organizationId): void
    {
        $hasCurrentOrganizationAccess = $user->organizationAccesses()
            ->where('organization_id', $organizationId)
            ->currentlyValid()
            ->exists();

        abort_unless(
            $hasCurrentOrganizationAccess,
            403,
            'A conta solicitada não possui concessão ativa no contexto institucional atual.',
        );

        $hasOtherActiveGrant = $user->organizationAccesses()
            ->where('organization_id', '!=', $organizationId)
            ->currentlyValid()
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'))
            ->exists();

        if ($hasOtherActiveGrant) {
            throw ValidationException::withMessages([
                'account' => 'O status da conta é global. Esta conta também possui acesso ativo em outra organização; revogue apenas a concessão local ou encaminhe a alteração a uma administração de nível superior.',
            ]);
        }
    }

    private function ensureAdministrativeAccessDoesNotExpire(array $abilities, mixed $expiresAt): void
    {
        if ($expiresAt !== null && in_array(AccessAbility::ACCESS_MANAGE, $abilities, true)) {
            throw ValidationException::withMessages([
                'expires_at' => 'Concessões com access.manage não podem expirar automaticamente. Transfira a administração antes de limitar a validade.',
            ]);
        }
    }

    private function ensureAnotherAdministratorExists(int $organizationId, int $excludedAccessId): void
    {
        $anotherAdministratorExists = UserOrganizationAccess::query()
            ->where('organization_id', $organizationId)
            ->currentlyValid()
            ->whereKeyNot($excludedAccessId)
            ->whereJsonContains('abilities', AccessAbility::ACCESS_MANAGE)
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->exists();

        if (! $anotherAdministratorExists) {
            throw ValidationException::withMessages([
                'access' => 'A organização precisa manter ao menos um administrador ativo com access.manage.',
            ]);
        }
    }

    private function ensureAnotherAdministratorUserExists(int $organizationId, int $excludedUserId): void
    {
        $anotherAdministratorExists = UserOrganizationAccess::query()
            ->where('organization_id', $organizationId)
            ->currentlyValid()
            ->where('user_id', '!=', $excludedUserId)
            ->whereJsonContains('abilities', AccessAbility::ACCESS_MANAGE)
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->exists();

        if (! $anotherAdministratorExists) {
            throw ValidationException::withMessages([
                'account' => 'A organização precisa manter ao menos um administrador ativo com access.manage.',
            ]);
        }
    }
}

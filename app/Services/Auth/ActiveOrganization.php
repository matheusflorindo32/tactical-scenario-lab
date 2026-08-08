<?php

namespace App\Services\Auth;

use App\Models\Person;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ActiveOrganization
{
    public function id(Request $request): int
    {
        $organizationId = (int) $request->session()->get('active_organization_id', 0);

        $this->ensureAccess($request, $organizationId);

        return $organizationId;
    }

    public function ensureAccess(Request $request, int $organizationId): void
    {
        $user = $request->user();

        if ($organizationId < 1 || ! $user || ! $user->hasOrganizationAccess($organizationId)) {
            throw new HttpException(403, 'O usuário não possui acesso institucional válido para esta organização.');
        }
    }

    public function ensureAbility(Request $request, string $ability, ?int $organizationId = null): int
    {
        $organizationId ??= $this->id($request);
        $this->ensureAccess($request, $organizationId);

        $access = $request->user()
            ->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->first();

        if (! $access || ! in_array($ability, $access->abilities ?? [], true)) {
            throw new HttpException(403, 'O usuário não possui a habilidade necessária para esta operação.');
        }

        return $organizationId;
    }

    public function ensure(Request $request, int $organizationId): void
    {
        if ($this->id($request) !== $organizationId) {
            throw new HttpException(403, 'O recurso solicitado pertence a outra organização.');
        }
    }

    public function ensurePerson(Request $request, Person $person, bool $requireActiveMembership = false): int
    {
        $organizationId = $this->id($request);

        $membership = $person->memberships()
            ->where('organization_id', $organizationId)
            ->when($requireActiveMembership, fn ($query) => $query
                ->where('status', 'active')
                ->whereNull('ended_at'))
            ->exists();

        if (! $membership) {
            throw new HttpException(403, 'A pessoa solicitada não pertence ao contexto institucional ativo.');
        }

        return $organizationId;
    }
}

<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ActiveOrganization
{
    public function id(Request $request): int
    {
        $organizationId = (int) $request->session()->get('active_organization_id', 0);
        $user = $request->user();

        if ($organizationId < 1 || ! $user || ! $user->hasOrganizationAccess($organizationId)) {
            throw new HttpException(403, 'Nenhuma organização ativa válida foi selecionada.');
        }

        return $organizationId;
    }

    public function ensure(Request $request, int $organizationId): void
    {
        if ($this->id($request) !== $organizationId) {
            throw new HttpException(403, 'O recurso solicitado pertence a outra organização.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ExecutionResource;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ExecutionResourceController extends Controller
{
    public function update(
        Request $request,
        ExecutionResource $resource,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $execution = $resource->execution()->firstOrFail();

        abort_unless($execution->organization_id === $organizationId, 403);
        abort_unless($execution->canConfigure(), 409, 'Recursos não podem ser alterados após o encerramento da execução.');

        $validated = $request->validate([
            'planned_quantity' => ['required', 'integer', 'min:0'],
            'available_quantity' => ['required', 'integer', 'min:0'],
            'used_quantity' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(['available', 'unavailable', 'depleted'])],
        ]);

        try {
            $resource->update($validated);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'used_quantity' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Recurso da execução atualizado.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\ProductionConfigurationValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HealthController extends Controller
{
    public function __construct(
        private readonly ProductionConfigurationValidator $productionConfiguration,
    ) {}

    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
        ])->withHeaders([
            'X-Diagnostic-Session-Secure' => config('session.secure') === true ? 'true' : 'false',
            'X-Diagnostic-Session-HttpOnly' => config('session.http_only') === true ? 'true' : 'false',
            'X-Diagnostic-Session-SameSite-Lax' => strtolower((string) config('session.same_site')) === 'lax' ? 'true' : 'false',
        ]);
    }

    public function ready(): JsonResponse
    {
        try {
            if (app()->environment('production')) {
                $this->productionConfiguration->assertSafe();
            }

            DB::select('select 1');
        } catch (Throwable) {
            Log::warning('Health readiness check unavailable.', [
                'category' => 'readiness_unavailable',
            ]);

            return response()->json([
                'status' => 'unavailable',
                'database' => 'unavailable',
            ], 503);
        }

        return response()->json([
            'status' => 'ready',
            'database' => 'ok',
        ]);
    }
}

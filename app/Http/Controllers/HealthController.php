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
        error_log(sprintf(
            'SESSION_DIAG secure=%s httponly=%s samesite_lax=%s',
            config('session.secure') === true ? 'true' : 'false',
            config('session.http_only') === true ? 'true' : 'false',
            strtolower((string) config('session.same_site')) === 'lax' ? 'true' : 'false',
        ));

        return response()->json([
            'status' => 'ok',
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

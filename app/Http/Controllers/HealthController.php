<?php

namespace App\Http\Controllers;

use App\Support\ProductionConfigurationValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
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
        $production = app()->environment('production');
        $sslmode = (string) config('database.connections.pgsql.sslmode', '');
        $host = (string) config('database.connections.pgsql.host', '');
        $rootCert = getenv('PGSSLROOTCERT');
        $rootCertSet = is_string($rootCert) && $rootCert !== '';
        $validatorPass = $production && $this->productionConfiguration->violations() === [];
        $preflightExecuted = false;
        $preflightPassed = false;

        if ($production) {
            $preflightExecuted = true;
            $preflightPassed = Artisan::call('production:preflight', ['--database' => true]) === 0;
        }

        Log::info('Release diagnostic snapshot.', [
            'category' => 'release_diagnostic',
            'production' => $production,
            'pgsql' => config('database.default') === 'pgsql',
            'db_url_set' => config('database.connections.pgsql.url') !== null,
            'sslmode' => $sslmode,
            'verify_full' => $sslmode === 'verify-full',
            'root_cert_set' => $rootCertSet,
            'root_cert_system' => $rootCertSet && $rootCert === 'system',
            'host_is_hostname' => $host !== '' && $host !== 'localhost' && filter_var($host, FILTER_VALIDATE_IP) === false,
            'validator_pass' => $validatorPass,
            'preflight_executed' => $preflightExecuted,
            'preflight_passed' => $preflightPassed,
        ]);

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

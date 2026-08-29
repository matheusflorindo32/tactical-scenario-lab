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

        error_log(sprintf(
            'RELEASE_DIAG production=%s pgsql=%s db_url_set=%s sslmode=%s verify_full=%s root_cert_set=%s root_cert_system=%s host_is_hostname=%s validator_pass=%s preflight_executed=%s preflight_passed=%s',
            $production ? 'true' : 'false',
            config('database.default') === 'pgsql' ? 'true' : 'false',
            config('database.connections.pgsql.url') !== null ? 'true' : 'false',
            $sslmode,
            $sslmode === 'verify-full' ? 'true' : 'false',
            $rootCertSet ? 'true' : 'false',
            $rootCertSet && $rootCert === 'system' ? 'true' : 'false',
            $host !== '' && $host !== 'localhost' && filter_var($host, FILTER_VALIDATE_IP) === false ? 'true' : 'false',
            $validatorPass ? 'true' : 'false',
            $preflightExecuted ? 'true' : 'false',
            $preflightPassed ? 'true' : 'false',
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

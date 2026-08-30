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
        try {
            $production = app()->environment('production');
            $connection = DB::connection();
            DB::select('select 1');

            $sslmode = strtolower((string) $connection->getConfig('sslmode'));
            $configuredRootCert = $connection->getConfig('sslrootcert');
            $environmentRootCert = getenv('PGSSLROOTCERT') ?: null;
            $rootCert = is_string($configuredRootCert) && $configuredRootCert !== ''
                ? $configuredRootCert
                : (is_string($environmentRootCert) ? $environmentRootCert : '');
            $homeRootCert = rtrim((string) getenv('HOME'), '/').'/.postgresql/root.crt';
            $rootCertReadable = $rootCert === 'system'
                || ($rootCert !== '' && is_readable($rootCert))
                || ($homeRootCert !== '/.postgresql/root.crt' && is_readable($homeRootCert));
            $host = (string) $connection->getConfig('host');
            $validatorPass = $production && $this->productionConfiguration->violations() === [];
            $preflightExecuted = false;
            $preflightPassed = false;

            if ($production) {
                $preflightExecuted = true;
                $preflightPassed = Artisan::call('production:preflight', ['--database' => true]) === 0;
            }

            error_log(sprintf(
                'RELEASE_DIAG production=%s pgsql=%s db_url_set=%s sslmode=%s verify_full=%s root_cert_set=%s root_cert_system=%s root_cert_readable_or_system=%s host_is_hostname=%s validator_pass=%s preflight_executed=%s preflight_passed=%s',
                $production ? 'true' : 'false',
                config('database.default') === 'pgsql' ? 'true' : 'false',
                filled($connection->getConfig('url')) ? 'true' : 'false',
                $sslmode,
                $sslmode === 'verify-full' ? 'true' : 'false',
                $rootCert !== '' ? 'true' : 'false',
                $rootCert === 'system' ? 'true' : 'false',
                $rootCertReadable ? 'true' : 'false',
                $host !== '' && filter_var($host, FILTER_VALIDATE_IP) === false ? 'true' : 'false',
                $validatorPass ? 'true' : 'false',
                $preflightExecuted ? 'true' : 'false',
                $preflightPassed ? 'true' : 'false',
            ));
        } catch (Throwable) {
            error_log('RELEASE_DIAG unavailable=true');
        }

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

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

    public function releaseDiagnostic(): JsonResponse
    {
        try {
            $connection = DB::connection();
            DB::select('select 1');

            $appEnvProduction = app()->environment('production');
            $sslmode = strtolower((string) $connection->getConfig('sslmode'));
            $configuredRootCert = $connection->getConfig('sslrootcert');
            $environmentRootCert = getenv('PGSSLROOTCERT') ?: null;
            $rootCert = is_string($configuredRootCert) && $configuredRootCert !== ''
                ? $configuredRootCert
                : (is_string($environmentRootCert) ? $environmentRootCert : '');
            $rootCertIsSystem = $rootCert === 'system';
            $rootCertPresent = $rootCert !== '';
            $homeRootCert = rtrim((string) getenv('HOME'), '/').'/.postgresql/root.crt';
            $rootCertReadable = $rootCertIsSystem
                || ($rootCertPresent && is_readable($rootCert))
                || ($homeRootCert !== '/.postgresql/root.crt' && is_readable($homeRootCert));
            $host = (string) $connection->getConfig('host');

            $preflightExecuted = false;
            $preflightPassed = false;

            if ($appEnvProduction) {
                $preflightExecuted = true;
                $preflightPassed = Artisan::call('production:preflight', [
                    '--database' => true,
                ]) === 0;
            }

            return response()->json([
                'status' => 'ok',
                'app_env_production' => $appEnvProduction,
                'database_connection_pgsql' => config('database.default') === 'pgsql',
                'database_url_present' => filled($connection->getConfig('url')),
                'effective_sslmode' => $sslmode,
                'effective_sslmode_verify_full' => $sslmode === 'verify-full',
                'sslrootcert_present' => $rootCertPresent,
                'sslrootcert_system' => $rootCertIsSystem,
                'sslrootcert_readable_or_system' => $rootCertReadable,
                'database_host_is_hostname' => $host !== '' && filter_var($host, FILTER_VALIDATE_IP) === false,
                'production_preflight_database_executed' => $preflightExecuted,
                'production_preflight_database_passed' => $preflightPassed,
            ]);
        } catch (Throwable) {
            Log::warning('Release diagnostic unavailable.', [
                'category' => 'release_diagnostic_unavailable',
            ]);

            return response()->json([
                'status' => 'unavailable',
            ], 503);
        }
    }
}

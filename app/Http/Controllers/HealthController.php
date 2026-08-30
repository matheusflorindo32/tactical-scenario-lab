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
        $isProduction = app()->environment('production');
        $sslmode = strtolower((string) config('database.connections.pgsql.sslmode'));
        $rootCert = getenv('PGSSLROOTCERT');
        $home = getenv('HOME');
        $defaultRootCert = is_string($home) && $home !== '' ? rtrim($home, '/').'/.postgresql/root.crt' : null;
        $configuredHost = (string) config('database.connections.pgsql.host');
        $validatorPassed = false;
        $preflightExecuted = false;
        $preflightPassed = false;
        $pdoClientVersion = null;

        try {
            if ($isProduction) {
                $this->productionConfiguration->assertSafe();
                $validatorPassed = true;

                $preflightExecuted = true;
                $preflightPassed = Artisan::call('production:preflight', ['--database' => true]) === 0;
            }

            $pdoClientVersion = DB::connection('pgsql')->getPdo()->getAttribute(\PDO::ATTR_CLIENT_VERSION);
        } catch (Throwable) {
            // Diagnostic intentionally records only sanitized state below.
        }

        $rootCertPresent = is_string($rootCert) && $rootCert !== '';
        $rootCertSystem = $rootCertPresent && strtolower($rootCert) === 'system';
        $rootCertReadable = $rootCertPresent && ! $rootCertSystem && is_readable($rootCert);
        $defaultRootCertReadable = $defaultRootCert !== null && is_readable($defaultRootCert);
        $hostIsHostname = $configuredHost !== '' && filter_var($configuredHost, FILTER_VALIDATE_IP) === false;

        error_log('release_diagnostic='.json_encode([
            'app_env_production' => $isProduction,
            'database_connection_pgsql' => config('database.default') === 'pgsql',
            'database_url_present' => filled(config('database.connections.pgsql.url')),
            'effective_sslmode' => $sslmode,
            'effective_sslmode_verify_full' => $sslmode === 'verify-full',
            'sslrootcert_present' => $rootCertPresent,
            'sslrootcert_system' => $rootCertSystem,
            'sslrootcert_readable' => $rootCertReadable,
            'default_sslrootcert_readable' => $defaultRootCertReadable,
            'database_host_is_hostname' => $hostIsHostname,
            'production_validator_passed' => $validatorPassed,
            'production_preflight_database_executed' => $preflightExecuted,
            'production_preflight_database_passed' => $preflightPassed,
            'pdo_client_version_present' => is_string($pdoClientVersion) && $pdoClientVersion !== '',
            'pdo_client_version' => is_string($pdoClientVersion) ? preg_replace('/[^0-9.]/', '', $pdoClientVersion) : null,
        ], JSON_THROW_ON_ERROR));

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

<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

final class PostgresRuntimeRole
{
    public static function connection(string $name = 'pgsql_runtime'): Connection
    {
        $config = config('database.connections.pgsql');
        $config['username'] = env('M6_RUNTIME_DB_USERNAME', 'tactical_runtime_test');
        $config['password'] = env('M6_RUNTIME_DB_PASSWORD', 'runtime-test-only');

        config(["database.connections.{$name}" => $config]);

        DB::purge($name);

        return DB::connection($name);
    }

    public static function activateWithinTransaction(Connection $connection): void
    {
        $connection->statement('SET LOCAL ROLE tactical_runtime_test');
    }
}

<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

final class PostgresRuntimeRole
{
    public static function connection(string $name = 'pgsql_runtime'): Connection
    {
        config(["database.connections.{$name}" => config('database.connections.pgsql')]);

        DB::purge($name);
        $connection = DB::connection($name);
        $connection->statement('SET ROLE tactical_runtime_test');

        return $connection;
    }

    public static function activateWithinTransaction(Connection $connection): void
    {
        $connection->statement('SET LOCAL ROLE tactical_runtime_test');
    }
}

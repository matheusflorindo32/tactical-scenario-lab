<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ConcurrentDatabaseOperation
{
    /**
     * @param  array<callable(): mixed>  $operations
     * @return array<int, array{barrier_reached: bool, ok: bool, value: mixed, exception: ?string, message: ?string}>
     */
    public static function run(array $operations, int $timeoutSeconds = 10): array
    {
        if (! function_exists('pcntl_fork') || ! function_exists('pcntl_waitpid')) {
            throw new RuntimeException('pcntl is required for PostgreSQL concurrency tests.');
        }

        if (count($operations) < 2) {
            throw new RuntimeException('Concurrency tests require at least two workers.');
        }

        $directory = sys_get_temp_dir().'/tactical-m6-concurrency-'.Str::uuid();

        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create concurrency test directory.');
        }

        $pids = [];
        $workerCount = count($operations);

        foreach (array_values($operations) as $index => $operation) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                self::cleanup($directory, $workerCount);
                throw new RuntimeException('Unable to fork concurrency test worker.');
            }

            if ($pid === 0) {
                self::runChild($directory, $index, $workerCount, $timeoutSeconds, $operation);
            }

            $pids[$index] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $results = [];

        foreach (array_keys($operations) as $index) {
            $path = self::resultPath($directory, $index);

            if (! is_file($path)) {
                self::cleanup($directory, $workerCount);
                throw new RuntimeException("Concurrency worker {$index} did not produce a result.");
            }

            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            $results[$index] = $decoded;
        }

        self::cleanup($directory, $workerCount);

        foreach ($results as $index => $result) {
            if (($result['barrier_reached'] ?? false) !== true) {
                throw new RuntimeException("Concurrency worker {$index} did not reach the shared barrier.");
            }
        }

        return array_values($results);
    }

    /**
     * @param  callable(): mixed  $operation
     */
    private static function runChild(
        string $directory,
        int $index,
        int $workerCount,
        int $timeoutSeconds,
        callable $operation,
    ): never {
        $result = [
            'barrier_reached' => false,
            'ok' => false,
            'value' => null,
            'exception' => null,
            'message' => null,
        ];

        try {
            DB::disconnect();
            DB::purge();
            DB::reconnect();

            file_put_contents(self::readyPath($directory, $index), 'ready', LOCK_EX);
            self::waitForBarrier($directory, $workerCount, $timeoutSeconds);
            $result['barrier_reached'] = true;
            $result['value'] = $operation();
            $result['ok'] = true;
        } catch (Throwable $exception) {
            $result['exception'] = $exception::class;
            $result['message'] = $exception->getMessage();
        } finally {
            try {
                file_put_contents(
                    self::resultPath($directory, $index),
                    json_encode($result, JSON_THROW_ON_ERROR),
                    LOCK_EX,
                );
            } finally {
                DB::disconnect();
            }
        }

        exit(0);
    }

    private static function waitForBarrier(string $directory, int $workerCount, int $timeoutSeconds): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        do {
            $ready = glob($directory.'/ready-*') ?: [];

            if (count($ready) === $workerCount) {
                return;
            }

            usleep(10_000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Concurrency barrier timed out before all workers arrived.');
    }

    private static function readyPath(string $directory, int $index): string
    {
        return $directory.'/ready-'.$index;
    }

    private static function resultPath(string $directory, int $index): string
    {
        return $directory.'/result-'.$index.'.json';
    }

    private static function cleanup(string $directory, int $workerCount): void
    {
        for ($index = 0; $index < $workerCount; $index++) {
            @unlink(self::readyPath($directory, $index));
            @unlink(self::resultPath($directory, $index));
        }

        @rmdir($directory);
    }
}

<?php

declare(strict_types=1);

/**
 * Vercel's PHP community runtime executes this file as the serverless entrypoint.
 * Laravel's writable runtime paths are redirected to /tmp because the deployed
 * function filesystem is read-only outside that scratch directory.
 */
function vercelRuntimeDefault(string $name, string $value): void
{
    $current = getenv($name);

    if ($current !== false && $current !== '') {
        return;
    }

    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

foreach (['/tmp/views'] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

vercelRuntimeDefault('APP_CONFIG_CACHE', '/tmp/config.php');
vercelRuntimeDefault('APP_EVENTS_CACHE', '/tmp/events.php');
vercelRuntimeDefault('APP_PACKAGES_CACHE', '/tmp/packages.php');
vercelRuntimeDefault('APP_ROUTES_CACHE', '/tmp/routes.php');
vercelRuntimeDefault('APP_SERVICES_CACHE', '/tmp/services.php');
vercelRuntimeDefault('VIEW_COMPILED_PATH', '/tmp/views');
vercelRuntimeDefault('LOG_CHANNEL', 'stderr');

if ((getenv('APP_URL') === false || getenv('APP_URL') === '') && getenv('VERCEL_URL')) {
    vercelRuntimeDefault('APP_URL', 'https://'.getenv('VERCEL_URL'));
}

require __DIR__.'/../public/index.php';

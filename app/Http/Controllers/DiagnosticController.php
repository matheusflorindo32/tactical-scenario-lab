<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Instrumentação temporária para diagnóstico do Gate 0.
 * REMOVER após identificação da causa raiz.
 */
final class DiagnosticController extends Controller
{
    public function proxyScheme(Request $request): JsonResponse
    {
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null;

        $url = url('/');
        $assetUrl = asset('build/manifest.json');

        return response()->json([
            'headers' => [
                'x_forwarded_proto_present' => $forwardedProto !== null,
                'x_forwarded_proto_value' => $forwardedProto,
                'x_forwarded_host_present' => $forwardedHost !== null,
                'x_forwarded_host_value' => $forwardedHost,
            ],
            'request' => [
                'scheme' => $request->getScheme(),
                'is_secure' => $request->isSecure(),
            ],
            'config' => [
                'app_url_scheme' => parse_url(config('app.url'), PHP_URL_SCHEME),
                'asset_url_scheme' => parse_url(config('app.asset_url') ?? '', PHP_URL_SCHEME) ?: null,
            ],
            'generated_urls' => [
                'url_root_scheme' => parse_url($url, PHP_URL_SCHEME),
                'asset_manifest_scheme' => parse_url($assetUrl, PHP_URL_SCHEME),
            ],
            'environment' => [
                'vercel_env' => $_ENV['VERCEL_ENV'] ?? $_SERVER['VERCEL_ENV'] ?? null,
                'vercel_url_present' => ($_ENV['VERCEL_URL'] ?? $_SERVER['VERCEL_URL'] ?? null) !== null,
            ],
        ]);
    }
}

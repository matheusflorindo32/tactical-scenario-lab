<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProxySchemeContractTest extends TestCase
{
    /**
     * @dataProvider proxyHeaderProvider
     */
    public function test_request_behind_proxy_trusts_forwarded_scheme(
        string $forwardedProto,
        string $expectedScheme,
        bool $expectedIsSecure,
    ): void {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => $forwardedProto,
            'X-Forwarded-Host' => 'preview.vercel.app',
        ])->get('/');

        $response->assertStatus(200);

        $this->assertSame(
            $expectedScheme,
            request()->getScheme(),
            "Request scheme must reflect X-Forwarded-Proto={$forwardedProto}"
        );

        $this->assertSame(
            $expectedIsSecure,
            request()->isSecure(),
            'isSecure() must be '.($expectedIsSecure ? 'true' : 'false')." for X-Forwarded-Proto={$forwardedProto}"
        );
    }

    public function test_url_generator_produces_https_when_proxy_declares_https(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'preview.vercel.app',
        ])->get('/');

        $url = url('/');
        $this->assertStringStartsWith('https://', $url, 'url(\'/\') must be HTTPS when proxy declares HTTPS');

        $assetUrl = asset('build/manifest.json');
        $this->assertStringStartsWith('https://', $assetUrl, 'asset() must be HTTPS when proxy declares HTTPS');

        $routeUrl = route('health.live');
        $this->assertStringStartsWith('https://', $routeUrl, 'route() must be HTTPS when proxy declares HTTPS');
    }

    public function test_url_generator_produces_http_when_proxy_declares_http(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'http',
            'X-Forwarded-Host' => 'preview.vercel.app',
        ])->get('/');

        $url = url('/');
        $this->assertStringStartsWith('http://', $url, 'url(\'/\') must be HTTP when proxy declares HTTP');
    }

    public function test_config_app_url_scheme_is_preserved_when_no_proxy_headers(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->get('/');

        $this->assertSame('http', request()->getScheme());
        $this->assertStringStartsWith('http://', url('/'));
    }

    public static function proxyHeaderProvider(): array
    {
        return [
            'https proxy' => ['https', 'https', true],
            'http proxy' => ['http', 'http', false],
        ];
    }
}

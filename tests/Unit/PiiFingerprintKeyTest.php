<?php

namespace Tests\Unit;

use App\Support\Normalizer;
use RuntimeException;
use Tests\TestCase;

class PiiFingerprintKeyTest extends TestCase
{
    public function test_fingerprint_remains_stable_when_app_key_changes(): void
    {
        config()->set('privacy.fingerprint_key', 'stable-pii-key');
        config()->set('app.key', 'first-app-key');

        $first = Normalizer::fingerprint('identifier', 'cpf', '123.456.789-09');

        config()->set('app.key', 'rotated-app-key');

        $second = Normalizer::fingerprint('identifier', 'cpf', '12345678909');

        $this->assertSame($first, $second);
    }

    public function test_fingerprint_changes_when_dedicated_key_changes(): void
    {
        config()->set('privacy.fingerprint_key', 'first-pii-key');
        $first = Normalizer::fingerprint('contact', 'email', 'Pessoa@Example.com');

        config()->set('privacy.fingerprint_key', 'second-pii-key');
        $second = Normalizer::fingerprint('contact', 'email', 'pessoa@example.com');

        $this->assertNotSame($first, $second);
    }

    public function test_missing_fingerprint_key_fails_closed(): void
    {
        config()->set('privacy.fingerprint_key', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PII fingerprint key is not configured.');

        Normalizer::fingerprint('identifier', 'cpf', '12345678909');
    }
}

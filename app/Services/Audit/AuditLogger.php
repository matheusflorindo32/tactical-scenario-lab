<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditLogger
{
    private const SAFE_MASK_KEYS = [
        'masked_value',
        'mask',
    ];

    private const EXACT_SENSITIVE_KEYS = [
        'cpf',
        'rg',
    ];

    private const SENSITIVE_KEY_FRAGMENTS = [
        'value',
        'email',
        'phone',
        'whatsapp',
        'password',
        'token',
        'secret',
        'document',
        'identifier',
        'contact',
    ];

    public function record(
        string $action,
        ?Model $subject = null,
        ?int $organizationId = null,
        array $payload = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();
        $user = $request->user();

        return AuditLog::create([
            'organization_id' => $organizationId,
            'actor_type' => $user ? 'user' : 'system',
            'actor_id' => $user?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'payload' => $this->sanitize($payload),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'logged_at' => now(),
        ]);
    }

    public function sanitize(array $payload): array
    {
        return collect($payload)
            ->mapWithKeys(function (mixed $value, string|int $key): array {
                $normalizedKey = Str::lower((string) $key);

                if ($this->isSensitiveKey($normalizedKey)) {
                    return [$key => '[REDACTED]'];
                }

                if (is_array($value)) {
                    return [$key => $this->sanitize($value)];
                }

                if (is_string($value)) {
                    return [$key => Str::limit($value, 500, '…')];
                }

                return [$key => $value];
            })
            ->all();
    }

    private function isSensitiveKey(string $key): bool
    {
        if (in_array($key, self::SAFE_MASK_KEYS, true)) {
            return false;
        }

        if (in_array($key, self::EXACT_SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $sensitive) {
            if ($key === $sensitive || str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}

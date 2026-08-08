<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Normalização central de documentos e contatos.
 *
 * O valor normalizado existe apenas em memória. Para pesquisa exata, o banco
 * recebe uma impressão digital HMAC; o valor normalizado nunca precisa ser
 * persistido em texto simples.
 */
final class Normalizer
{
    public static function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function cpf(?string $value): string
    {
        return substr(self::digitsOnly($value), 0, 11);
    }

    public static function phone(?string $value): string
    {
        return self::digitsOnly($value);
    }

    public static function email(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    public static function generic(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    public static function identifier(string $type, ?string $value): string
    {
        return match ($type) {
            'cpf' => self::cpf($value),
            default => self::generic($value),
        };
    }

    public static function contact(string $type, ?string $value): string
    {
        return match ($type) {
            'email' => self::email($value),
            'phone', 'whatsapp', 'emergency' => self::phone($value),
            default => self::generic($value),
        };
    }

    /**
     * Impressão digital determinística para busca exata e indicação de
     * duplicidade. O prefixo de domínio evita colisões lógicas entre tipos.
     */
    public static function fingerprint(string $domain, string $type, ?string $value): string
    {
        $normalized = $domain === 'contact'
            ? self::contact($type, $value)
            : self::identifier($type, $value);

        $key = (string) config('privacy.fingerprint_key');

        if ($key === '') {
            throw new RuntimeException('PII fingerprint key is not configured.');
        }

        return hash_hmac('sha256', "$domain:$type:$normalized", $key);
    }

    public static function maskCpf(?string $value): string
    {
        $digits = self::cpf($value);

        if (strlen($digits) !== 11) {
            return '***';
        }

        return '***.***.***-'.substr($digits, -2);
    }

    public static function maskPhone(?string $value): string
    {
        $digits = self::phone($value);

        if (strlen($digits) < 4) {
            return '***';
        }

        $ddd = strlen($digits) >= 10 ? substr($digits, -11, 2) : '**';

        return "($ddd) *****-".substr($digits, -2);
    }

    public static function maskEmail(?string $value): string
    {
        $email = self::email($value);

        if (! str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 2)).'@'.$domain;
    }

    public static function maskGeneric(?string $value): string
    {
        $clean = trim((string) $value);

        if ($clean === '') {
            return '***';
        }

        $length = mb_strlen($clean);

        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        return mb_substr($clean, 0, 1).str_repeat('*', max(1, $length - 3)).mb_substr($clean, -2);
    }
}

<?php

namespace App\Support;

/**
 * Normalizador central para valores de documentos e contatos.
 *
 * A normalização é usada para busca exata e detecção de duplicidades
 * (ver docs/EXPANSION_PLAN.md §6). Manter aqui, e não espalhado nos
 * models, evita divergência de regras entre PersonIdentifier e
 * PersonContact.
 */
final class Normalizer
{
    /**
     * Remove qualquer caractere que não seja dígito.
     * Usado para CPF, RG numérico, telefone, matrícula numérica.
     */
    public static function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    /** CPF em 11 dígitos (sem formatar). */
    public static function cpf(?string $value): string
    {
        return substr(self::digitsOnly($value), 0, 11);
    }

    /** Telefone em dígitos (mantém DDI/DDD sem formatação). */
    public static function phone(?string $value): string
    {
        return self::digitsOnly($value);
    }

    /** E-mail em lowercase, sem espaços nas pontas. */
    public static function email(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    /** Genérico: trim + lowercase. Usado para RG, matrícula alfanumérica, outros. */
    public static function generic(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    /**
     * Dispatcher por tipo de identificador. Mantém a lógica de tipo → norma
     * centralizada e testável.
     */
    public static function identifier(string $type, ?string $value): string
    {
        return match ($type) {
            'cpf'      => self::cpf($value),
            'phone'    => self::phone($value),
            'email'    => self::email($value),
            default    => self::generic($value),
        };
    }

    /** Dispatcher por tipo de contato. */
    public static function contact(string $type, ?string $value): string
    {
        return match ($type) {
            'email'                => self::email($value),
            'phone', 'emergency'   => self::phone($value),
            default                => self::generic($value),
        };
    }

    /**
     * Máscara PII para exibição em listagens: mantém apenas os últimos
     * 2 dígitos. Reveal completo só via ability `pii_reveal`.
     */
    public static function maskCpf(?string $value): string
    {
        $digits = self::cpf($value);
        if (strlen($digits) < 3) {
            return '***';
        }
        return '***.***.***-' . substr($digits, -2);
    }

    public static function maskPhone(?string $value): string
    {
        $digits = self::phone($value);
        if (strlen($digits) < 4) {
            return '***';
        }
        return '(' . substr($digits, 0, 2) . ') *****-' . substr($digits, -2);
    }
}

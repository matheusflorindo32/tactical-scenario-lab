<?php

$environment = env('APP_ENV', 'production');
$dedicatedKey = env('PII_FINGERPRINT_KEY');
$localFallback = in_array($environment, ['local', 'testing'], true)
    ? env('APP_KEY')
    : null;

return [
    /*
    |--------------------------------------------------------------------------
    | Chave de fingerprint de dados pessoais
    |--------------------------------------------------------------------------
    |
    | Use uma chave dedicada e estável para fingerprints HMAC de documentos e
    | contatos. Ela não deve ser alterada junto com APP_KEY, pois sua rotação
    | tornaria fingerprints existentes inutilizáveis para busca e duplicidade.
    |
    | Em desenvolvimento e testes, APP_KEY permanece como fallback para não
    | bloquear instalações locais. Em produção, PII_FINGERPRINT_KEY é
    | obrigatória e a ausência da chave interrompe a operação de forma segura.
    |
    */
    'fingerprint_key' => $dedicatedKey ?: $localFallback,
];

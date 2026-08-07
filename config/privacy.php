<?php

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
    | bloquear instalações locais. Em produção, defina PII_FINGERPRINT_KEY.
    |
    */
    'fingerprint_key' => env('PII_FINGERPRINT_KEY', env('APP_KEY')),
];

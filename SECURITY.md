# Política de Segurança

## Versões suportadas

Até existir uma política formal de releases versionados, a branch `main` é a linha suportada para correções de segurança.

| Linha | Suportada |
|---|---|
| `main` (HEAD) | ✅ |
| branches de feature e estados históricos não publicados | ❌ |

## Reportando uma vulnerabilidade

**Não abra issue pública.** Vulnerabilidades devem ser reportadas em canal privado:

1. Envie e-mail para **matheusdideusf@gmail.com** com o assunto `[SECURITY] tactical-scenario-lab`.
2. Descreva, quando aplicável:
   - o comportamento observado;
   - passos mínimos para reprodução;
   - impacto potencial;
   - ambiente/versão ou commit afetado;
   - sugestão de correção, se houver.

O projeto buscará confirmar o recebimento, realizar triagem e coordenar a divulgação de acordo com a gravidade e a capacidade operacional disponível. Não publique detalhes exploráveis antes de uma correção ou mitigação coordenada.

## Postura atual do produto

O Tactical Scenario Lab possui autenticação, contexto de organização ativa e autorização por capacidades. A interface ability-aware não substitui autorização no backend. Operações institucionais e dados pessoais devem permanecer isolados por organização, e os invariantes históricos de cenários publicados, timeline de execução e avaliações finalizadas devem ser preservados.

A produção usa PostgreSQL conforme `docs/PRODUCTION.md`, com separação entre identidade de migração e identidade runtime de menor privilégio, preflight de produção e probes de liveness/readiness.

## Escopo

Estão dentro do escopo desta política:

- injeção SQL, XSS, CSRF e template injection;
- falhas de autenticação, sessão ou autorização;
- escalonamento de privilégio;
- quebra de isolamento entre organizações/tenants;
- exposição de PII, segredos ou material criptográfico;
- bypass de invariantes históricos ou de imutabilidade;
- vulnerabilidades em dependências diretas ou transitivas relevantes;
- renderização insegura da Base de Conhecimento;
- falhas que permitam leitura/escrita indevida de arquivos ou paths.

Estão fora do escopo desta política, salvo quando demonstrarem uma vulnerabilidade do produto:

- ataques que dependam exclusivamente de acesso físico ao dispositivo do usuário;
- engenharia social contra mantenedores;
- indisponibilidade causada apenas por limites do provedor/infraestrutura externa sem exploração de falha da aplicação.

## Boas práticas para operadores

- Nunca commite `.env`, credenciais, certificados privados ou chaves de produção.
- Gere `APP_KEY` e `PII_FINGERPRINT_KEY` independentes e mantenha-os em secret manager/plataforma de deploy.
- Execute `php artisan production:preflight` antes de migrations/deploy.
- Use PostgreSQL em produção e mantenha a identidade runtime sem privilégios de DDL/ownership.
- Use HTTPS e `SESSION_SECURE_COOKIE=true` em produção.
- Não execute migrations automaticamente no processo web comum.
- Mantenha backup/PITR e testes periódicos de restore no provedor de PostgreSQL.
- Consulte `docs/PRODUCTION.md` para o contrato operacional completo.

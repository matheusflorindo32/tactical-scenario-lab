# Fase 2.3 — Auditoria de administração de acessos institucionais

## Status

Fase 2.3 funcionalmente implementada no branch `feature/phase-2-3-access-admin` e mantida em PR #3 draft durante esta auditoria. O M1 não adicionou funcionalidades de domínio; seu objetivo foi comprovar, documentar e fechar as garantias de governança e administração de acesso já implementadas.

O primeiro gate documental completo foi validado pelo GitHub Actions no commit `3009aa01f535b4a52996b6725cfe42d655b197fc`, workflow `tests` run **#416**, com `status=completed` e `conclusion=success`.

O commit desta atualização documental passa a ser o novo HEAD; portanto, o gate final de integração depende também de CI verde para este novo SHA.

## Base e HEAD auditados

- Base funcional da Fase 2.3: `feature/phase-2-2-auth` em `ca21dde53562cb4ef0f2794f20020113c0d1628d`.
- Branch auditada: `feature/phase-2-3-access-admin`.
- HEAD no início da auditoria formal M1: `e4a463994b92cb7a8dfc6f02cfef7a71c027ad77`.
- Gate CI intermediário validado: `3009aa01f535b4a52996b6725cfe42d655b197fc` — run #416 — success.
- PR: #3 — `WIP: Fase 2.3 Elite — administração de acessos institucionais`.

## Escopo entregue

### Administração de concessões

A administração institucional exige `access.manage` no contexto da organização ativa. O painel lista somente concessões atualmente válidas da organização ativa.

Fluxos implementados:

- conceder acesso a conta existente;
- editar abilities de concessão válida;
- revogar sem apagar histórico;
- regrant de concessão histórica compatível sem criar nova linha para o mesmo usuário/organização/papel;
- inativar e reativar contas dentro das restrições institucionais previstas.

### Abilities

O catálogo fechado `App\Support\Auth\AccessAbility` contém:

- `people.view`;
- `people.manage`;
- `scenarios.view`;
- `scenarios.manage`;
- `evaluations.manage`;
- `reports.view`;
- `access.manage`.

Valores submetidos são validados contra esse catálogo. Autenticação não substitui autorização.

### Revogação, regrant e expiração

`UserOrganizationAccess` mantém `granted_at`, `expires_at` e `revoked_at`. A validade atual considera revogação e expiração.

Garantias verificadas:

- acesso expirado deixa de compor contexto institucional válido;
- grant expirado deixa de aparecer na listagem administrativa ativa;
- grant expirado pode ser regranted pelo fluxo previsto;
- alteração/remoção de expiração é auditada;
- concessão com `access.manage` não pode expirar automaticamente.

### Proteção do último administrador

O controller impede que a organização perca seu último administrador ativo com `access.manage` quando:

- uma edição removeria `access.manage` do último grant administrativo;
- o último grant administrativo seria revogado;
- uma conta com grant administrativo seria inativada sem outro usuário administrador ativo.

Administradores inativos não contam como alternativa válida. O fluxo também impede autoinativação da própria conta administrativa.

### Contas ativas/inativas

Como o status da conta é global, um tenant local não pode inativar globalmente uma conta que mantenha grant ativo em outra organização ativa. A inativação preserva o histórico.

### Isolamento multi-institucional

O contexto institucional é resolvido por `ActiveOrganization` e revalidado no backend.

Garantias verificadas:

- `access.manage` obrigatório;
- grants de outra organização não podem ser editados/revogados;
- organização inativa não constitui contexto válido;
- grants históricos não precisam ser apagados para bloquear acesso;
- operações administrativas globais respeitam o vínculo institucional apropriado.

### Auditoria e privacidade

Eventos observados:

- `access.granted`;
- `access.updated`;
- `access.revoked`;
- `account.deactivated`;
- `account.reactivated`.

`AuditLogger` sanitiza payloads recursivamente. Chaves sensíveis exatas `cpf` e `rg`, além de fragmentos como `email`, `phone`, `whatsapp`, `password`, `token`, `secret`, `document`, `identifier`, `contact` e `value`, são redigidos. Os eventos administrativos da Fase 2.3 registram metadados operacionais, não credenciais ou PII desnecessária.

## Garantias preservadas das Fases 2.1 e 2.2

- UUID público separado do `id` interno onde aplicável;
- proteção de PII por criptografia, fingerprint e máscara;
- autenticação institucional;
- bloqueio de conta inativa;
- sessão segura e CSRF;
- contexto de organização ativa;
- ownership institucional de cenários;
- isolamento de pessoas, PII, vínculos, papéis e cenários;
- auditoria sanitizada;
- histórico preservado em vez de exclusões destrutivas.

## Cobertura de testes

### `AccessAdministrationTest`

Cobre:

- guest fora do painel administrativo;
- usuário sem `access.manage` proibido;
- listagem limitada à organização ativa;
- grant/regrant;
- edição/revogação com auditoria;
- proteção do último administrador;
- bloqueio cross-org;
- inativação/reativação de conta;
- bloqueio de alteração global de conta compartilhada;
- bloqueio de autoinativação administrativa.

### `AccessExpirationTest`

Cobre:

- validade futura;
- rejeição após expiração;
- ocultação de grant expirado;
- UI de validade;
- alteração/remoção da expiração;
- regrant de grant expirado;
- proibição de expiração automática para `access.manage`.

### `OrganizationLifecycleAuthorizationTest`

Cobre:

- organização inativa sem contexto de acesso ativo;
- inativação posterior da organização invalidando contexto sem apagar o grant histórico.

Não foi executada toolchain PHP/NPM local nesta sessão. A execução autoritativa foi feita pelo GitHub Actions.

## Validação CI

Workflow `tests`, run **#416**, commit:

`3009aa01f535b4a52996b6725cfe42d655b197fc`

Resultado:

- `Lint (Pint)`: success;
- `PHPUnit — PHP 8.4`: success;
- checkout: success;
- setup PHP 8.4: success;
- setup Node.js: success;
- dependências PHP/frontend: success;
- build Vite: success;
- environment/app key: success;
- migrations: success;
- test suite: success.

**Importante:** esta atualização documental gera um novo HEAD. O M1 só poderá receber `READY FOR INTEGRATION` após o GitHub Actions do novo SHA também concluir com sucesso.

## Limitações deliberadamente posteriores

Fora do M1/Fase 2.3:

- Scenario Core / casualties;
- `ScenarioVersion`;
- `ScenarioVictim`;
- `VictimCohort`;
- Simulation Engine;
- Assessment/Debriefing avançado;
- Wiki final Premium Elite Diamante;
- redesign visual final;
- expansões pós-1.0 definidas na especificação Institutional Edition.

## Regra de integração

- manter PR #3 draft até o gate final;
- não fazer merge automático;
- confirmar CI verde no HEAD final exato;
- confirmar ausência de alteração pertencente ao M2;
- preservar a ordem de integração das fases;
- evitar force push desnecessário;
- marcar `READY FOR INTEGRATION` somente após todos os critérios do plano M1 estarem comprovados.

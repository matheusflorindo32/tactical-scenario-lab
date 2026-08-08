# Fase 2.3 — Auditoria de administração de acessos institucionais

## Status

Fase 2.3 funcionalmente implementada no branch `feature/phase-2-3-access-admin` e mantida em PR #3 draft durante esta auditoria. O M1 não adiciona funcionalidades de domínio; seu objetivo é comprovar, documentar e fechar as garantias de governança e administração de acesso já implementadas.

**CI do HEAD final:** aguardando execução do GitHub Actions.

## Base e HEAD auditados

- Base funcional da Fase 2.3: `feature/phase-2-2-auth` em `ca21dde53562cb4ef0f2794f20020113c0d1628d`.
- Branch auditada: `feature/phase-2-3-access-admin`.
- HEAD no início da auditoria formal M1: `e4a463994b92cb7a8dfc6f02cfef7a71c027ad77`.
- PR: #3 — `WIP: Fase 2.3 Elite — administração de acessos institucionais`.

O HEAD acima pode avançar apenas por documentação/hardening do M1. O gate final deve usar o SHA exato do último commit e a execução correspondente do GitHub Actions.

## Escopo entregue

### Administração de concessões

A administração institucional exige a ability `access.manage` no contexto da organização ativa. O painel lista somente concessões atualmente válidas da organização ativa e não expõe concessões de outras organizações.

O fluxo permite:

- conceder acesso a conta existente;
- editar abilities de uma concessão válida;
- revogar sem apagar o histórico;
- regrant de concessão histórica compatível sem criar linha duplicada para o mesmo usuário/organização/papel;
- inativar e reativar contas dentro das restrições institucionais previstas.

### Abilities

O catálogo fechado em `App\Support\Auth\AccessAbility` contém:

- `people.view`;
- `people.manage`;
- `scenarios.view`;
- `scenarios.manage`;
- `evaluations.manage`;
- `reports.view`;
- `access.manage`.

Valores submetidos em concessões/edições são validados contra esse catálogo. Autenticação por si só não concede autorização administrativa.

### Revogação, regrant e expiração

`UserOrganizationAccess` mantém `granted_at`, `expires_at` e `revoked_at`. A validade atual considera revogação e prazo de expiração.

Garantias observadas:

- acesso expirado deixa de compor contexto institucional válido;
- grant expirado deixa de aparecer na listagem administrativa ativa;
- grant expirado pode ser reativado/reutilizado pelo fluxo de regrant previsto;
- alteração/remoção de expiração é auditada;
- uma concessão contendo `access.manage` não pode receber expiração automática.

A proibição de expiração automática de `access.manage` reduz o risco de uma organização ficar sem administração por vencimento silencioso.

### Proteção do último administrador

O controller impede que a organização perca seu último administrador ativo com `access.manage`.

A proteção é aplicada quando:

- uma edição removeria `access.manage` do último grant administrativo;
- o último grant administrativo seria revogado;
- uma conta com grant administrativo seria inativada sem outro usuário administrador ativo.

Administradores inativos não satisfazem a condição de administrador alternativo válido.

O fluxo também impede autoinativação da própria conta administrativa.

### Contas ativas/inativas

O status da conta é global. Por isso, um tenant local não pode inativar globalmente uma conta que ainda possua grant ativo em outra organização ativa.

A inativação preserva histórico e sessões existentes são bloqueadas pelos mecanismos de conta ativa da Fase 2.2 no próximo acesso protegido.

### Isolamento multi-institucional

O contexto institucional é resolvido por `ActiveOrganization` e revalidado no backend.

Para administração de acessos:

- `access.manage` é obrigatório;
- grants de outra organização não podem ser editados ou revogados;
- contas alvo precisam possuir concessão ativa no contexto institucional apropriado para operações globais permitidas;
- organização inativa deixa de ser um contexto institucional válido;
- grants históricos não precisam ser apagados para bloquear acesso.

### Auditoria e privacidade

Eventos administrativos observados incluem:

- `access.granted`;
- `access.updated`;
- `access.revoked`;
- `account.deactivated`;
- `account.reactivated`.

O `AuditLogger` sanitiza payloads recursivamente. Chaves sensíveis exatas como `cpf` e `rg`, e fragmentos como `email`, `phone`, `whatsapp`, `password`, `token`, `secret`, `document`, `identifier`, `contact` e `value`, são redigidos. Valores de máscara explicitamente seguros podem permanecer.

Os eventos de administração de acesso registrados nesta fase usam metadados operacionais como papel, abilities, validade, status e indicação de regrant; não copiam senha, token, documento ou e-mail do usuário para o payload de auditoria.

## Garantias preservadas das Fases 2.1 e 2.2

A Fase 2.3 preserva as fundações anteriores:

- UUID público separado do `id` interno onde aplicável;
- proteção de PII por criptografia, fingerprint e máscara;
- autenticação institucional;
- bloqueio de conta inativa;
- sessão segura e proteção CSRF;
- contexto de organização ativa;
- ownership institucional de cenários;
- isolamento de pessoas, PII, vínculos, papéis e cenários;
- auditoria sanitizada;
- histórico preservado em vez de exclusões destrutivas para simular revogação/inativação.

## Cobertura de testes

A auditoria estática confirmou cobertura específica em:

### `AccessAdministrationTest`

- guest redirecionado do painel administrativo;
- usuário sem `access.manage` proibido;
- listagem limitada à organização ativa;
- grant e regrant sem duplicação histórica indevida;
- edição de abilities e revogação com auditoria;
- proteção do último `access.manage` contra remoção e revogação;
- bloqueio de edição/revogação cross-org;
- inativação/reativação de conta exclusiva com auditoria;
- bloqueio de alteração global de conta compartilhada entre organizações;
- bloqueio de autoinativação administrativa e exigência de outro administrador ativo.

### `AccessExpirationTest`

- validade futura;
- rejeição após expiração;
- ocultação de grant expirado na administração ativa;
- UI de validade;
- alteração/remoção de expiração com auditoria;
- regrant de grant expirado sem duplicação de linha;
- proibição de expiração automática para `access.manage`.

### `OrganizationLifecycleAuthorizationTest`

- organização inativa não constitui contexto de acesso ativo;
- inativação posterior da organização invalida o contexto sem apagar/revogar o histórico do grant.

O ambiente desta sessão não executa a toolchain PHP/NPM local do repositório. Portanto, esta seção documenta cobertura existente por inspeção de código; o resultado autoritativo de execução é o GitHub Actions do HEAD final.

## Validação CI

Pipeline esperado no repositório:

- dependências PHP;
- build Vite;
- migrations em banco limpo;
- PHPUnit;
- Laravel Pint.

**Estado atual nesta revisão:** aguardando GitHub Actions do HEAD final do M1. Não considerar esta fase `READY FOR INTEGRATION` até existir `status=completed`, `conclusion=success` para o SHA final exato.

## Limitações deliberadamente posteriores

Ficam explicitamente fora da Fase 2.3/M1:

- Scenario Core / remoção do limite de casualties;
- `ScenarioVersion`;
- `ScenarioVictim`;
- `VictimCohort`;
- Simulation Engine;
- Assessment/Debriefing avançado;
- Wiki final Premium Elite Diamante;
- redesign visual final;
- expansões de IA, Evidence Core e demais módulos pós-1.0 definidos na especificação da Institutional Edition.

A ausência desses itens não é defeito da Fase 2.3; eles pertencem aos marcos posteriores do roadmap aprovado.

## Regra de integração

- manter PR #3 em draft até o gate final do M1;
- não fazer merge automático;
- confirmar CI verde no HEAD final;
- confirmar ausência de regressão e de alteração pertencente ao M2;
- integrar as fases em ordem deliberada, preservando histórico;
- após integração das fases anteriores, retargetar PRs sucessivos sem force push desnecessário;
- somente marcar `READY FOR INTEGRATION` quando todos os critérios do plano M1 estiverem comprovados.

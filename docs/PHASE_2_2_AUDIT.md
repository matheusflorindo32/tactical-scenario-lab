# Fase 2.2 — Auditoria de autenticação e acesso institucional

## Status

Fase concluída no branch `feature/phase-2-2-auth`, mantendo o PR #2 em draft e sem merge automático.

A base temporária continua sendo `feature/phase-2-1-elite` para preservar o encadeamento das fases e manter `main` intocada até a integração deliberada.

## Escopo entregue

### Autenticação

- login institucional por e-mail e senha;
- normalização do e-mail antes da autenticação;
- bloqueio de contas com `status` diferente de `active`;
- limitação de tentativas por identidade de login e IP;
- regeneração de sessão após login;
- logout com invalidação de sessão e regeneração de token CSRF;
- middleware `account.active` para encerrar sessão que permaneça aberta após inativação da conta.

### Contexto institucional

- vínculo opcional entre `users` e `people`;
- `user_organization_accesses` separado dos papéis de domínio da pessoa;
- contexto ativo armazenado como `active_organization_id` na sessão;
- seleção de organização limitada a acessos ativos e não revogados;
- revalidação do contexto institucional no backend.

### Autorização funcional

Abilities aplicadas no backend:

- `people.view`;
- `people.manage`;
- `scenarios.view`;
- `scenarios.manage`;
- `evaluations.manage`.

A simples autenticação não substitui autorização por organização e por habilidade.

### Isolamento multi-institucional

- organizações e unidades isoladas por acesso/contexto;
- pessoas, identificadores, contatos, vínculos e papéis isolados por organização;
- bloqueio de requisições forjadas com IDs/UUIDs externos;
- perfis multi-institucionais filtram relacionamentos para a organização ativa;
- cenários possuem `organization_id` e ownership institucional explícito;
- cenários legados sem proprietário não são expostos automaticamente a tenants;
- dashboard agrega somente cenários da organização ativa.

### Auditoria

Eventos de autenticação registrados:

- `auth.login.succeeded`;
- `auth.logout`.

Credenciais não são copiadas para o payload de auditoria. O sanitizador trata dados sensíveis de forma recursiva e evita falso positivo do campo curto `rg` em metadados como `organization_*`.

## Garantias preservadas do MVP

- recursos de cenário continuam independentes;
- catálogo de erros críticos permanece separado dos erros observados;
- execução duplicada continua bloqueada;
- avaliação fora de ordem continua bloqueada;
- registros históricos não são apagados para simular revogação/inativação;
- PII permanece protegida por criptografia, fingerprint e máscaras conforme a Fase 2.1.

## Validação

O GitHub Actions executa:

- instalação de dependências PHP;
- build Vite;
- migrations em banco limpo;
- suíte PHPUnit;
- Laravel Pint.

O fechamento da fase exige HEAD verde no GitHub Actions. Resultado local ou suposição não substitui essa validação.

## Itens deliberadamente fora da Fase 2.2

Os itens abaixo são evolução posterior e não são necessários para considerar o núcleo de autenticação, autorização e isolamento institucional da Fase 2.2 concluído:

- recuperação de senha por e-mail;
- verificação de e-mail;
- painel administrativo completo para criação de contas e concessões;
- workflow de regrant/revogação com interface administrativa;
- policies Laravel formais para substituir gradualmente verificações centralizadas em serviços;
- estratégia de provisionamento inicial de administradores em produção;
- classificação administrativa dos cenários legados sem `organization_id`.

## Regra de integração

- não fazer merge automático;
- manter PR #2 em draft até decisão explícita de integração;
- antes de integrar, confirmar CI verde no HEAD e ausência de review threads pendentes;
- após integração da Fase 2.1, retarget do PR #2 deve ser feito de forma deliberada, sem force push.

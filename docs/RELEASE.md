# Release Operations — Tactical Scenario Lab

Este documento define o procedimento de release do repositório. Ele complementa `docs/PRODUCTION.md`; não substitui o runbook do provedor de infraestrutura, a política de backup/PITR nem um plano organizacional de resposta a incidentes.

## 1. Release evidence

Todo release deve estar associado a evidência verificável e imutável:

- **release SHA** exato do candidato;
- pull request e diff revisados;
- execução de CI no mesmo SHA;
- jobs de Security, SQLite, PostgreSQL 16 e Pint concluídos com sucesso;
- estado das migrations conhecido;
- janela de backup/PITR confirmada antes de mudança de schema;
- merge protegido usando o SHA verificado quando o fluxo de integração permitir.

Não use apenas “último build” ou nome de branch como identidade de release.

## 2. Pre-release gate

Antes de alterar produção:

1. confirme que o candidato está sincronizado com a linha suportada;
2. confirme `composer audit --locked` e `npm audit --audit-level=high` verdes;
3. confirme build frontend, PHPUnit SQLite/PostgreSQL, Pint e contratos M6–M9 verdes;
4. confirme backup ou recovery point adequado à política da organização;
5. registre o release SHA e o estado de migrations;
6. mantenha credenciais de migration e runtime separadas.

## 3. Migration phase

A **migration identity** é uma identidade temporária/controlada com permissões suficientes para migrations. Ela não deve permanecer no processo web/queue depois da fase de deploy.

Com ambiente de produção e segredos fornecidos fora do repositório:

```bash
php artisan production:preflight
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Se `production:preflight` falhar, interrompa o release. Se uma migration falhar, não admita tráfego até entender o estado do schema e decidir entre roll-forward, schema rollback compatível ou recuperação.

## 4. Runtime phase

A **runtime identity** usa o papel PostgreSQL de menor privilégio: sem ownership de tabelas/schema, sem DDL e sem credenciais de migration.

O container de referência:

- possui `pdo_pgsql`;
- recebe o bundle frontend já construído;
- não cria SQLite para produção;
- não executa migrations no startup do processo web;
- executa a aplicação como usuário não-root `app`;
- exige que segredos e conexão PostgreSQL sejam injetados externamente.

Depois de iniciar a versão candidata, não abra tráfego imediatamente.

## 5. Traffic admission

A **traffic admission** segue duas etapas:

1. `GET /health/live` deve retornar HTTP 200 e `{"status":"ok"}`;
2. `GET /health/ready` deve retornar HTTP 200 com `{"status":"ready","database":"ok"}`.

Liveness prova que o processo responde sem depender do banco. Readiness prova configuração/database de forma coarse e secret-safe. HTTP 503 em readiness mantém o serviço fora da admissão.

Somente depois desses probes e das verificações operacionais a versão deve receber tráfego normal.

## 6. Post-deploy verification

Após admissão:

- confirme login e organização ativa;
- abra um cenário e uma execução de teste autorizada conforme ambiente;
- confirme acesso ao Knowledge Center;
- confirme `/health/live` e `/health/ready` estáveis;
- confirme que workers usam o mesmo release SHA e a runtime identity correta;
- confirme que credenciais de migration não estão presentes em runtime;
- confirme ausência de segredos/PII em logs;
- registre resultado do deploy junto ao release SHA.

## 7. Recovery decision tree

### Application rollback

**Application rollback** é voltar o código/artefato para um release anterior quando o schema atual continua compatível. Antes de fazê-lo, confirme que a versão anterior entende o schema e os dados atuais. Não execute rollback de código às cegas depois de mudanças de schema incompatíveis.

### Schema rollback

**Schema rollback** é uma decisão separada. Use `php artisan migrate:rollback` apenas depois de revisar compatibilidade, impacto em dados e guards históricos. Remover um trigger/constraint não restaura informação perdida nem autoriza fabricar histórico.

### Backup/PITR restore

Use **PITR** ou restore de backup quando o problema exige recuperação de dados, não apenas mudança de código/schema. O provedor PostgreSQL é responsável pelo mecanismo; o operador deve conhecer o recovery point, o cluster alvo e o procedimento testado.

Depois de qualquer restore, verifique migration state, isolamento entre organizações, versões publicadas, avaliações finalizadas e timeline append-only antes de reabrir tráfego.

## 8. Failed deployment before admission

Se a falha ocorrer depois de migrations mas antes de traffic admission:

1. mantenha tráfego fechado;
2. registre release SHA, migration state e erro sem segredos;
3. avalie roll-forward primeiro;
4. use application rollback apenas se schema-compatible;
5. use schema rollback somente com revisão explícita;
6. use PITR/restore quando houver necessidade de recuperação de dados;
7. rerode `production:preflight`, migrations aplicáveis, `/health/live` e `/health/ready` antes da nova admissão.

## 9. Observability boundaries

Os endpoints públicos de health permanecem deliberadamente mínimos. Diagnóstico detalhado deve ocorrer em logs protegidos e nas ferramentas do provedor de aplicação/PostgreSQL.

Este repositório não presume um fornecedor de APM, SIEM ou logging. Não aumente a resposta pública dos probes para compensar falta de observabilidade privada e nunca exponha host, usuário de banco, password, SQL, SQLSTATE, stack trace, `APP_KEY`, `PII_FINGERPRINT_KEY` ou PII.

## 10. Release closeout

O fechamento registra:

- release SHA verificado;
- CI run ID e jobs relevantes;
- merge commit, quando aplicável;
- resultado de health/readiness;
- migration state;
- confirmação de runtime identity;
- qualquer anomalia de processo e sua correção.

Uma tag/hosted release só deve ser criada quando a versão for inequívoca e a política de versionamento estiver definida. A ausência de nova tag não invalida um `main` release-ready comprovado por SHA e CI.

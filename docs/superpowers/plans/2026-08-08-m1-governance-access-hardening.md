# M1 Governance & Access Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fechar a Fase 2.3 com evidência técnica, documentação autoritativa, testes de proteção administrativa e CI verde, sem expandir o escopo funcional do Tactical Scenario Lab.

**Architecture:** O M1 preserva a arquitetura atual da Fase 2.3 e atua somente sobre hardening, cobertura de testes e documentação. A lógica central de administração permanece em `AccessAdministrationController`, autorização institucional em `ActiveOrganization`/abilities e evidência de comportamento em testes Feature. Nenhuma alteração de casualties, Scenario Core ou Simulation Engine entra neste plano.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, Tailwind/Vite, PHPUnit, SQLite em testes, GitHub Actions.

## Global Constraints

- Trabalhar somente em `feature/phase-2-3-access-admin`.
- Não alterar `main`.
- Não fazer merge durante a execução deste plano.
- Não iniciar M2/Fase 2.4.
- Não alterar regras de casualties.
- Não fazer force push.
- Não remover branches de backup.
- Nenhuma nova feature fora do escopo de governance/access hardening.
- Toda mudança funcional deve possuir teste de regressão correspondente.
- O GitHub Actions é a fonte autoritativa de CI quando o ambiente local não puder executar PHP/NPM.

---

## File map

### Código existente a revisar
- `app/Http/Controllers/AccessAdministrationController.php` — concessão, atualização, revogação, inativação/reativação e proteção de último administrador.
- `app/Services/Auth/ActiveOrganization.php` — resolução do contexto institucional e enforcement de abilities.
- `app/Services/Audit/AuditLogger.php` — registro e sanitização de eventos de auditoria.
- `app/Models/UserOrganizationAccess.php` — validade, revogação e expiração de grants.
- `app/Support/Auth/AccessAbility.php` — catálogo fechado de abilities.
- `routes/web.php` — rotas administrativas e middleware.

### Testes existentes a revisar/estender
- `tests/Feature/AccessAdministrationTest.php`
- `tests/Feature/AccessExpirationTest.php`
- `tests/Feature/OrganizationLifecycleAuthorizationTest.php`
- `tests/Feature/OrganizationManagementFlowTest.php`

### Documentação
- Create: `docs/PHASE_2_3_AUDIT.md`
- Create or update only if already present remotely: `docs/REPO_AUDIT_2026-08-07.md`
- Existing reference: `docs/PHASE_2_2_AUDIT.md`

---

### Task 1: Baseline autoritativo do M1

**Files:**
- Read: `app/Http/Controllers/AccessAdministrationController.php`
- Read: `app/Services/Auth/ActiveOrganization.php`
- Read: `app/Services/Audit/AuditLogger.php`
- Read: `app/Models/UserOrganizationAccess.php`
- Read: `app/Support/Auth/AccessAbility.php`
- Read: `routes/web.php`
- Read: `tests/Feature/AccessAdministrationTest.php`
- Read: `tests/Feature/AccessExpirationTest.php`
- Read: `tests/Feature/OrganizationLifecycleAuthorizationTest.php`

**Interfaces:**
- Consumes: comportamento atual do HEAD da Fase 2.3.
- Produces: matriz factual de garantias existentes e lacunas reais antes de qualquer alteração.

- [ ] **Step 1: Confirmar HEAD e escopo**

Registrar SHA atual de `feature/phase-2-3-access-admin`, PR #3 e lista de arquivos alterados.

Expected: nenhuma alteração funcional feita nesta etapa.

- [ ] **Step 2: Mapear garantias existentes**

Confirmar por código e testes, sem inferência:

```text
access.manage obrigatório
cross-org bloqueado
revoke preserva histórico
regrant reutiliza grant histórico quando aplicável
access.manage não pode expirar automaticamente
último administrador não pode perder access.manage
último administrador não pode ser revogado
conta administrativa não pode se autoinativar
conta global compartilhada entre organizações não pode ser inativada por tenant local
organização inativa bloqueia operação institucional
auditoria não copia credenciais/PII desnecessária
```

- [ ] **Step 3: Classificar cada garantia**

Usar exatamente:

```text
IMPLEMENTADO E TESTADO
IMPLEMENTADO SEM TESTE SUFICIENTE
PARCIAL
AUSENTE
```

- [ ] **Step 4: Não corrigir ainda**

Se houver lacuna, registrar arquivo, método e comportamento exato. Não modificar código antes da Task 2.

---

### Task 2: Fechar gaps de segurança com TDD

**Files:**
- Modify only if a gap is confirmed: `app/Http/Controllers/AccessAdministrationController.php`
- Modify only if a gap is confirmed: `app/Services/Auth/ActiveOrganization.php`
- Modify only if a gap is confirmed: `app/Services/Audit/AuditLogger.php`
- Test: `tests/Feature/AccessAdministrationTest.php`
- Test: `tests/Feature/AccessExpirationTest.php`
- Test: `tests/Feature/OrganizationLifecycleAuthorizationTest.php`

**Interfaces:**
- Consumes: gaps factuais da Task 1.
- Produces: regressões automatizadas para cada lacuna e correção mínima correspondente.

- [ ] **Step 1: Para cada gap, escrever primeiro o teste que falha**

Exemplo somente se a proteção correspondente estiver ausente:

```php
public function test_last_active_access_manager_cannot_be_revoked(): void
{
    // arrange organization + only active admin
    // act revoke
    // assert validation error and revoked_at remains null
}
```

Não duplicar testes que já cobrem o comportamento.

- [ ] **Step 2: Executar o teste específico**

Run local quando disponível:

```bash
php artisan test tests/Feature/AccessAdministrationTest.php
```

Expected before fix: FAIL apenas no caso novo que reproduz a lacuna.

Se PHP não estiver disponível, registrar `NOT RUN LOCALLY` e prosseguir somente com mudança pequena e revisão estática; a validação final será via GitHub Actions.

- [ ] **Step 3: Implementar a menor correção possível**

Regras:

```text
não refatorar unrelated code
não mover responsabilidades sem necessidade
não criar novo sistema de roles
não introduzir policy formal apenas por estética se a segurança já estiver corretamente centralizada
```

- [ ] **Step 4: Executar teste específico novamente**

```bash
php artisan test tests/Feature/AccessAdministrationTest.php
```

Expected: PASS.

- [ ] **Step 5: Executar suíte M1**

```bash
php artisan test \
  tests/Feature/AccessAdministrationTest.php \
  tests/Feature/AccessExpirationTest.php \
  tests/Feature/OrganizationLifecycleAuthorizationTest.php \
  tests/Feature/OrganizationManagementFlowTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit pequeno apenas se houve código/teste funcional novo**

```bash
git add app/ tests/
git commit -m "fix: harden institutional access administration"
```

Se nenhuma lacuna funcional existir, não criar commit vazio.

---

### Task 3: Auditoria formal da Fase 2.3

**Files:**
- Create: `docs/PHASE_2_3_AUDIT.md`
- Reference: `docs/PHASE_2_2_AUDIT.md`
- Reference: PR #3 changed files and final HEAD.

**Interfaces:**
- Consumes: código/testes validados nas Tasks 1–2.
- Produces: documento autoritativo para fechamento da Fase 2.3.

- [ ] **Step 1: Criar `docs/PHASE_2_3_AUDIT.md`**

O documento deve conter exatamente estas seções:

```markdown
# Fase 2.3 — Auditoria de administração de acessos institucionais

## Status
## Base e HEAD auditados
## Escopo entregue
### Administração de concessões
### Abilities
### Revogação, regrant e expiração
### Proteção do último administrador
### Contas ativas/inativas
### Isolamento multi-institucional
### Auditoria e privacidade
## Garantias preservadas das Fases 2.1 e 2.2
## Cobertura de testes
## Validação CI
## Limitações deliberadamente posteriores
## Regra de integração
```

- [ ] **Step 2: Documentar somente fatos observados**

Não escrever `CI verde` antes de existir uma execução verde para o HEAD final.

Enquanto CI não rodar, usar:

```text
CI do HEAD final: aguardando execução do GitHub Actions.
```

- [ ] **Step 3: Declarar itens fora do M1**

Incluir explicitamente:

```text
Scenario Core / casualties
ScenarioVersion
ScenarioVictim
VictimCohort
Simulation Engine
Wiki final
redesign visual final
```

- [ ] **Step 4: Commit documental**

```bash
git add docs/PHASE_2_3_AUDIT.md
git commit -m "docs: add phase 2.3 access administration audit"
```

---

### Task 4: Reconciliação documental do repositório

**Files:**
- Create or Modify: `docs/REPO_AUDIT_2026-08-07.md`

**Interfaces:**
- Consumes: topologia Git remota já reconciliada.
- Produces: registro histórico que não repete a conclusão errada do origin desatualizado.

- [ ] **Step 1: Verificar se o arquivo já existe no HEAD remoto**

Se não existir, criar. Se existir, preservar conteúdo histórico e acrescentar seção de correção.

- [ ] **Step 2: Registrar a correção factual**

A seção deve declarar:

```text
A auditoria inicial foi executada contra um origin local desatualizado.
Após git fetch --all --prune, foram recuperadas as branches feature/phase-2-1-elite, feature/phase-2-2-auth, feature/phase-2-3-access-admin e feature/phase-2-4-unlimited-casualties, além dos backups intermediários.
As conclusões anteriores de inexistência dessas branches e provável inexistência de PRs foram invalidadas.
```

- [ ] **Step 3: Registrar topologia final**

Usar:

```text
main
└── feature/phase-2-1-elite
    └── feature/phase-2-2-auth
        └── feature/phase-2-3-access-admin
            └── feature/phase-2-4-unlimited-casualties (inicialmente espelho da 2.3)
```

Registrar backups como históricos, não como bases de desenvolvimento ativas.

- [ ] **Step 4: Commit documental**

```bash
git add docs/REPO_AUDIT_2026-08-07.md
git commit -m "docs: reconcile repository audit with remote state"
```

---

### Task 5: Gate técnico e CI do M1

**Files:**
- Verify: `.github/workflows/tests.yml`
- Verify: todos os arquivos tocados nas Tasks 2–4.

**Interfaces:**
- Consumes: HEAD final do M1.
- Produces: decisão objetiva `READY FOR INTEGRATION` ou `BLOCKED`.

- [ ] **Step 1: Executar validação local completa quando disponível**

```bash
composer install --no-interaction --prefer-dist
npm ci
npm run build
php artisan migrate:fresh --force
php artisan test
vendor/bin/pint --test
```

Expected: todos PASS.

Se o ambiente não tiver toolchain, não inventar resultado.

- [ ] **Step 2: Push da branch da Fase 2.3**

```bash
git push origin feature/phase-2-3-access-admin
```

- [ ] **Step 3: Aguardar/verificar GitHub Actions do HEAD final**

Critério:

```text
status = completed
conclusion = success
commit_sha = HEAD final exato da Fase 2.3
```

- [ ] **Step 4: Atualizar `docs/PHASE_2_3_AUDIT.md` com evidência do CI**

Substituir estado pendente por execução real e SHA real.

- [ ] **Step 5: Commit final de fechamento documental**

```bash
git add docs/PHASE_2_3_AUDIT.md
git commit -m "docs: finalize phase 2.3 audit after green CI"
git push origin feature/phase-2-3-access-admin
```

Se esse commit disparar novo CI, o gate final usa o novo HEAD, não a execução anterior.

- [ ] **Step 6: Gate de conclusão M1**

Marcar `READY FOR INTEGRATION` somente se:

```text
PHASE_2_3_AUDIT presente
REPO_AUDIT reconciliado
testes de último administrador presentes
testes cross-org presentes
revoke/regrant/expiry cobertos
conta e organização inativas cobertas
auditoria sem credenciais/PII desnecessária
CI verde no HEAD final
nenhuma alteração de M2 misturada
```

- [ ] **Step 7: Parar antes do merge**

Não integrar PR #1, #2 ou #3 neste plano. Entregar relatório final com:

```text
HEAD final
commits criados
arquivos alterados
testes executados
CI run e conclusão
M1 = X%
READY FOR INTEGRATION ou BLOCKED
bloqueios restantes, se houver
```

---

## Self-review do plano

- Cobertura da especificação M1: completa.
- Nenhum placeholder `TBD/TODO` presente.
- Nenhuma tarefa inicia casualties/M2.
- Segurança funcional só muda se um gap for reproduzido por teste.
- Documentação não pode declarar CI verde sem evidência do HEAD final.
- Merge foi deliberadamente excluído do plano e permanece como gate humano posterior.

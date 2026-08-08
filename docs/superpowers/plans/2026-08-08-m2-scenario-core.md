# M2 Scenario Core Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Evoluir o núcleo de cenários para escala sem limite artificial, versionamento explícito e representação híbrida de vítimas individuais e cohorts, preservando compatibilidade e isolamento institucional.

**Architecture:** `Scenario` permanece como identidade institucional do caso. `ScenarioVersion` passa a guardar a definição versionada do treinamento e `estimated_casualty_count`; `ScenarioVictim` representa indivíduos relevantes; `VictimCohort` representa grupos agregados. A Fase 2.4 não separa ainda execução/avaliação do `Scenario`: isso pertence a M3/M4.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent, Blade/Alpine/Tailwind, SQLite CI, PHPUnit, GitHub Actions.

## Global Constraints
- Trabalhar somente em `feature/phase-2-4-unlimited-casualties`.
- Não alterar `main` durante a implementação.
- TDD obrigatório para mudança de comportamento.
- Não iniciar Simulation Engine, timeline, teams, injects ou Assessment avançado.
- Preservar tenant isolation e UUID público.
- Migrações devem preservar cenários existentes.
- Nenhuma estimativa de vítimas pode criar automaticamente N registros individuais.
- CI do HEAD final é o gate autoritativo.

---

### Task 1: Escala de vítimas sem limite artificial
**Files:**
- Test: `tests/Feature/ScenarioCasualtyScaleTest.php`
- Modify: `app/Http/Controllers/ScenarioController.php`
- Modify: `app/Services/ScenarioGenerator.php`
- Modify: `resources/views/scenarios/create.blade.php`
- Create: migration incremental para ampliar `scenarios.casualties` e adicionar `estimated_casualty_count`
- Modify: `app/Models/Scenario.php`

**Acceptance:** 1, 11, 100 e 1000 aceitos; 0 e negativos rejeitados; nenhuma regra `max:10`; campo persistido em `estimated_casualty_count`; compatibilidade `casualties` preservada.

### Task 2: ScenarioVersion
**Files:**
- Create: `app/Models/ScenarioVersion.php`
- Create: migration `scenario_versions`
- Modify: `app/Models/Scenario.php`
- Modify: `app/Http/Controllers/ScenarioController.php`
- Modify: `app/Services/ScenarioGenerator.php`
- Test: `tests/Feature/ScenarioVersioningTest.php`

**Acceptance:** novo cenário cria versão 1; dados antigos são backfilled para versão 1; versões possuem UUID; versão aponta ao cenário/organização por meio do cenário; 1000 vítimas estimadas ficam na versão sem expansão de linhas.

### Task 3: ScenarioVictim e VictimCohort
**Files:**
- Create: `app/Models/ScenarioVictim.php`
- Create: `app/Models/VictimCohort.php`
- Create: migrations correspondentes
- Modify: `app/Models/ScenarioVersion.php`
- Test: `tests/Feature/ScenarioVictimModelTest.php`

**Acceptance:** vítima individual e cohort pertencem a uma versão; ambos possuem UUID; quantity do cohort >=1; 1000 estimadas podem coexistir com poucas vítimas individuais/cohorts sem criação automática em massa.

### Task 4: UX de estimativa e representação
**Files:**
- Modify: `resources/views/scenarios/create.blade.php`
- Modify: `resources/views/scenarios/show.blade.php`
- Test: `tests/Feature/ScenarioCasualtyScaleTest.php`

**Acceptance:** UI usa linguagem “Estimativa total de vítimas”; não contém “Uma a dez”, `max="10"` ou botão travado em 10; show diferencia estimativa total de representações detalhadas.

### Task 5: Auditoria e gate
**Files:**
- Create: `docs/PHASE_2_4_AUDIT.md`

**Acceptance:** suíte PHPUnit, build e Pint verdes no HEAD final; PR permanece draft até revisão; documentação registra limites deliberados e prepara M3.

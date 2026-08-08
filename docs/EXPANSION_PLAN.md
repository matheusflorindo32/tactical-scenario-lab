# Plano de expansão modular — Tactical Scenario Lab → Tactical Medicine Academy

Versão do documento: **0.2.0 (proposta revisada)** · Data: **2026-08-06** · Status: **aguardando aprovação** · Sucessor de: **0.1.0** (mesmo arquivo)

Este documento **não altera código**. É a proposta arquitetural revisada. Nenhuma migration, controller, model, view ou rota nova é criada até aprovação explícita.

---

## Changelog 0.1.0 → 0.2.0

| # | Decisão nova | Impacto |
|---|---|---|
| C1 | `people.category` **NÃO é mais um campo fixo** — papel é sempre contextual (`person_roles`, `enrollments`, `execution_participants`, `organization_memberships`). | Uma pessoa pode ser instrutor numa turma e aluno em outra sem cadastro duplicado. |
| C2 | Separar **`person_identifiers`** (documentos: CPF, RG, matrícula, passaporte, temp_code, registro profissional) de **`person_contacts`** (canais: e-mail, telefone, contato de emergência). | Documento não é contato. Máscara e retenção diferem. |
| C3 | Introduzir **`users`** com `person_id` nullable + unique. Nem toda pessoa terá login. Login e-mail ≠ e-mail pessoal. | Alunos, figurantes, vítimas, apoio podem existir sem conta. |
| C4 | Adotar **pessoa global** com vínculos institucionais (não pessoa por instituição), com escopo rígido de leitura por `organization_id` e mesclagem sempre supervisionada. | Justificado na §7 (unicidade, privacidade, riscos). |
| C5 | Separar **`scenario_victims` (planejadas)** de **`execution_victim_states` (observadas)**. Nunca sobrescrever o template durante execução. | Cenário pode ser reexecutado n vezes sem corromper o design original. |
| C6 | Separar **`scenario_victims.actor_person_id` (nullable)** da ficha clínica fictícia. Dados clínicos nunca gravam na pessoa real. | Ator interpretando vítima não fica com "hemorragia" no cadastro. |
| C7 | Novo campo **`scenarios.complexity`** (`simples|intermediario|avancado|tatico|desastre|pediatrico|clinico|trauma|remoto|multiplas_vitimas`). Controla quais campos clínicos aparecem na ficha. | Cenário simples continua simples; instrutor não é sufocado. |
| C8 | **SCI é opcional** — `scenarios.sci_enabled` bool, padrão `false`. Módulo só carrega quando ligado. | Cenário de sala de aula não pede comandante nem operações. |
| C9 | Retenção deixou de ter "5 anos" fixo. **Retenção configurável** por organização, conforme finalidade, base legal, contrato, política institucional e orientação jurídica. Anonimização automática **não implementar até aprovação jurídica**. | Alinha à LGPD real. |
| C10 | `scenarios.casualties` **permanece temporariamente** para compatibilidade. `scenario_victims` vira fonte oficial em Fase 3; `casualties` pode ser sincronizado como `count()`; remoção só em fase futura planejada. | Não quebra MVP nem os 23 testes atuais. |
| C11 | **Fase 2 subdividida em 4 sub-fases** (2.1 pessoas/organizações → 2.2 auth/governança → 2.3 educação → 2.4 administração). | Entregas menores, feedback mais rápido. |
| C12 | Curadoria visual obrigatória (**21st.dev prioritário → shadcn/ui → design system TMA → biblioteca externa licenciada → componente próprio**) documentada em `docs/UI_COMPONENT_RESEARCH.md`. Nenhuma UI nova sem registro de fonte + licença. | Reduz "template genérico"; auditabilidade da origem. |

---

## Sumário executivo (revisado)

O MVP entrega um artefato — **Scenario** — com ciclo `draft → running → completed`, catálogo de erros determinístico, avaliação com score e debrief. Já há **23 testes/83 assertions**, design system consolidado e wizard versionado.

A expansão decompõe em **~40 tabelas relacionadas** organizadas em 8 fases. Espinha dorsal `people` + `person_identifiers` + `person_contacts` + `users` (nullable-linked). Papel é sempre contextual, nunca fixo. Cenário compõe-se de `scenario_victims` planejadas + `execution_victim_states` observadas — planejamento nunca é sobrescrito por execução.

**Regra invariante**: nada que é opcional (CPF, RG, matrícula, e-mail, telefone) vira obrigatório sem configuração institucional. **Nada bloqueia por documento faltando**. Cadastro incompleto é status legítimo, com badge de pendências.

---

## 1. Inventário da arquitetura atual (real)

### 1.1 Domínio
| Camada | Elemento | Estado |
|---|---|---|
| Model | `App\Models\Scenario` | 15 fillable, 4 casts de data/array. Guards `isDraft/Running/Completed/canBeStarted/canBeEvaluated`. |
| Model | `App\Models\User` | Padrão Laravel, **ainda não usado** — MVP sem auth. |
| Service | `App\Services\ScenarioGenerator` | Determinístico. 5 inputs → título/objetivos/ações/catálogo. |
| Controller | `App\Http\Controllers\ScenarioController` | `index/create/store/show/execute/evaluate`. Guards e `Rule::in($catalog)` no evaluate. |
| Views | `resources/views/scenarios/*`, `dashboard`, `welcome`, `errors/*` | Padrão misto (welcome usa `@extends('layouts.public')`; dashboard/scenarios usam `<x-layouts.app>`). |
| DB | `scenarios` (SQLite) | 16 colunas — inclui `observed_critical_errors`, `started_at`, `completed_at`. |
| Testes | `tests/Feature/ScenarioFlowTest.php` | 20 casos cobrindo landing, dashboard, listagem, criação, recursos independentes, ciclo de vida, catálogo vs observados, persistência. |

### 1.2 Limitações estruturais reafirmadas
`scenarios.casualties` = inteiro; `scenarios.resources` = array de strings livres; sem `people`, `organizations`, `units`, `courses`, `classes`; sem RBAC; sem auditoria; sem soft delete; JSON em SQLite (funcional em dev, exige Postgres em produção).

---

## 2. Riscos (revisados)

| # | Risco | Impacto | Mitigação |
|---|---|---|---|
| R1 | Inflar `scenarios` com 40+ colunas | Tabela ilegível, migrations frágeis | Decomposição em ~40 tabelas; `scenarios` fica com ≤ 20 colunas. |
| R2 | CPF virar obrigatório/chave | Bloqueia estrangeiros, cadastros rápidos, exercícios | `person_identifiers` normalizado; CPF é *um* entre muitos; nunca chave. |
| R3 | Duplicidade com cadastro simplificado | Instrutor "João" 5× | Detecção fuzzy no salvamento + alerta + `usar existente`/`criar novo`; nunca merge automático. |
| R4 | Vazamento de PII em logs/URL/listagens | Falha LGPD | Máscara `***.***.***-XX`, UUID em URL, `audit_logs` escopo. |
| R5 | Migração destruir MVP | Perda de dados/rascunhos | Migrations incrementais reversíveis; sem `migrate:fresh`. |
| R6 | RBAC explodir em complexidade | 3 meses gastos em auth | Enums + Policies (Laravel nativo); ABAC só quando surgir 2º tenant real. |
| R7 | SCI virar obrigatório em cenário simples | Instrutor abandona | `sci_enabled` bool por cenário; default false. |
| R8 | IA inventar referências | Perda de credibilidade | `evidence_sources.verified_at` só por humano com role auditor/curador. |
| R9 (novo) | Componente 21st.dev/shadcn importado em React sem adaptação | Quebra Blade+Alpine; tokens divergentes | `UI_COMPONENT_RESEARCH.md` documenta fonte, licença, adaptação; nunca copiar código React direto. |
| R10 (novo) | Pessoa global expor dados entre organizações | Vazamento cross-tenant | Escopo obrigatório por `organization_id`; busca global só admin_tma com auditoria. |
| R11 (novo) | Retenção fixa violar contrato | Não conformidade com política institucional | Retenção **configurável** por org; anonimização automática só após aprovação jurídica. |
| R12 (novo) | Ator "encarnado" na ficha clínica da vítima | Dados fictícios contaminam pessoa real | `scenario_victims.actor_person_id` separa ator; clínica fica em `victim_profiles`. |

---

## 3. Decisões revisadas (detalhamento)

### 3.1 `users` vs `people` (C3)

```
users
├── id (PK)
├── person_id (nullable, unique)   ← nem toda pessoa loga; nem todo login tem pessoa "operacional"
├── login_email                    ← pode ser diferente do e-mail pessoal em person_contacts
├── password
├── status                         ← active | suspended | invited
├── last_login_at
└── timestamps

people
├── id (PK, BIGINT interno)
├── uuid                           ← usado em URLs
├── display_name (obrigatório)
├── social_name (opcional)
├── birth_date (opcional)
├── photo_path (opcional)
├── status                         ← active | incomplete | inactive | merged
├── merged_into (nullable, FK people.id)
├── created_by (FK users.id, nullable)   ← quem cadastrou (pode ser sistema em bulk import)
├── notes
└── timestamps

person_identifiers   ← documentos
person_contacts      ← canais de comunicação
person_roles         ← papéis por organização (histórico)
organization_memberships   ← vínculo pessoa ↔ organização (com período)
```

**Casos legítimos de pessoa sem `users.id`:** aluno único, figurante, vítima simulada, ator, apoio, observador, convidado externo.

### 3.2 Documentos vs contatos (C2)

```
person_identifiers
├── id, person_id, type            ← cpf | rg | id_funcional | matricula | passaporte
│                                     registro_profissional | temp_code | qr | outro
├── value, value_normalized        ← só dígitos para CPF/telefone; lowercase para outros
├── issuer                         ← SSP-SP, PMESP, CFM (opcional)
├── country, state (opcional)
├── is_primary                     ← um por pessoa
├── verified_at (opcional)
└── expires_at (condicional)       ← passaporte, registro profissional

person_contacts
├── id, person_id, type            ← email | phone | emergency | other
├── value, value_normalized        ← E.164, lowercase
├── label                          ← "trabalho", "pessoal", "esposa"
├── is_primary
└── verified_at
```

**QR Code**: gerado a partir de `person_identifiers` do tipo `qr`, cujo `value` é UUID opaco — **nunca CPF nem RG codificados**. Escaneamento resolve `uuid → person_id` server-side com escopo institucional.

### 3.3 Papéis contextuais (C1)

Uma pessoa não tem categoria fixa. Papéis vivem em contexto:

- `organization_memberships` — vínculo com instituição (com período, status, cargo).
- `person_roles` — permissões da pessoa naquela organização.
- `enrollments` — participação numa turma como aluno OU instrutor OU avaliador OU apoio.
- `execution_participants` — papel numa execução específica (aluno, instrutor, controlador, ator, observador, comando).

O **cadastro rápido pode sugerir um papel inicial** (aluno/instrutor) para acelerar o vínculo com uma turma, mas não trava o registro.

### 3.4 Pessoa global vs institucional (C4)

**Escolha:** pessoa global com vínculos institucionais.

Justificativa e mitigações:

| Dimensão | Global (escolhido) | Isolada por instituição |
|---|---|---|
| Unicidade | Uma linha em `people` para "Maria Silva" independentemente da org | Duplicada N vezes; dificulta relatório TMA-wide |
| Privacidade | Escopo obrigatório por `organization_id` em toda query. Busca global só `admin_tma` com auditoria. Uma organização **não** vê pessoas de outra por default. | Naturalmente isolado, mas relatórios cross-org exigem ETL manual |
| Deduplicação | Detecção no salvamento + mesclagem supervisionada | Sem deduplicação nativa; N cadastros permanecem |
| Transferência entre instituições | Simples: adicionar `organization_membership` | Complexa: importar/exportar |
| Histórico | Único e coerente | Fragmentado |
| LGPD | Precisa políticas fortes: máscara, escopo, auditoria, base legal por finalidade | Naturalmente atendida por isolamento, mas com custo de UX |
| Risco central | Vazamento cross-tenant | Duplicidade e inconsistência |

**Mitigações obrigatórias no modelo global:**
- Middleware `EnsureOrganizationScope` injeta `WHERE organization_id = auth()->user()->currentOrgId()` em queries de `PersonRepository` — falha aberta é bug crítico.
- Máscara de PII em toda serialização exceto rota `pii_reveal` autenticada + auditada.
- `person_identifiers` tem índice `(type, value_normalized, organization_id)` — mesmo CPF em orgs diferentes = dois registros com mesma pessoa apenas se membership em ambas.
- Transferência entre orgs = nova `organization_membership`, não novo `people`.
- Mesclagem sempre supervisionada, transacional, com auditoria antes/depois.

### 3.5 Ator vs vítima fictícia (C6)

```
scenario_victims
├── id, scenario_id
├── code (V1, V2)
├── fictional_name           ← "Vítima Alfa", "João Silva 32 anos"
├── kind                     ← ator | manequim | card | simulador
├── actor_person_id (nullable, FK people.id)   ← só quando kind=ator
├── initial_priority (opcional)
└── ...

victim_profiles              ← ficha clínica FICTÍCIA vinculada a scenario_victims
victim_injuries
victim_baseline_vitals
victim_evolution_rules
```

**Regra invariante:** `victim_profiles.person_id` **NÃO EXISTE**. A ficha clínica fictícia pertence a `scenario_victims.id`, nunca a `people.id`. O ator real é referenciado apenas para participação (histórico de "quem interpretou").

### 3.6 Planejamento vs execução (C5)

```
scenarios / scenario_victims           ← DESIGN, imutável durante execução
executions                             ← instância; um cenário pode ser executado N vezes
execution_victim_states                ← estado observado por vítima naquela execução
execution_vital_signs                  ← sinais registrados
execution_interventions                ← intervenções realizadas
execution_events                       ← timeline
```

Reexecução do mesmo cenário cria nova `executions.id` sem tocar em `scenario_victims`.

---

## 4. Diagrama de relacionamentos (revisado)

```
organizations 1─N units
organizations 1─N organization_memberships N─1 people
organizations 1─N person_roles N─1 people

users 0..1─1 people                        (users.person_id nullable + unique)

people 1─N person_identifiers              (documentos)
people 1─N person_contacts                 (canais)

courses 1─N course_versions
courses 1─N classes                        (via course_version_id)
classes 1─N enrollments N─1 people
classes 1─N teams
teams   1─N team_members N─1 enrollments

classes   1─N scenarios                    (class_id nullable — cenário pode existir solto)
scenarios 1─N scenario_victims
scenarios N─N protocol_versions (via scenario_protocols)
scenarios 1─N executions

executions 1─N execution_participants N─1 people
executions 1─N execution_teams
executions 1─N execution_events
executions 1─N execution_victim_states N─1 scenario_victims
executions 1─N execution_vital_signs
executions 1─N execution_interventions
executions 1─N triage_records
executions 0..1─1 incident_commands        (só se scenarios.sci_enabled)
executions 1─N assessments N─1 people
executions 1─1 debriefings
executions 1─N action_plans

scenario_victims 1─1 victim_profiles
victim_profiles  1─N victim_injuries
victim_profiles  1─N victim_baseline_vitals
victim_profiles  1─N victim_evolution_rules
victim_templates 1─N victim_profiles       (opcional; clonagem)

kits N─1 organizations
kits 0..1─1 people                          (kit individual)
kits 1─N kit_items N─1 equipment_catalog

inventories 1─N inventory_movements

protocols 1─N protocol_versions
protocol_versions N─N evidence_sources     (via protocol_evidences)
scenarios         N─N evidence_sources     (via scenario_evidences)

attachments polymorphic → people/scenarios/protocols/kits/evidence
audit_logs polymorphic → toda operação sensível, escopo por org
```

---

## 5. Matriz de obrigatoriedade (expandida)

Formato: `Campo | Entidade | Classificação | Obrigatório? | Condição | Pendente? | Impacto se ausente`

### 5.1 `people`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| display_name | mínimo | sim | — | não | não existe registro sem nome/apelido |
| uuid | mínimo | sim | gerado | não | URL/QR |
| status | mínimo | sim | default `incomplete` | não | filtro global |
| social_name | opcional | não | — | sim | display cai para name |
| birth_date | opcional | não | — | sim | idade indeterminada |
| photo_path | opcional | não | — | sim | avatar padrão |
| notes | opcional | não | — | sim | — |
| merged_into | condicional | condicional | quando status=merged | — | integridade |

### 5.2 `person_identifiers`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| type | mínimo | sim | enum | não | integridade |
| value | mínimo | sim | — | não | integridade |
| value_normalized | mínimo | sim | derivado | não | busca |
| issuer | opcional | não | — | sim | — |
| is_primary | opcional | não | um `true` por pessoa | sim | display |
| verified_at | opcional | não | — | sim | badge "não verificado" |
| expires_at | condicional | sim se passaporte/registro | — | — | alerta |

### 5.3 `person_contacts`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| type | mínimo | sim | email/phone/emergency/other | não | — |
| value / value_normalized | mínimo | sim | — | não | — |
| label | opcional | não | — | sim | — |
| verified_at | opcional | não | — | sim | — |

### 5.4 `users`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| login_email | mínimo | sim | único | não | login |
| password | mínimo | sim | hash | não | login |
| person_id | opcional | não | se pessoa operacional existir | — | vínculo |
| status | mínimo | sim | active/suspended/invited | não | login |

### 5.5 `scenarios` (ajustes na Fase 3)
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| environment | mínimo | sim | — | não | contexto |
| threat_level | mínimo | sim | enum | não | ações esperadas |
| mechanism | mínimo | sim | — | não | catálogo de erros |
| complexity | mínimo | sim | default `simples` | não | controla campos |
| organization_id | opcional (2.1) → mínimo (2.2 com auth) | condicional | quando auth ativo | — | segregação |
| class_id | opcional | não | — | sim | pode existir solto |
| sci_enabled | mínimo | não | default `false` | — | ativa módulo |
| casualties | legado | não | mantido para compat, deprecated em Fase 3 | — | conta de vítimas |

### 5.6 `scenario_victims`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| code | mínimo | sim | ex.: V1 | não | referência |
| fictional_name | mínimo | sim | — | não | identificação no cenário |
| kind | mínimo | sim | ator/manequim/card/simulador | não | UX |
| actor_person_id | condicional | condicional | quando kind=ator | sim | figurante anônimo default |
| initial_priority | opcional | não | vermelho/amarelo/verde/preto | sim | triagem gera |

### 5.7 `equipment_catalog`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| name | mínimo | sim | — | não | display |
| category | mínimo | sim | enum ampla | não | filtro |
| fabricante, modelo, lote, validade, foto, manual, custo | opcional | não | — | sim | todos os detalhes |

### 5.8 `evidence_sources`
| Campo | Classe | Obrig.? | Condição | Pendente? | Impacto |
|---|---|---|---|---|---|
| title | mínimo | sim | — | não | display |
| type | mínimo | sim | artigo/guideline/manual/… | não | filtro |
| authors, year, doi, url | opcional | não | — | sim | metadados |
| verified_at | condicional | sim para publicar | só humano com role curador; **IA proibida** | — | credibilidade |

---

## 6. Busca universal de pessoas (revisada)

Endpoint único **`GET /people/search?q=…`**. Pipeline:

1. **Normalização** — remove pontuação; se `q` bate CPF/telefone/e-mail, normaliza para exato.
2. **Match exato** por `person_identifiers.value_normalized` (por qualquer `type`) e por `person_contacts.value_normalized`.
3. **Match fuzzy** por `people.display_name` e `people.social_name` (LIKE + Levenshtein; `pg_trgm` em Postgres).
4. **Match composto** — nome + data nascimento; nome + matrícula; nome + unidade.
5. Sinaliza `possible_duplicate=true` quando score > threshold.
6. Escopado por `organization_id` do usuário. Busca cross-org só `admin_tma` com auditoria.
7. Retorno vazio = `[]` + botão "Não achei — cadastrar rapidamente".

---

## 7. Cadastro simplificado + duplicidade + mesclagem

- **Rápido (1 passo, 2 campos):** `display_name` + `role_hint`. Gera `uuid`, `temp_code`, salva com `status=incomplete`, insere na turma/equipe/cenário no ato.
- **Nunca exigir** CPF/RG/e-mail/telefone.
- **No salvamento** roda pipeline de busca com o nome — se score > threshold, modal "Encontrei pessoas parecidas — é uma delas?" com opções `Usar existente | Criar novo | Cancelar`. Decisão auditada.
- **Mesclagem** só supervisionada, transacional, com diff antes/depois em `audit_logs`. `merged_into` preserva referências antigas.

---

## 8. Privacidade e LGPD (revisada)

- **Minimização** — só o obrigatório mínimo. Pendências são a norma.
- **Máscaras** — CPF `***.***.***-XX`; telefone `(11) *****-XX34`; RG só últimos 2 dígitos. Reveal apenas por role `pii_reveal` + auditoria.
- **URLs opacas** — sempre `uuid`, nunca `id` sequencial.
- **Escopo** — `organization_id` injetado por middleware; falha aberta = bug crítico.
- **Auditoria** — `audit_logs` polimórfico com ator, ação, entidade, diff resumido, IP, UA.
- **Retenção configurável** — cada organização define política, conforme finalidade, base legal, contrato, política institucional e orientação jurídica. **Anonimização automática NÃO implementar até aprovação jurídica.**
- **Nunca em logs** — filtros no `LogFormatter` mascaram CPF/e-mail/telefone.
- **CPF nunca é PK** — sempre `id BIGINT`; CPF é *um* `person_identifier`.

---

## 9. Permissões (revisadas)

**Roles** (enum em `person_roles.role` + `abilities` complementares):

- `admin_tma` — visão global; ações auditadas.
- `manager_org` — escopo à organização.
- `coordinator` — courses/classes atribuídos.
- `instructor` — classes onde está enrolled.
- `evaluator` — leitura em execução, escrita em `assessments`.
- `student` — leitura da própria turma; nunca CPF de terceiros.
- `support`, `auditor`, `viewer` — leituras restritas.

**Abilities compostas** por role: `pii_reveal`, `export_reports`, `manage_protocols`, `manage_kits`, `merge_people`.

Policies Laravel nativas (`viewAny/view/create/update/delete/…`). Nunca autorização só em Blade.

---

## 10. Complexidade do cenário (novo, C7)

`scenarios.complexity` controla renderização da ficha clínica em `scenario_victims`/`victim_profiles`:

| Nível | Campos exibidos |
|---|---|
| simples | mecanismo, sinais vitais básicos, prioridade |
| intermediario | + lesões, MARCH resumido |
| avancado | + sinais vitais completos, evolução, gatilhos |
| tatico | + zona de segurança, evacuação, comando |
| desastre | + triagem em massa, zonas, transporte |
| pediatrico | + parâmetros pediátricos (peso, dose por kg) |
| clinico | + medicamentos, acesso vascular, monitorização |
| trauma | + MARCH completo, mecanismos específicos, exposição |
| remoto | + atendimento prolongado, decisão de evacuação |
| multiplas_vitimas | + triagem, priorização, recursos |

Cenário simples continua sendo simples de criar — a decomposição só aparece quando o instrutor pediu.

---

## 11. SCI opcional (C8)

- `scenarios.sci_enabled` bool, default `false`.
- Quando `true`, execução ativa módulo `incident_commands` + `incident_positions` (comandante, operações, logística, administração, segurança, ligação, informação pública, setor médico, triagem, tratamento, transporte).
- **Nunca** exigir SCI em cenário simples ou pequeno.

---

## 12. Triagem, timeline, avaliação, debriefing

- **Triagem** — modelos configuráveis (institucional/START/JumpSTART/tática/desastre/prioridade). `triage_records` registra classificação, reclassificação, horário, avaliador, justificativa, destino, transporte, divergência.
- **Linha do tempo** — `execution_events` com fontes múltiplas (instrutor, avaliador, botões rápidos, QR, mobile, futuros sensores).
- **Avaliação** — `assessments` por pessoa/equipe/turma/execução/instrutor/instituição. Critérios: segurança, comando, comunicação, técnica, protocolo, tempo, liderança, triagem, tratamento, documentação, evacuação, equipamentos, trabalho em equipe.
- **Debriefing** — `debriefings` estruturado (reação, descrição, análise, pontos fortes, falhas, fatores contribuintes, decisões, lições, plano de ação, responsável, prazo, repetição).

---

## 13. Roadmap (revisado, Fase 2 subdividida)

### Fase 1 — Estabilização ✅ (entregue)
Wizard corrigido, ciclo de vida estável, catálogo vs observados, 20 testes verdes.

### Fase 2 — Pessoas e organizações (subdividida)

#### 2.1 Pessoas e organizações (fundação, sem auth)
Escopo: `organizations`, `units`, `organization_memberships`, `people`, `person_identifiers`, `person_contacts`, `person_roles`. Busca universal. Cadastro simplificado. Duplicidade não bloqueante. Máscara PII. Auditoria mínima.

**Não** nesta sub-fase: login, mesclagem via UI, courses/classes, relatórios.

#### 2.2 Autenticação e governança
`users` (com `person_id` nullable), login e-mail/senha, Policies com 5 roles iniciais (`admin_tma`, `manager_org`, `instructor`, `evaluator`, `student`), escopo por organização, auditoria formal, `pii_reveal` gated.

**Não** nesta sub-fase: MFA, SSO, RBAC granular, mesclagem via UI (fica no console curador).

#### 2.3 Educação
`courses`, `course_versions`, `course_modules`, `classes`, `enrollments`, `teams`, `team_members`. Papéis contextuais na turma.

#### 2.4 Administração
Importação por planilha, cadastro em lote, mesclagem supervisionada via UI, exportações autorizadas.

### Fase 3 — Vítimas configuráveis
`scenario_victims`, `victim_profiles`, `victim_injuries`, `victim_baseline_vitals`, `victim_evolution_rules`, `victim_templates`, `execution_victim_states`. `scenarios.casualties` deprecated (mantido, sincronizado por count).

### Fase 4 — Equipamentos e kits
`equipment_catalog`, `kits`, `kit_items`, `inventories`, `inventory_movements`. Migração: `scenarios.resources[]` do MVP vira `equipment_catalog` auto-criando entradas com `name`+`category=outros`, marcadas incompletas.

### Fase 5 — Protocolos e evidências
`protocols`, `protocol_versions` (imutáveis), `evidence_sources` (verified_at humano), `scenario_protocols`, `scenario_evidences`. Anexos via `attachments` polimórfico.

### Fase 6 — Execução ampliada, SCI, triagem
`executions`, `execution_participants`, `execution_teams`, `execution_events`, `execution_vital_signs`, `execution_interventions`, `triage_records`, `incident_commands`, `incident_positions`.

### Fase 7 — Gestão e relatórios
`assessments`, `debriefings`, `action_plans`, indicadores agregados, exportações.

### Fase 8 — Automação e IA
Recomendações, sugestão de evidências, geração assistida — sempre "human in the loop". IA nunca preenche `evidence_sources.verified_at`, `assessments.score`, ou `people` (categoria/documentos).

---

## 14. Migrations incrementais planejadas (esqueleto — não escritas)

Todas incrementais, com `Schema::hasColumn`/`hasTable`, reversíveis. Ordem sugerida da Fase 2.1:

1. `create_organizations_table`
2. `create_units_table`
3. `create_organization_memberships_table`
4. `create_people_table` (com `uuid` unique)
5. `create_person_identifiers_table` — índice único `(type, value_normalized, organization_id)`
6. `create_person_contacts_table` — índice `(type, value_normalized, organization_id)`
7. `create_person_roles_table`
8. `create_audit_logs_table` (polimórfico)

Fase 2.2: `create_users_add_person_id_migration`, `add_organization_id_and_class_id_to_scenarios_table` (nullable).

Fase 3+: análoga.

---

## 15. Testes planejados (grosso, por fase)

### Backend
- 2.1: cadastro sem CPF; busca por CPF com/sem pontuação; máscara PII em index; pessoa em duas orgs; duplicidade detectada; escopo por organização.
- 2.2: pessoa sem login; user vinculado a pessoa; RBAC (student não vê CPF de terceiro); auditoria de ações sensíveis.
- 2.3: enrollment múltiplos papéis; equipe herda escopo.
- 3: cenário com 1/5/30 vítimas; ator interpretando N vítimas; template aplicado; complexidade esconde campos.
- 4: kit sem lote; consumo → `inventory_movement`; alerta de validade.
- 5: protocolo em rascunho oculto; `evidence.verified_at` só por curador.
- 6: reexecução do mesmo cenário sem tocar template; SCI desligado oculta módulo.
- 7: relatório de turma agrega por aluno; export CSV; filtro período.

### Frontend
- Responsividade (mobile/tablet/desktop); teclado; foco; busca; cadastro rápido; modal; drawer; stepper; estados vazios/erro/loading; persistência de rascunho; acessibilidade WCAG AA.

---

## 16. Estratégia de UI (curadoria)

Ver documento separado: **[`docs/UI_COMPONENT_RESEARCH.md`](UI_COMPONENT_RESEARCH.md)** com tabela por tela × fonte × URL × licença × adaptação.

Prioridade fixa:
1. **21st.dev** (registry shadcn-compatível — inspiração visual e de composição)
2. **shadcn/ui** (base primitiva)
3. Componentes já existentes no design system TMA
4. Bibliotecas externas licenciadas
5. Próprio, só como última alternativa

**Não copiar código React mecanicamente** — adaptar sempre para Blade + Alpine, preservando tokens TMA (`navy-*`, `stone-*`, `emergency-*`, `clinical-*`, `alert-*`) e acessibilidade.

---

## 17. Wireframes textuais (novos, resumidos)

- **Dashboard executivo (gestor)**: KPI cards (pessoas ativas, cursos em andamento, turmas concluídas, prontidão %) → gráfico evolução → tabela unidades com semáforo → top erros recorrentes → equipamentos vencidos.
- **Dashboard instrutor**: minhas turmas → cenários em andamento → próximas execuções → últimas avaliações minhas → alertas.
- **Busca universal (global)**: input topo → resultados agrupados (pessoas, cursos, turmas, cenários, equipamentos, protocolos) → filtro por tipo → link direto.
- **Lista de pessoas**: search-first, filtros (organização, papel, status, pendências) → tabela responsiva → hover mostra badges (documentos verificados, pendências) → linha clicável.
- **Cadastro rápido (modal a partir da busca)**: display_name + role_hint → salvar e continuar.
- **Ficha de pessoa (tabs)**: Identificação · Documentos · Contatos · Vínculos · Formação · Anexos · Auditoria. Cada tab salva independente.
- **Turma**: tabs (Alunos · Equipes · Cenários · Frequência · Avaliações).
- **Criação de cenário** (evoluído do wizard atual): + passo "Complexidade" antes de Recursos; + passo "Vítimas" (a partir de Fase 3).
- **Execução ativa**: linha do tempo à esquerda, painel de vítimas ao centro (cards por vítima com estado atual), lateral de ações rápidas (registrar intervenção, sinal vital, evento). SCI aparece como tab só se `sci_enabled`.
- **Debriefing**: formulário estruturado + timeline replay + notas + plano de ação.
- **Relatório**: filtros no topo, KPIs, gráficos, tabela detalhada com export CSV/PDF.

Mobile: tudo em coluna única, ações no rodapé fixo, drawers no lugar de sidebars.

---

## 18. Complexidade estimada

| Fase | Backend | Frontend | Testes | Migrações | Total |
|---|---|---|---|---|---|
| 2.1 | M | M | M | S | **M** |
| 2.2 | M | S | M | S | **M** |
| 2.3 | M | M | M | S | **M** |
| 2.4 | S | M | S | S | **M** |
| 3 | M | L | M | S (`casualties`→count) | **L** |
| 4 | L | M | M | M (resources→catalog) | **L** |
| 5 | M | S | S | S | **M** |
| 6 | L | L | L | S | **XL** |
| 7 | M | M | M | S | **L** |
| 8 | XL | M | XL | S | **XL** |

S ≤ 3d, M ≈ 1sem, L ≈ 2sem, XL ≥ 1mês/eng.

---

## 19. Próxima fase mínima (2.1 detalhada)

**Incremento vertical entregando valor imediato:**

- Migrations 1–8 da §14 (organizations, units, memberships, people, identifiers, contacts, roles, audit_logs).
- Controllers: `OrganizationController`, `UnitController`, `PersonController` (index/create/store/show), `PeopleSearchController` (search endpoint).
- Blade: `/organizations`, `/organizations/{uuid}/units`, `/people` (search-first + modal cadastro rápido), `/people/{uuid}` (tabs Identificação/Documentos/Contatos/Vínculos/Auditoria).
- Componentes novos (adaptados da curadoria): `x-command-palette`, `x-search-input`, `x-data-table`, `x-tabs`, `x-quick-add-modal`, `x-completeness-bar`, `x-pending-badges`.
- Máscara PII em serialização.
- Middleware `EnsureOrganizationScope` (mesmo sem auth, escopa por `session('current_org_id')`).
- 25+ testes cobrindo cadastro sem CPF, busca normalizada, duplicidade, escopo, máscara.

**Não** nesta fase: users/login, courses/classes, mesclagem via UI, relatórios, protocolos, kits.

---

## 20. O que NÃO implementar ainda

- SSO institucional, MFA, TOTP.
- Assinatura digital de certificado.
- Multi-idioma.
- API pública / OAuth2.
- Módulo financeiro.
- Mobile app nativo.
- Integração com prontuário eletrônico.
- Reconhecimento facial / biometria.
- IA generativa em produção (protótipos internos até Fase 8).
- Anonimização automática por retenção (só após aprovação jurídica).
- Substituição de protocolos oficiais por conteúdo gerado.
- Mesclagem automática de pessoas.

---

## 21. Restrições ativas honradas

- Sem git, commit ou push neste ciclo.
- Sem `migrate:fresh` ou destrutivos.
- CPF/RG/matrícula/identidade funcional nunca obrigatórios; CPF nunca chave, nunca em URL.
- Sem alterar o MVP antes da aprovação.
- Sem inventar protocolos, evidências, instituições ou números.
- Sem afirmar que pesquisou 21st.dev sem registrar componente + URL (ver `UI_COMPONENT_RESEARCH.md`).

---

## 22. Decisão solicitada

> **Aprova a versão 0.2.0 e a curadoria visual para iniciar o incremento 2.1?**

Opções:

1. **Aprovar tudo** — sigo para o incremento 2.1 (migrations 1–8 + `PersonController` + `/people` + testes).
2. **Aprovar por partes** — quais fases/decisões entram; revido o doc.
3. **Ajustar antes** — apontar entidades, campos ou regras a mudar.
4. **Recusar** — descartar ou revisar com outras premissas.

Nenhuma migration, controller ou view novos são escritos até você indicar a opção.

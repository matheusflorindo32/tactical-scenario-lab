# Fase 2.4 — Auditoria do Scenario Core escalável

## Status

A Fase 2.4 implementa o M2 — Scenario Core da Institutional Edition 1.0 em `feature/phase-2-4-unlimited-casualties`.

O escopo desta fase é tornar a definição de cenários escalável e versionável, separar a estimativa total de vítimas de suas representações detalhadas e preparar a fronteira técnica para o Simulation Engine do M3.

A fase não move execução, avaliação ou debriefing para novas entidades. Esses fluxos permanecem compatíveis no `Scenario` até os marcos M3/M4.

## Base e referência de validação

- Base da fase: `main` em `e7ff9b1472b3e53ff05d439c02c67141e8f97e8b`.
- PR: #4 — `Fase 2.4 Elite — Scenario Core escalável e vítimas sem limite artificial`.
- HEAD funcional auditado antes deste documento: `b0c2b6f9e9587a05a4db0445212a596e8830283b`.
- GitHub Actions funcional: run #456, `status=completed`, `conclusion=success`.
- O commit deste documento é apenas documental; o gate de integração exige novo CI verde no HEAD final do PR.

## Escopo entregue

### 1. Escala de vítimas sem teto operacional artificial

O fluxo deixou de tratar `casualties <= 10` como regra de domínio.

Foram removidos do caminho ativo:

- validação backend `max:10`;
- validação JavaScript limitada a 10;
- atributo HTML `max="10"`;
- botão de incremento travado em 10;
- linguagem de interface “Uma a dez”;
- mensagem “Informe entre 1 e 10 vítimas.”.

A nova regra é um inteiro positivo, com cobertura explícita para:

- 1;
- 11;
- 100;
- 1.000;
- rejeição de 0;
- rejeição de negativos.

### 2. Compatibilidade do campo legado

`scenarios.casualties` foi ampliado de `unsignedTinyInteger` para `unsignedBigInteger` para eliminar o teto físico de 255 do schema legado.

Foi adicionado `scenarios.estimated_casualty_count` como representação explícita da escala estimada.

Durante a transição:

- novos cenários mantêm `casualties` e `estimated_casualty_count` sincronizados;
- o `ScenarioGenerator` aceita a estimativa canônica e mantém compatibilidade com payload legado;
- a migration faz backfill de `estimated_casualty_count` a partir de `casualties` para registros existentes.

O rollback da migration recusa de forma fail-safe reduzir `casualties` novamente para `TINYINT` caso existam valores acima de 255, evitando truncamento silencioso ou perda de dados.

### 3. ScenarioVersion

Foi criada a entidade `ScenarioVersion` com UUID público e definição versionada contendo:

- `scenario_id`;
- `version_number`;
- `environment`;
- `threat_level`;
- `mechanism`;
- `estimated_casualty_count`;
- `resources`;
- `learning_objectives`;
- `expected_actions`;
- `critical_errors`;
- `publication_status`.

Existe unicidade por `scenario_id + version_number`.

A migration cria versão 1 para cenários existentes por backfill incremental.

Novos cenários criam `Scenario` e `ScenarioVersion` v1 dentro da mesma transação, evitando cenário novo sem definição versionada correspondente.

### 4. Lifecycle de versões

`ScenarioVersionManager` implementa o lifecycle mínimo da definição:

- publicação de versão draft;
- publicação idempotente de versão já publicada;
- recusa de publicação a partir de estado incompatível;
- criação transacional da próxima revisão draft a partir de versão publicada;
- cálculo do próximo `version_number` com lock do `Scenario` para reduzir risco de corrida;
- rejeição de overrides fora do catálogo de campos da definição.

Uma versão publicada é historicamente imutável nos campos de definição.

Qualquer tentativa Eloquent de alterar definição publicada lança erro e orienta criação de nova versão.

Uma revisão de versão publicada:

- não muta a versão histórica;
- recebe novo UUID;
- recebe próximo número de versão;
- nasce como `draft`;
- permite alterar somente campos definidos no contrato da versão.

`estimated_casualty_count >= 1` também é aplicado na fronteira do modelo `ScenarioVersion`, impedindo que uso direto do domínio contorne a validação HTTP.

### 5. ScenarioVictim

Foi criada `ScenarioVictim` para representar vítimas individualmente relevantes ao exercício.

Campos estruturais:

- UUID público;
- `scenario_version_id`;
- `code` opcional;
- `profile` estruturado em JSON;
- `injuries` estruturado em JSON;
- `initial_state` estruturado em JSON;
- `expected_priority`.

O código é único dentro da mesma versão quando informado.

### 6. VictimCohort

Foi criada `VictimCohort` para representar grupos agregados sem uma linha por vítima.

Campos estruturais:

- UUID público;
- `scenario_version_id`;
- `label`;
- `quantity` em `unsignedBigInteger`;
- `profile` estruturado em JSON;
- `triage_category`;
- `characteristics` estruturadas em JSON.

O domínio exige `quantity >= 1`, inclusive no SQLite usado pelos testes.

### 7. Representação híbrida de incidentes de massa

A cobertura automatizada demonstra um cenário com 1.000 vítimas estimadas contendo apenas:

- 2 `ScenarioVictim` individualizadas;
- 1 `VictimCohort` com 998 vítimas.

Isso comprova a separação entre:

- escala total estimada;
- quantidade de registros individuais;
- quantidade de grupos agregados.

A criação de um cenário com 1.000 vítimas não cria automaticamente 1.000 registros em `scenario_victims`.

### 8. UX escalável

O fluxo ativo de criação foi substituído por `resources/views/scenarios/create-scalable.blade.php`.

A interface usa explicitamente o conceito:

**Estimativa total de vítimas**

Ela explica que a escala do incidente não determina a quantidade de registros individuais e apresenta a distinção entre:

- estimativa total;
- vítimas individuais;
- cohorts.

O detalhe ativo do cenário foi substituído por `resources/views/scenarios/show-scalable.blade.php`.

A tela mostra:

- versão atual;
- estimativa total de vítimas;
- quantidade de representações individuais;
- quantidade de cohorts;
- objetivos;
- ações esperadas;
- catálogo de erros críticos;
- fluxo legado de execução/avaliação preservado até M3/M4.

As views antigas `scenarios/create.blade.php` e `scenarios/show.blade.php` foram removidas após o novo fluxo passar pela suíte completa, eliminando o código morto que ainda carregava o limite artificial.

## Isolamento institucional

A Fase 2.4 preserva a fronteira institucional consolidada nas Fases 2.1–2.3:

- criação de cenário exige `scenarios.manage` na organização ativa;
- leitura exige `scenarios.view`;
- execução exige `scenarios.manage`;
- avaliação exige `evaluations.manage`;
- acesso direto a cenário de outra organização continua bloqueado;
- `ScenarioVersion`, `ScenarioVictim` e `VictimCohort` pertencem ao agregado por meio do `Scenario` institucional proprietário.

Não foram adicionadas rotas públicas independentes para versões/vítimas/cohorts nesta fase; portanto não foi criado um novo caminho HTTP capaz de contornar o ownership do cenário.

## TDD e cobertura de regressão

A implementação foi conduzida por ciclos RED → GREEN no GitHub Actions.

### `ScenarioCasualtyScaleTest`

Cobre:

- 1, 11, 100 e 1.000 vítimas estimadas;
- rejeição de 0 e negativos;
- ausência de criação implícita de vítimas individuais;
- linguagem e campo canônico da interface de criação;
- ausência do limite visual legado;
- distinção visual entre estimativa e representações detalhadas.

### `ScenarioVersioningTest`

Cobre:

- contrato de schema da versão;
- criação automática da versão 1;
- UUID público;
- estimativa escalável na versão;
- versão publicada imutável;
- revisão gerando próxima versão draft;
- preservação da versão histórica;
- rejeição de estimativa não positiva na fronteira do domínio.

### `ScenarioVictimModelTest`

Cobre:

- contratos de storage de vítimas e cohorts;
- representação híbrida de incidente de massa;
- UUIDs;
- relação com a versão e o cenário institucional;
- `quantity >= 1` em cohort.

### Regressão existente

Os ciclos verdes também executaram toda a suíte existente, incluindo autenticação, abilities, isolamento multi-organização, PII, auditoria, lifecycle institucional e o fluxo legado de execução/avaliação.

## Evidência funcional antes da documentação

No HEAD funcional `b0c2b6f9e9587a05a4db0445212a596e8830283b`, o GitHub Actions run #456 concluiu com sucesso nos jobs:

- Laravel Pint;
- instalação Composer;
- Node/NPM;
- build Vite;
- preparação de ambiente;
- migrations;
- PHPUnit.

O gate final de integração deve usar o CI do HEAD documental final do PR #4, não apenas o run funcional acima.

## Limitações deliberadamente posteriores

Os itens abaixo não são ausência acidental da Fase 2.4; pertencem a marcos posteriores:

- separar `Execution` de `Scenario` — M3;
- múltiplas execuções independentes — M3;
- equipes e participantes por execução — M3;
- timeline — M3;
- eventos e intervenções — M3;
- instructor injects — M3;
- estado dinâmico das vítimas durante execução — M3;
- assessment multi-critério — M4;
- debriefing estruturado — M4;
- plano de ação — M4;
- dashboard e relatórios institucionais avançados — M5;
- CRUD visual completo e refinado para gerenciamento de vítimas/cohorts pode ser acoplado ao workflow operacional do M3/M5; nesta fase o contrato de domínio, persistência e leitura agregada estão estabelecidos;
- PostgreSQL/Docker de produção — M6;
- Design System final — M7;
- Wiki Premium Elite Diamante — M8.

## Riscos e decisões técnicas registradas

### Duplicidade temporária de definição

Durante a transição, campos de definição permanecem também em `scenarios` para não quebrar os fluxos existentes. `ScenarioVersion` é a fronteira de versionamento criada nesta fase, mas a remoção definitiva de duplicidade deve ocorrer somente quando M3/M4 moverem execução/avaliação e consumidores antigos estiverem migrados.

Essa duplicidade é dívida transitória consciente, não justificativa para apagar dados agora.

### Rollback do campo casualties

O rollback para `unsignedTinyInteger` não é destrutivo: ele falha explicitamente se houver valores acima de 255. Isso privilegia integridade sobre reversão cega.

### Gestão visual de versões e representações

O M2 entrega lifecycle de versão no domínio e representação híbrida persistente. Uma interface administrativa completa para publicar/revisar versões e editar vítimas/cohorts não foi adicionada para evitar misturar Scenario Core com os workflows operacionais do M3 e a camada de produto do M5.

## Gate de integração

A Fase 2.4 só pode ser marcada `READY FOR INTEGRATION` se:

- [x] PR #4 permanece isolado de M3/M4;
- [x] branch está baseada em `main` sem divergência conhecida;
- [x] limite artificial de 10 removido do fluxo ativo;
- [x] 1/11/100/1000 cobertos;
- [x] 0 e negativos rejeitados;
- [x] `estimated_casualty_count` implementado;
- [x] compatibilidade do campo legado preservada;
- [x] `ScenarioVersion` implementado;
- [x] backfill de versão 1 implementado;
- [x] novos cenários criam versão 1 atomicamente;
- [x] versão publicada é imutável;
- [x] revisão cria próxima versão sem mutar história;
- [x] `ScenarioVictim` implementado;
- [x] `VictimCohort` implementado;
- [x] cohort exige quantidade positiva;
- [x] 1.000 estimadas não geram 1.000 registros automaticamente;
- [x] UI diferencia escala e representações;
- [x] views legadas com teto artificial removidas;
- [x] PR #4 sem review threads pendentes na auditoria pré-documentação;
- [x] CI funcional verde no run #456;
- [ ] CI verde no HEAD final do PR após este documento.

Não fazer merge antes do último item.

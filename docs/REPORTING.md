# M5 — Reporting institucional

Este documento descreve os contratos operacionais e de segurança do reporting institucional introduzido no M5 do Tactical Scenario Lab.

## Fonte de verdade

Os dashboards, histórico e exportações M5 usam como fonte operacional:

- `ScenarioExecution` para a realização concreta do treinamento;
- `ExecutionAssessment` para avaliação estruturada M4;
- `CriticalErrorOccurrence` para erros efetivamente observados;
- `ActionItem` para pendências de debriefing;
- snapshots de `ExecutionParticipant` para atribuição histórica de unidade.

`Scenario.score` é legado e **não é fonte de verdade para métricas institucionais M5**.

### Taxa de aprovação

A taxa de aprovação considera apenas avaliações `finalized` cujo `result` seja conhecido. Avaliações legadas sem resultado semântico (`result = null`) permanecem preservadas, mas são excluídas do denominador da taxa de aprovação; nenhum resultado é inferido ou fabricado.

### Erros críticos

O ranking de erros críticos é calculado a partir de `CriticalErrorOccurrence`. O catálogo previsto no cenário não conta como ocorrência observada.

## Contexto institucional e autorização

O tenant ativo é obtido por `ActiveOrganization`. O cliente não escolhe livremente `organization_id` para consultas de reporting.

As superfícies abaixo exigem `reports.view`:

- dashboard executivo;
- histórico institucional de execuções;
- exportação CSV;
- PDF de execução.

Filtros de unidade e cenário são resolvidos por UUID e aceitos somente quando pertencem à organização ativa.

## Período e filtros

`InstitutionalFilter` recebe apenas:

- `date_from`;
- `date_to`;
- `unit_uuid`;
- `scenario_uuid`;
- `status`.

Sem datas explícitas, o período padrão cobre os últimos **90 dias corridos**, incluindo o dia final. O intervalo máximo é de **366 dias corridos**, contando as duas datas.

O instante de referência de uma execução é `started_at` quando disponível; caso contrário, `created_at`.

## Unidade histórica

Filtros e relatórios não tentam reconstruir a unidade atual da pessoa. A atribuição histórica vem do participante da execução:

- `organization_membership_id` registra o vínculo utilizado;
- `unit_id_snapshot` preserva a unidade da execução;
- `unit_name_snapshot` preserva o rótulo histórico;
- `position_snapshot` preserva a posição institucional naquele momento.

Renomeações posteriores da unidade não reescrevem o histórico já capturado.

## Histórico institucional

A listagem institucional é paginada e ordenada pelo instante operacional mais relevante (`started_at` ou `created_at`), com desempate por ID.

A consulta:

1. restringe `scenario_executions.organization_id` ao tenant ativo;
2. aplica período, cenário, unidade histórica e status;
3. carrega cenário/versionamento, avaliação e snapshots de participantes;
4. calcula contagem de erros críticos observados;
5. calcula action items abertos ou em andamento.

## Dashboard do instrutor

O dashboard do instrutor usa filas operacionais reais:

- execuções em andamento;
- execuções em rascunho;
- execuções concluídas ainda sem avaliação;
- avaliações em rascunho;
- action items abertos/em andamento;
- action items vencidos;
- ações com vencimento próximo;
- avaliações finalizadas recentes.

Os recortes de organização, período, cenário e unidade histórica são aplicados às consultas.

## Dashboard executivo

O dashboard executivo agrega:

- total de execuções;
- execuções concluídas;
- avaliações finalizadas;
- média de `final_score` quando disponível;
- taxa de aprovação apenas entre resultados conhecidos;
- quantidade de `automatic_fail`;
- erros críticos observados mais frequentes;
- action items abertos/em andamento;
- action items vencidos;
- tendência mensal de execuções.

A tendência mensal é derivada das execuções do tenant e do período filtrado.

## CSV institucional

Endpoint: `GET /reports/executions.csv`.

### Streaming

A resposta é um `StreamedResponse`. O exportador escreve diretamente em `php://output` e percorre a consulta por `lazyById(200)`. Assim, o histórico completo não é materializado em memória antes do download.

### Ordem estável de colunas

1. `execution_uuid`
2. `execution_sequence`
3. `scenario_uuid`
4. `scenario_title`
5. `scenario_version`
6. `unit_uuids`
7. `unit_names`
8. `execution_status`
9. `started_at`
10. `completed_at`
11. `assessment_status`
12. `final_score`
13. `result`
14. `automatic_fail`
15. `critical_error_count`
16. `open_action_count`

### Proteção contra formula injection

Valores textuais iniciados por `=`, `+`, `-` ou `@` recebem prefixo de apóstrofo antes de serem gravados no CSV. A ordem das colunas e a neutralização possuem testes permanentes.

### Cache

A resposta utiliza `Cache-Control: no-store, private`.

## PDF institucional

Endpoint: `GET /reports/executions/{execution}/pdf`.

### Fence de tenant

Antes de renderizar, o controller:

1. exige `reports.view` no contexto ativo;
2. confirma que `execution.organization_id` é igual ao tenant ativo;
3. passa explicitamente o `organizationId` ao builder, que revalida o pertencimento.

O teste permanente de cross-org exige HTTP 403 para uma execução estrangeira.

### Minimização de dados

O `ExecutionReportDataBuilder` cria um DTO/array explícito de apresentação. Ele não serializa livremente models Eloquent.

O PDF pode conter dados operacionais necessários, como:

- organização;
- cenário e versão;
- execução;
- equipes;
- nome preferencial do participante;
- função, equipe, unidade e posição históricas;
- avaliação, critérios e evidências;
- erros observados;
- tempos-chave;
- debriefing e plano de ação.

Contatos e identificadores pessoais não integram o builder. Há teste permanente que cria um contato de e-mail e prova que ele não aparece no payload do relatório.

### Dompdf

O PDF usa Dompdf instalado via Composer. `isRemoteEnabled` é configurado como `false`, impedindo carregamento remoto de assets durante a renderização. O template é renderizado localmente e o resultado é devolvido como `application/pdf` com `Cache-Control: no-store, private`.

## Garantias cobertas por testes

A suíte M5 mantém testes para:

- escopo do tenant ativo;
- autorização `reports.view`;
- filtros same-org;
- período padrão e limite máximo;
- métricas M3/M4 sem depender de `Scenario.score`;
- exclusão de resultado legado desconhecido da taxa de aprovação;
- erros críticos baseados em ocorrências observadas;
- atribuição histórica de unidade;
- histórico paginado;
- CSV tenant-safe;
- ordem estável das colunas;
- neutralização de fórmula;
- PDF válido;
- minimização de PII;
- bloqueio de PDF cross-org.

## Regra de evolução

Qualquer nova métrica ou campo exportado deve preservar estas regras:

1. fonte de verdade M3/M4 explícita;
2. tenant derivado do contexto autenticado;
3. autorização compatível com a superfície;
4. sem inferir semântica inexistente em dados legados;
5. mínimo de dados necessário ao propósito;
6. teste de isolamento institucional e regressão antes de integração.

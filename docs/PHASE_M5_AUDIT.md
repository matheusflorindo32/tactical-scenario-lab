# PHASE M5 — Auditoria forense do produto institucional

Data da auditoria: 2026-08-08

Escopo: **somente o delta M5 do PR #7** (`feature/m5-institutional-product` → `main`). Esta auditoria não autoriza, especifica nem antecipa funcionalidades de M6–M9.

## 1. Objetivo

Verificar se o M5 fecha a camada institucional sobre os contratos já estabelecidos em M1–M4 sem:

- reintroduzir fontes legadas como verdade operacional;
- permitir escolha de tenant pelo cliente;
- reconstruir atribuição histórica de forma especulativa;
- materializar históricos inteiros em exportações;
- expor PII não necessária;
- copiar histórico operacional ao reutilizar templates;
- carregar dados reais ou permitir demo em produção;
- declarar gates verdes sem evidência do SHA correspondente.

## 2. Método

A revisão foi conduzida em quatro camadas:

1. **leitura do delta**: models, migrations, services, controllers, queries de reporting, views, exportadores, renderer PDF, DemoSeeder e workflow;
2. **invariantes de domínio**: comparação com lifecycle, tenant, snapshots e imutabilidade já existentes em M1–M4;
3. **testes permanentes**: isolamento, autorização, métricas, multi-unidade, CSV, PDF, templates e demo;
4. **CI por SHA**: cada task foi fechada somente após workflow do commit correspondente concluir com `success`.

## 3. Resultado executivo

Após as remediações descritas nesta auditoria, **não foi identificado achado Critical ou High remanescente no delta M5**.

A integração permanece condicionada aos gates finais do PR: workflow exato do HEAD, sincronização com `main`, mergeability, revisão sem threads pendentes e merge protegido pelo SHA esperado.

## 4. Achados e remediações

### A-01 — Gate PDF com falsa falha por serialização Unicode

**Severidade real:** Low / teste, sem impacto de produção.

O primeiro gate final da Task 5 falhou porque o teste serializava o array do relatório com `json_encode()` padrão e procurava texto UTF-8 literal. O JSON escapava Unicode, produzindo uma falha de asserção sem indicar vazamento de PII ou erro do builder.

**Evidência:** CI run #642: 255 testes verdes e uma falha de serialização. O teste permanente cross-org permaneceu verde.

**Remediação:** o teste passou a usar `JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE`, sem alteração do builder ou do renderer de produção.

**Gate de fechamento:** run #643, commit `2ae408d653f513b5154bd45243df446045879bc4`, `success`.

### A-02 — DemoSeeder inicialmente incompleto para o walkthrough contratual

**Severidade durante auditoria:** High para a qualidade da demonstração, **não** para confidencialidade/integridade de produção.

O primeiro DemoSeeder verde demonstrava organização, unidades, cenários, template, uma avaliação finalizada, debrief e execução running, porém não cobria todo o grafo exigido pelo plano M5: avaliação em `draft`, ocorrência crítica observada, key time e múltiplos estados de action item.

**Resposta TDD:** o teste de integração foi ampliado antes da implementação.

- RED controlado: run #661 — 262 testes antigos verdes; apenas `DemoSeederTest` falhou no novo contrato.
- GREEN: run #662 — SHA `26a685d05ce24a7e80ae7194e35c258871f12cdb`, PHPUnit e Pint verdes.

**Estado após remediação:** o seed cria, de forma determinística e fictícia:

- execução `running`;
- ao menos duas execuções `completed`;
- avaliação `draft`;
- avaliação `finalized` calculada pelo domínio;
- ocorrência crítica observada;
- key time derivado do lifecycle da execução;
- debrief estruturado;
- action items em `open` e `in_progress` por transição explícita.

O bloqueio de produção permanece testado diretamente no `DemoSeeder`.

### A-03 — CI anterior não executava o gate final exato do plano

**Severidade durante auditoria:** High para assurance/release discipline.

O workflow anterior executava `composer install`, `npm ci`, build, `php artisan migrate --force`, PHPUnit e Pint, mas não executava:

- `composer validate --strict`;
- `php artisan migrate:fresh --force`.

**Remediação:** `.github/workflows/tests.yml` foi endurecido para incluir `composer validate --strict` e reconstrução integral do banco com `migrate:fresh --force`, preservando `npm ci`, `npm run build`, `php artisan test` e `vendor/bin/pint --test`.

**Estado:** remediado em código; a integração somente poderá ocorrer após o workflow do HEAD final confirmar este gate reforçado.

## 5. Revisão por dimensão

### 5.1 Correção funcional — PASS

- O dashboard executivo deriva métricas de `ScenarioExecution`, `ExecutionAssessment`, `CriticalErrorOccurrence` e `ActionItem`.
- `Scenario.score` permanece legado e não alimenta métricas institucionais M5.
- Resultados legados sem semântica (`result = null`) não são convertidos artificialmente em aprovado/reprovado e ficam fora do denominador da taxa de aprovação.
- O histórico usa paginação e ordenação operacional estável.
- Templates criam nova identidade de cenário + versão 1 `draft` sem copiar histórico operacional.

### 5.2 Autorização multi-tenant — PASS

- O tenant de reporting deriva de `ActiveOrganization`; o cliente não fornece `organization_id` livre.
- Dashboard executivo, histórico, CSV e PDF exigem `reports.view`.
- Filtros por cenário/unidade resolvem UUIDs e validam pertencimento à organização ativa.
- PDF valida tenant no controller e novamente no builder.
- Há teste permanente para tentativa de PDF cross-org.
- Criação/uso/arquivamento de templates é tenant-safe e requer `scenarios.manage` nas superfícies HTTP.

### 5.3 Performance — PASS

- Histórico institucional é paginado.
- CSV utiliza `StreamedResponse` e iteração `lazyById(200)`; não materializa todo o histórico antes do download.
- Relações necessárias ao reporting são carregadas de forma explícita.
- Filtro multi-unidade usa existência de participante histórico em vez de duplicar a linha de execução.

### 5.4 Exatidão de reporting — PASS

- Aprovação usa somente avaliações finalizadas com `result` conhecido.
- Erros críticos são contados a partir de ocorrências observadas, não do catálogo do cenário.
- Unidade histórica vem dos snapshots de `ExecutionParticipant`.
- Execução multi-unidade aparece uma vez; CSV agrega unidades históricas distintas.
- Período padrão é de 90 dias corridos e o máximo é 366 dias inclusivos.

### 5.5 Privacidade e segurança de saída — PASS

- CSV neutraliza células textuais iniciadas por `=`, `+`, `-` ou `@`.
- CSV e PDF usam `Cache-Control: no-store, private`.
- PDF é construído por payload explícito, sem serialização irrestrita de models.
- Contatos e identificadores pessoais ficam fora do builder e são cobertos por teste permanente.
- Dompdf opera com remote assets desativados.
- O template PDF recebe dados de apresentação já selecionados; não há caminho M5 para fetch remoto de conteúdo.

### 5.6 Usabilidade — PASS

- Há catálogo institucional de templates com estado ativo/arquivado.
- Versões publicadas expõem ação de salvar como template.
- O DemoSeeder oferece estados operacionais diferentes para demonstrar filas, reporting e debriefing sem dados reais.
- `docs/DEMO.md` descreve percurso reproducível.
- `docs/REPORTING.md` explicita semântica de filtros, unidade histórica, multi-unidade, CSV e PDF.

### 5.7 Manutenibilidade — PASS

- Fontes de verdade ficam encapsuladas em queries/builder/exporter específicos de reporting.
- `ScenarioVersion::DEFINITION_FIELDS` centraliza o conjunto clonável pelo template.
- Lifecycle de execução/avaliação é reutilizado pelo DemoSeeder em vez de fabricar estados finais por INSERT arbitrário.
- Testes permanentes cobrem fronteiras de tenant, snapshots, exportações e demo.
- O SDD ledger registra RED, remediação e GREEN por task.

### 5.8 Disciplina de escopo M5 — PASS

O delta observado se mantém em:

- atribuição histórica necessária ao reporting;
- filtros e dashboards institucionais;
- histórico;
- CSV;
- PDF;
- templates;
- DemoSeeder;
- documentação/auditoria/CI de fechamento.

Não foi identificado trabalho funcional de M6, M7, M8 ou M9 no escopo revisado.

## 6. Checklist forense M5

- [x] Nenhum dashboard/relatório novo depende de `Scenario.score` como verdade.
- [x] Nenhum filtro de reporting aceita `organization_id` cliente como tenant.
- [x] Reporting é fenced pela organização ativa.
- [x] Snapshots históricos de unidade não aceitam membership cross-org.
- [x] Membership ambíguo não é inferido silenciosamente.
- [x] Backfill histórico só preenche quando existe exatamente um candidato válido no anchor temporal.
- [x] Histórico grande é paginado; CSV é streaming/lazy.
- [x] CSV neutraliza `=`, `+`, `-` e `@` no início de valor textual.
- [x] PDF não expõe contacts/identifiers e bloqueia cross-org.
- [x] PDF não habilita remote assets.
- [x] Template não clona execution/assessment/evidence/debrief.
- [x] Template não atravessa tenant.
- [x] DemoSeeder usa apenas identidades e conteúdos fictícios.
- [x] DemoSeeder se recusa a rodar em `production`.
- [x] DemoSeeder cobre múltiplos estados operacionais e de avaliação.
- [x] Nenhum item funcional de M6–M9 foi introduzido deliberadamente.
- [x] Workflow foi reforçado para executar o conjunto exato de verificação final.

## 7. Gates obrigatórios antes do merge

Mesmo com a auditoria funcional concluída, **não integrar** até que todos os itens abaixo sejam comprovados no estado final do PR:

1. `composer validate --strict` verde;
2. `php artisan migrate:fresh --force` verde;
3. `php artisan test` verde;
4. `vendor/bin/pint --test` verde;
5. `npm ci` verde;
6. `npm run build` verde;
7. PR atualizado com `main`;
8. PR `mergeable`;
9. zero threads de review não resolvidas;
10. zero achados Critical/High remanescentes;
11. HEAD obtido novamente imediatamente antes da integração;
12. merge commit executado somente com o SHA esperado.

## 8. Conclusão da auditoria

O M5 está tecnicamente apto a entrar no **gate de integração**, condicionado às verificações finais acima. Os dois gaps relevantes encontrados durante a revisão — completude do grafo demo e exatidão do workflow de release — foram remediados antes da integração, preservando o princípio adotado desde o início do milestone: **evidência antes de declaração de sucesso**.

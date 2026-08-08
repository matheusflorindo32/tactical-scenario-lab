# M4 — Assessment & Debriefing — Auditoria Forense

**Data:** 2026-08-08  
**Branch:** `feature/m4-assessment-debriefing`  
**Base auditada:** `main` em `c135cd12ef2415d91b7e2ba4636bfbd23dac8759`  
**HEAD funcional auditado antes deste documento:** `4edbab24f543d028d16c3d9bd65f221e7296ca03`  
**Status:** funcionalmente aprovado; a integração só é autorizada se o CI do HEAD que contém este documento concluir integralmente em verde.

---

## 1. Escopo entregue

O M4 transfere avaliação e debriefing do `Scenario` legado para o contexto correto: cada `ScenarioExecution` pode possuir no máximo um `ExecutionAssessment`.

Arquitetura entregue:

```text
Scenario
  └── ScenarioVersion
        └── ScenarioExecution
              └── ExecutionAssessment
                    ├── AssessmentCriterion
                    │     └── AssessmentEvidence
                    ├── CriticalErrorOccurrence
                    ├── KeyTimeRecord
                    └── ExecutionDebrief
                          ├── DebriefEntry
                          └── ActionItem
```

Não foram introduzidos relatórios M5, hardening PostgreSQL/M6, redesign M7, Wiki M8, IA, API externa ou outros módulos fora do escopo aprovado.

## 2. Modelo de dados e integridade

Foram entregues tabelas normalizadas para assessment, rubrica/evidência, erros críticos observados, tempos-chave, debrief e plano de ação.

Garantias verificadas:

- um assessment por execução por constraint única;
- UUID público separado do BIGINT interno;
- chaves estrangeiras e cascatas coerentes com o agregado;
- organização do assessment obrigatoriamente igual à organização da execução;
- um debrief por assessment;
- ocorrência do mesmo erro crítico não pode ser duplicada no mesmo assessment;
- referências de evento são opcionais, mas, quando presentes, precisam pertencer à mesma execução;
- pessoa responsável por ação precisa estar ativa e possuir vínculo institucional ativo na mesma organização.

### Achado forense corrigido

Durante a auditoria final foi identificado um gap de integridade latente: a criação interna de um `ExecutionAssessment` permitia, antes do guard final, combinar `organization_id` de uma organização com `scenario_execution_id` de outra. Os controllers públicos já criavam corretamente, mas um registro inconsistente poderia enfraquecer a fronteira de tenant em leituras futuras.

O problema foi congelado por teste TDD e corrigido no próprio domínio. O run #590 produziu RED limpo — 234 testes passaram e somente o novo invariant falhou. O commit `4edbab24f543d028d16c3d9bd65f221e7296ca03` adicionou o guard, e o run #591 concluiu em sucesso.

## 3. Rubrica, evidência e scoring

`AssessmentScoreCalculator` é a autoridade única das fórmulas de score.

Regras verificadas:

- pesos da rubrica somam exatamente `100.00` na finalização normal;
- objetivos de aprendizagem podem semear critérios com distribuição determinística em centésimos;
- todos os critérios precisam de score entre 0 e 100;
- todo critério precisa de evidência;
- evidência respeita a janela temporal da execução;
- `base_score = Σ(score × weight) / 100`;
- `final_score = clamp(base_score - penalty_points + evaluator_adjustment, 0, 100)`;
- ajuste é inteiro entre -10 e +10;
- ajuste diferente de zero exige justificativa;
- threshold normal M4 é snapshot `70.00`;
- `automatic_fail` prevalece sobre aprovação sem reescrever o score numérico.

## 4. Erros críticos observados

O catálogo da `ScenarioVersion` permanece definição imutável; observações pertencem ao assessment.

Regras verificadas:

- `record` não gera penalidade numérica;
- `penalty` exige penalidade positiva e limitada;
- `automatic_fail` é explícito e não infere desconto numérico;
- novos registros M4 precisam usar item do catálogo da versão;
- evento associado precisa pertencer à mesma execução;
- legado pode preservar texto histórico fora do catálogo sem reinterpretá-lo;
- conteúdo finalizado é imutável.

## 5. Tempos-chave

O cliente não é autoridade de `elapsed_seconds`.

O backend calcula o tempo decorrido a partir de `execution.started_at`, ignora valores forjados do cliente e rejeita registros anteriores ao início ou posteriores à conclusão da execução. `elapsed_seconds` não é mass assignable.

## 6. Debrief e plano de ação

O debrief normal M4 separa explicitamente:

- fato;
- interpretação;
- recomendação.

`legacy_unstructured` é reservado a importação histórica e não é aceito pelo HTTP público M4.

O plano de ação exige ação, responsável e prazo. A máquina de estados é explícita:

```text
open -> in_progress | completed | cancelled
in_progress -> completed | cancelled
completed -> terminal
cancelled -> terminal
```

Após finalização, conteúdo histórico da ação é congelado. Somente a transição operacional de status permanece permitida, com ator e timestamp persistidos.

## 7. Finalização e imutabilidade

`ExecutionAssessmentManager::finalize()` é o caminho normal de finalização e executa em transação com `lockForUpdate` no assessment.

A finalização:

- relê estado persistido;
- rejeita finalização duplicada/stale;
- exige execução `completed`;
- rejeita `running` e `cancelled`;
- valida rubrica, evidências, debrief e ajuste;
- calcula score centralmente;
- persiste componentes, resultado, finalizador e timestamp;
- devolve o snapshot finalizado.

Models de critérios, evidências, erros críticos, tempos-chave, debrief, entradas e conteúdo de ações impedem update/delete após finalização. Nenhuma rota HTTP reabre assessment finalizado.

**Limite deliberado:** triggers/check constraints de imutabilidade no banco e testes sofisticados de serialização concorrente em PostgreSQL pertencem ao M6. O M4 garante a fronteira de aplicação e o lock do caminho normal de finalização.

## 8. Autorização e isolamento institucional

Fronteiras verificadas:

- leitura exige `scenarios.view`;
- mutação exige `evaluations.manage`;
- `scenarios.manage` sozinho não autoriza avaliação;
- cada controller resolve a organização ativa antes de ler/mutar;
- child resources derivam o assessment pai e validam o tenant antes da operação;
- referências de evento e pessoa são revalidadas no domínio;
- o invariant `assessment.organization_id == execution.organization_id` é aplicado no model;
- todos os aggregates M4 endereçáveis publicamente usam UUID.

A suíte cobre leitura/escrita cross-org, ability insuficiente e referências de outra execução.

## 9. Auditabilidade e privacidade

A proveniência exigida pela spec está persistida no próprio domínio:

- finalizador e horário;
- componentes do score;
- justificativa do ajuste;
- regra/penalidade do erro crítico;
- autor de evidência;
- marcador de importação legada;
- ator/horário de mudança de status da ação.

O M4 não duplica desnecessariamente evidências e debriefs em logs genéricos, preservando a convenção de minimização de texto livre/PII. O legado sem autor historicamente verificável permanece com autor nulo em vez de receber identidade artificial.

## 10. Migração do legado

A migração é conservadora e idempotente.

Para cenários com mapeamento confiável para a execução histórica #1:

- score é preservado como score histórico;
- não é criado threshold ou resultado sintético;
- erro legado vira ocorrência `source=legacy`, `rule=record`, penalidade zero;
- debrief legado vira `legacy_unstructured` sem classificação semântica inventada;
- autor histórico desconhecido permanece nulo;
- colunas antigas continuam no banco para rollback/auditoria.

Se não existe execução histórica mapeada, o importador não adivinha e deixa os dados de origem intactos.

`ScenarioController::evaluate` e a rota `scenarios.evaluate` foram aposentados. A página do cenário não envia mais score, debrief ou erros observados para campos legados; registros antigos permanecem somente para consulta e a avaliação estruturada é acessada pela execução.

## 11. UX e performance

A execução possui CTA dedicado **Avaliação & Debriefing**.

Em draft, a página funciona como estação de trabalho com rubrica, evidências, erros críticos, tempos-chave, debrief e ações. Em finalized, o conteúdo torna-se histórico/read-only e apenas o status das ações continua operacional.

A página do assessment faz eager loading do grafo necessário — critérios/evidências/eventos, erros observados, tempos-chave, debrief/ações e pessoas responsáveis — evitando o N+1 óbvio do fluxo principal. Analytics e reporting avançados permanecem M5.

## 12. Regressão e CI

Evidências principais do ciclo final:

- run #582: RED da aposentadoria incompleta do fluxo legado;
- run #583: produção corrigida; falhas restantes isoladas em testes antigos;
- run #589: Task 7 completa e suíte integral verde;
- run #590: RED limpo para mismatch organização/execução;
- run #591: invariant corrigido e suíte integral verde no HEAD funcional `4edbab24...`.

O CI executa, em conjunto:

- Composer install;
- npm install;
- Vite build;
- migrations em banco limpo;
- PHPUnit completo;
- Laravel Pint.

**Gate final deste documento:** o HEAD que contém `docs/PHASE_M4_AUDIT.md` precisa receber um novo run integralmente verde. O sucesso de #591 é evidência funcional, mas não substitui o CI do HEAD documental final.

## 13. Revisão do PR e higiene de escopo

Na auditoria pré-documentação:

- `main` era exatamente o merge-base do M4;
- branch estava `ahead` e `behind_by=0`;
- PR #6 estava mergeable;
- zero comentários de PR;
- zero review threads;
- compare atual continha somente domínio/controllers/migrations/views/tests/docs do M4 e as alterações necessárias para retirar a avaliação legada;
- `composer.lock` e `package-lock.json` tinham o mesmo blob SHA em `main` e na branch e não aparecem no compare efetivo;
- não foi encontrado código M5/M6/M7/M8/M9 misturado ao M4.

## 14. Limitações deliberadamente posteriores

Não são defeitos bloqueantes do M4:

- relatórios PDF/CSV, dashboards e analytics — M5;
- PostgreSQL, Docker/produção, stress concorrente avançado e enforcement de imutabilidade em banco — M6;
- auditoria final do design system — M7;
- Wiki completa — M8;
- release forense/tag `v1.0.0` — M9;
- amendment/reopen de assessment finalizado — fora do M4 aprovado.

## 15. Gate de integração

O M4 pode ser integrado somente se, no HEAD final:

1. `compare main...feature/m4-assessment-debriefing` continuar sem divergência da base (`behind_by=0`) e sem contaminação de escopo;
2. PR #6 continuar mergeable;
3. não existirem comentários/review threads bloqueantes;
4. GitHub Actions concluir Pint, build, migrations e PHPUnit em verde;
5. nenhum issue crítico/alto novo surgir na verificação final.

Se todos os cinco itens forem verdadeiros, o estado é **READY FOR INTEGRATION — M4 Assessment & Debriefing**.

# Tactical Scenario Lab — Institutional Edition 1.0 Design

## Objetivo

Concluir o Tactical Scenario Lab como um produto institucional autônomo, seguro, demonstrável, didático e tecnicamente sólido, culminando na release `v1.0.0 — Institutional Edition`.

O projeto Laravel não será expandido indefinidamente. O foco até a versão 1.0 é completar o fluxo essencial de treinamento institucional:

`Scenario → ScenarioVersion → Victims/Cohorts → Execution → Assessment → Debriefing → Report`

Após `v1.0.0`, evoluções maiores como IA completa, Evidence Core completo, Research Hub, marketplace, microserviços, mobile nativo, digital twin sofisticado, analytics científico avançado e colaboração live complexa ficam fora deste repositório e podem ser absorvidas pela plataforma TMA.

---

## Princípios de arquitetura

1. **Segurança e isolamento institucional primeiro.** Nenhum novo fluxo poderá romper tenant isolation, abilities, proteção de PII, auditoria ou segurança de sessão.
2. **Cenário é diferente de execução.** Um cenário publicado deve poder ser executado várias vezes sem mutar sua definição histórica.
3. **Versionamento explícito.** Alterações relevantes em cenários publicados devem produzir nova versão.
4. **Escala sem explosão de registros.** `estimated_casualty_count` deve ser independente do número de vítimas individualmente modeladas.
5. **Vítimas individuais e cohorts coexistem.** `ScenarioVictim` modela indivíduos relevantes; `VictimCohort` modela grupos agregados.
6. **Avaliação separa fato, interpretação e recomendação.** Isso melhora debriefing, relatórios e futura utilização científica.
7. **YAGNI.** Nenhum módulo fora do fluxo institucional central entra antes do `v1.0.0`.
8. **Testes antes de correções funcionais.** Mudanças de comportamento devem ser conduzidas por testes sempre que possível.
9. **Branches e PRs preservam história.** Sem force push desnecessário, mega-merge ou reconstrução do histórico.
10. **Documentação deve refletir software real.** Wiki, README e guias só serão finalizados após estabilização funcional.

---

## Topologia Git autoritativa no início deste plano

- `main`
- `feature/phase-2-1-elite`
- `feature/phase-2-2-auth`
- `feature/phase-2-3-access-admin`
- `feature/phase-2-4-unlimited-casualties`
- `backup/claude-phase-2-1-wip`
- `backup/phase-2-2-auth-start`
- `backup/phase-2-3-admin-start`

PRs existentes:

- PR #1 — Fase 2.1 Elite
- PR #2 — Fase 2.2 Auth
- PR #3 — Fase 2.3 Access Admin

Estratégia de integração:

1. validar e integrar PR #1;
2. retargetar PR #2 para `main`, validar e integrar;
3. retargetar PR #3 para `main` após integração da 2.2;
4. concluir M1 na branch da 2.3;
5. validar CI e integrar PR #3;
6. continuar M2 na branch `feature/phase-2-4-unlimited-casualties`.

Não apagar branches de backup antes da release `v1.0.0`.

---

# Marcos de conclusão

## M1 — Governance & Access Hardening

### Objetivo

Fechar definitivamente a Fase 2.3 antes de qualquer expansão do domínio de cenários.

### Já existente

- autenticação institucional;
- organização ativa por sessão;
- abilities granulares;
- `access.manage`;
- revogação e regrant;
- expiração de acessos;
- conta ativa/inativa;
- isolamento cross-org;
- auditoria;
- proteção contra revogar/remover o último administrador ativo.

### Critérios de aceite

- [ ] `docs/PHASE_2_3_AUDIT.md` existe e reflete o código remoto real;
- [ ] `docs/REPO_AUDIT_2026-08-07.md` registra a reconciliação do origin desatualizado;
- [ ] testes cobrem último administrador;
- [ ] testes cobrem cross-org;
- [ ] testes cobrem revoke/regrant/expiry;
- [ ] conta inativa não consegue operar;
- [ ] organização inativa bloqueia operação institucional;
- [ ] auditoria não registra credenciais ou PII desnecessária;
- [ ] CI do HEAD da Fase 2.3 está verde;
- [ ] nenhuma feature nova foi misturada ao M1.

**Gate:** M1 precisa atingir 100% antes do M2.

---

## M2 — Scenario Core

### Objetivo

Substituir o modelo atual limitado de cenários por um núcleo versionado, escalável e preparado para vítimas individuais e grandes incidentes.

### Modelo de domínio alvo

- `Scenario`
- `ScenarioVersion`
- `ScenarioVictim`
- `VictimCohort`
- `estimated_casualty_count`

### Regras essenciais

- remover qualquer `max:10` ou equivalente de casualties;
- `estimated_casualty_count >= 1` quando informado;
- um cenário com 1.000 vítimas não cria 1.000 registros individuais por obrigação;
- vítimas individuais representam pessoas relevantes ao exercício;
- cohorts representam grupos agregados;
- cenário publicado é imutável no nível da versão;
- edição de cenário publicado gera nova versão;
- versões mantêm histórico.

### Critérios de aceite

- [ ] limite artificial de casualties eliminado em backend, frontend e testes;
- [ ] modelagem de versão implementada;
- [ ] vítimas individuais implementadas;
- [ ] cohorts implementados;
- [ ] testes de 1, 11, 100 e 1.000 vítimas estimadas;
- [ ] teste garante que 1.000 estimadas não geram 1.000 vítimas individuais automaticamente;
- [ ] migrações reversíveis e compatíveis com dados existentes;
- [ ] UI diferencia estimativa global de vítimas modeladas.

---

## M3 — Simulation Engine

### Objetivo

Permitir múltiplas execuções independentes de uma mesma versão de cenário.

### Modelo funcional alvo

`ScenarioVersion → Execution → Teams/Participants/Timeline/Events/Interventions/InstructorInjects/Resources`

### Critérios de aceite

- [ ] uma versão pode possuir múltiplas execuções;
- [ ] uma execução não altera a versão do cenário;
- [ ] equipes e participantes são associados à execução;
- [ ] timeline possui eventos ordenados temporalmente;
- [ ] intervenções são registradas com autoria e tempo;
- [ ] instructor injects podem alterar o estado da simulação sem editar o cenário original;
- [ ] recursos da execução podem ser registrados;
- [ ] isolamento institucional é preservado em todas as entidades;
- [ ] testes garantem independência entre execuções.

---

## M4 — Assessment & Debriefing

### Objetivo

Transformar a execução em aprendizagem mensurável e auditável.

### Estrutura

- objetivos;
- critérios;
- evidências observadas;
- erros críticos previstos;
- erros críticos observados;
- tempos-chave;
- avaliação por equipe e, quando necessário, por indivíduo;
- debriefing;
- plano de ação.

### Regra de linguagem avaliativa

Cada conclusão relevante deve poder ser classificada como:

1. **Fato** — observação verificável;
2. **Interpretação** — significado atribuído ao fato;
3. **Recomendação** — ação proposta.

### Critérios de aceite

- [ ] catálogo de erros críticos separado dos erros observados;
- [ ] evidências podem ser vinculadas a critérios;
- [ ] tempos importantes podem ser registrados;
- [ ] debriefing possui estrutura clara;
- [ ] plano de ação possui ação, responsável, prazo e status;
- [ ] relatório diferencia fato, interpretação e recomendação;
- [ ] avaliação individual não é obrigatória em todos os cenários.

---

## M5 — Institutional Product Layer

### Objetivo

Fazer o sistema ser compreensível e demonstrável para instituição, empresa, prefeitura, escola ou órgão público.

### Entregas

- dashboard executivo;
- dashboard de instrutor;
- filtros por organização, unidade, período e cenário;
- histórico de execuções;
- indicadores;
- relatórios PDF;
- CSV;
- templates;
- `DemoSeeder` institucional.

### Demo

Criar organização fictícia completa, sem PII real, com administradores, instrutores, avaliadores, participantes, cenários, execuções, avaliações e relatórios.

### Critérios de aceite

- [ ] demo funciona do início ao fim;
- [ ] nenhuma PII real é necessária;
- [ ] dashboards respeitam abilities e tenant isolation;
- [ ] PDF é legível e institucional;
- [ ] CSV possui colunas estáveis e documentadas;
- [ ] um visitante técnico consegue compreender o valor do produto em poucos minutos.

---

## M6 — Production Ready

### Objetivo

Eliminar lacunas de operação e segurança incompatíveis com uma demonstração institucional séria.

### Infraestrutura alvo

- PostgreSQL como banco recomendado de produção;
- Docker / Docker Compose;
- `.env.example` completo;
- healthcheck;
- logs estruturados;
- backup e restore documentados;
- CI com cobertura de banco de produção quando viável.

### Matriz mínima de segurança

Testar:

- cross-org por UUID;
- alteração manual de formulário;
- POST manual;
- privilege escalation;
- CSRF;
- session fixation;
- conta inativa;
- organização inativa;
- grant expirado;
- grant revogado;
- último administrador;
- mass assignment;
- PII em logs/auditoria.

### Critérios de aceite

- [ ] ambiente Docker sobe de forma reproduzível;
- [ ] PostgreSQL validado;
- [ ] backup e restore testáveis;
- [ ] healthcheck implementado;
- [ ] secrets não são versionados;
- [ ] CI verde;
- [ ] matriz mínima de segurança possui evidência de testes.

---

## M7 — Design System Final

### Objetivo

Consolidar uma identidade visual institucional própria com alta legibilidade, coerência e acessibilidade.

### Princípios visuais

A experiência deve transmitir:

- controle;
- clareza;
- confiança;
- prontidão;
- ciência.

Evitar:

- estética de videogame;
- excesso de neon;
- painel militar cinematográfico;
- aparência genérica de template SaaS;
- excesso de informação simultânea.

### Cor por função

- azul profundo: confiança, estrutura, instituição;
- azul/ciano moderado: tecnologia e informação;
- verde: estado seguro/concluído;
- âmbar: atenção e decisão;
- vermelho: risco crítico/erro, nunca decoração;
- off-white/cinza claro: redução de carga cognitiva.

As escolhas finais devem respeitar contraste WCAG e não tratar associações culturais de cor como universais.

### Critérios de aceite

- [ ] paleta funcional documentada;
- [ ] tokens de cor e espaçamento centralizados;
- [ ] contraste WCAG verificado;
- [ ] estados de sucesso, atenção e erro são semanticamente consistentes;
- [ ] desktop e mobile coerentes;
- [ ] navegação por teclado e focus states funcionais;
- [ ] páginas críticas possuem empty, loading, success e error states quando aplicável.

---

## M8 — Wiki Premium Elite Diamante

### Objetivo

Criar uma camada didática e institucional capaz de ensinar o produto a públicos técnicos e não técnicos.

### Regra de timing

A Wiki final só será redesenhada após estabilização funcional e visual. A Wiki existente deve ser preservada e auditada antes de qualquer substituição.

### Públicos

1. gestor/instrutor/usuário não técnico;
2. desenvolvedor/pesquisador/administrador técnico.

### Arquitetura de informação

- Home;
- Comece Aqui;
- Conceitos;
- Cenários;
- Execuções;
- Avaliação;
- Debriefing;
- Administração;
- Guia do Instrutor;
- Guia do Desenvolvedor;
- Arquitetura;
- Segurança;
- Modelo de Dados;
- Deploy;
- Testes;
- Glossário;
- FAQ;
- Troubleshooting.

### Experiência didática

A Wiki deve combinar:

- textos curtos e hierárquicos;
- cards;
- diagramas;
- fluxos visuais;
- screenshots anotados;
- exemplos fictícios;
- tabelas comparativas;
- badges de estado;
- onboarding;
- glossário;
- navegação progressiva do básico ao avançado.

### Design

A Wiki deve reutilizar a linguagem visual do M7, com psicologia das cores aplicada por função, alta legibilidade, contraste adequado e carga cognitiva controlada.

### Critérios de aceite

- [ ] auditoria da Wiki existente concluída antes do redesign;
- [ ] arquitetura de informação aprovada;
- [ ] Home explica o produto em menos de 2 minutos de leitura;
- [ ] tour de 5 minutos disponível;
- [ ] diagramas explicam o fluxo `Scenario → Version → Execution → Assessment → Debriefing`;
- [ ] guia de instrutor cobre operação sem exigir conhecimento técnico;
- [ ] guia de desenvolvedor cobre instalação, arquitetura, testes e extensão;
- [ ] navegação e contraste são acessíveis;
- [ ] Wiki e software não se contradizem.

---

## M9 — Final Forensic Audit & Release

### Objetivo

Executar auditoria final de engenharia, segurança, banco, performance, UX, produto, documentação, Wiki e demo antes da release.

### Auditoria

Revisar:

- arquitetura;
- duplicação e dívida técnica;
- segurança e OWASP;
- tenant isolation;
- PII/LGPD;
- índices e integridade referencial;
- N+1 e paginação;
- acessibilidade;
- fluxos quebrados;
- inconsistências de produto;
- README;
- Wiki;
- demo;
- CI.

### Artefatos de release

- `README.md` institucional;
- `LICENSE`;
- `SECURITY.md`;
- `CONTRIBUTING.md`;
- `CHANGELOG.md`;
- `docs/ARCHITECTURE.md`;
- `docs/DATA_MODEL.md`;
- `docs/SECURITY_MODEL.md`;
- `docs/PRIVACY_LGPD.md`;
- `docs/DEPLOYMENT.md`;
- `docs/ADMIN_GUIDE.md`;
- `docs/INSTRUCTOR_GUIDE.md`;
- `docs/SCENARIO_ENGINE.md`;
- `docs/MIGRATION_TO_TMA_PLATFORM.md`;
- screenshots;
- demo;
- release notes;
- tag `v1.0.0`.

### Critério de encerramento

O projeto só é considerado concluído quando todos os marcos M1–M9 estiverem aceitos e a release `v1.0.0 — Institutional Edition` estiver criada.

---

# Percentual de referência inicial

Este plano utiliza percentual de produto, não cobertura de código ou volume de commits.

Estado inicial estimado em 08/08/2026:

- M1: 90%
- M2: 0%
- M3: 0%
- M4: 10%
- M5: 20%
- M6: 35%
- M7: 25%
- M8: 5%
- M9: 0%

Estimativa global ponderada inicial: **aproximadamente 35% concluído**.

A porcentagem será revisada ao final de cada marco com base em critérios de aceite efetivamente cumpridos.

---

# Regras de execução Premium Elite Diamante

1. nenhum marco é dado como concluído sem evidência;
2. nenhuma feature fora do escopo entra antes do `v1.0.0`;
3. nenhuma alteração destrutiva em Git sem necessidade demonstrada;
4. nenhum merge em `main` sem CI e revisão do marco correspondente;
5. mudanças funcionais relevantes devem ter testes;
6. documentação deve ser atualizada junto com o comportamento que descreve;
7. segurança e tenant isolation têm prioridade sobre conveniência;
8. a Wiki será uma experiência didática de alto nível, mas somente após estabilização do produto;
9. o projeto Laravel será congelado após `v1.0.0` como Institutional Edition, evitando crescimento infinito;
10. toda evolução posterior será avaliada contra a estratégia maior da TMA Platform.

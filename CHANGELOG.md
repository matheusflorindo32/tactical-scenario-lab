# Changelog

Todas as mudanças notáveis neste projeto são documentadas aqui.

O histórico abaixo preserva o registro inicial `0.1.0` já existente no repositório e descreve a linha **release-ready ainda não versionada** construída pelos milestones posteriores. Nenhuma nova versão semântica é declarada pelo M9.

## [Unreleased] — Release-ready line

### M1 — Core scenario lifecycle
- Estrutura inicial do produto e fluxo determinístico de cenários.
- Criação, configuração e execução de cenários com base institucional.

### M2 — Authentication & access foundations
- Autenticação e sessão deixaram de ser trabalho futuro e passaram a integrar o produto.
- Contexto autenticado tornou-se pré-condição das superfícies institucionais.

### M3 — Multi-organization governance
- Pessoas, organizações e governança de acesso foram incorporadas ao fluxo.
- Isolamento por organização e autorização backend passaram a ser invariantes do produto.

### M4 — Versioned scenarios & operational execution
- Cenários passaram a operar por versões publicadas/rascunho.
- Execuções, equipes, recursos, injects e timeline foram consolidados no domínio operacional.

### M5 — Assessment, reporting & dashboards
- Assessment/debrief estruturado, histórico, dashboards e relatórios institucionais passaram a compor a verdade operacional.
- `Scenario.score` deixou de ser a métrica canônica de apresentação institucional.

### M6 — Production & Database Hardening
- PostgreSQL tornou-se o banco de produção suportado.
- Preflight, liveness/readiness, least-privilege runtime role, rollback/reapply e invariantes de concorrência foram incorporados ao CI.
- Guards PostgreSQL reforçaram isolamento, histórico e imutabilidade de artefatos publicados/finalizados.

### M7 — Operational Command Center
- Shell autenticado, sidebar canônica, dashboards orientados a atenção/risco, cockpit de execução e assessment workbench foram consolidados.
- Design system, low-light local ao navegador e contratos de acessibilidade foram endurecidos.

### M8 — Knowledge & Documentation Center
- Base de Conhecimento autenticada, Git-versioned e read-only em runtime foi integrada ao produto.
- Busca determinística, Markdown seguro, TOC, relacionados, contextual help e governança de conteúdo passaram a ser verificados em CI.

### M9 — Release & Final Product Hardening
- Metadata, segurança, locale e políticas de release foram alinhados ao produto atual.
- Dependency audits passaram a bloquear release no CI (`composer audit --locked` e `npm audit --audit-level=high`).
- Container de referência passou a usar PostgreSQL, frontend build determinístico, runtime não-root e migration separada do processo web.
- Pipeline foi normalizado para a linha `main`, com actions atuais, cacheability Laravel e preservação dos hardening gates M6.
- Documentação de release/recovery e auditoria final passam a fechar a linha release-ready por SHA/CI, sem fabricar uma nova tag.

## [0.1.0] — 2026-08-05

### Adicionado
- Modelo `Scenario` e migration `create_scenarios_table`.
- Formulário de configuração de cenário.
- Gerador determinístico (`App\Services\ScenarioGenerator`).
- Tela de execução do cenário.
- Fluxo inicial de avaliação e debriefing.
- Endpoint de saúde `GET /health`.
- Dockerfile PHP-CLI 8.4 com SQLite, posteriormente substituído pelo contrato de produção M9.
- Workflow inicial de CI (GitHub Actions), posteriormente endurecido pelos milestones M6–M9.
- Documentação de produto (`docs/PRODUCT.md`) e backlog (`docs/BACKLOG.md`).
- README, licença MIT, política de segurança, guia de contribuição e templates de colaboração.

[Unreleased]: https://github.com/matheusflorindo32/tactical-scenario-lab/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/matheusflorindo32/tactical-scenario-lab/releases/tag/v0.1.0

# Changelog

Todas as mudanças notáveis neste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto adota [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Planejado (P1)
- Autenticação de instrutores
- Impressão limpa da ficha do cenário
- Templates de cenário pré-configurados
- Checklist MARCH com pontuação
- Seeds demonstrativos

## [0.1.0] — 2026-08-05

### Adicionado
- Modelo `Scenario` e migration `create_scenarios_table`
- Formulário de configuração de cenário
- Gerador determinístico (`App\Services\ScenarioGenerator`)
- Tela de execução do cenário
- Fluxo de avaliação e debriefing
- Endpoint de saúde `GET /health`
- Dockerfile PHP-CLI 8.4 com SQLite
- Workflow de CI (GitHub Actions) com PHPUnit
- Documentação de produto (`docs/PRODUCT.md`) e backlog (`docs/BACKLOG.md`)
- README com padrão editorial, LICENSE MIT, SECURITY.md, CONTRIBUTING.md
- Templates de Pull Request e Issue

[Unreleased]: https://github.com/matheusflorindo32/tactical-scenario-lab/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/matheusflorindo32/tactical-scenario-lab/releases/tag/v0.1.0

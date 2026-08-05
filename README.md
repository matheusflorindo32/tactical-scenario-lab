# Tactical Scenario Lab

**MVP web para instrutores de APH e treinamento operacional configurarem, executarem e debriefarem cenários de forma padronizada e reproduzível.**

[![CI](https://github.com/matheusflorindo32/tactical-scenario-lab/actions/workflows/tests.yml/badge.svg)](https://github.com/matheusflorindo32/tactical-scenario-lab/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-%5E13.0-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Sumário

- [Contexto](#contexto)
- [Proposta de valor](#proposta-de-valor)
- [Stack técnica](#stack-técnica)
- [Requisitos](#requisitos)
- [Instalação rápida](#instalação-rápida)
- [Uso](#uso)
- [Endpoints principais](#endpoints-principais)
- [Testes](#testes)
- [Docker](#docker)
- [Arquitetura](#arquitetura)
- [Roadmap](#roadmap)
- [Contribuindo](#contribuindo)
- [Segurança](#segurança)
- [Licença](#licença)

---

## Contexto

Instrutores de Atendimento Pré-Hospitalar (APH), primeiros socorros e treinamento operacional dedicam tempo desproporcional para montar cenários, checklists e debriefings em documentos separados (Word, PDF, planilhas). Isso dificulta padronização entre turmas, perde histórico e reduz a rastreabilidade da avaliação.

O **Tactical Scenario Lab** concentra configuração, execução e avaliação de um cenário em um fluxo web único, com geração determinística de objetivos de aprendizagem, ações esperadas e erros críticos a evitar. O critério de sucesso do piloto é: **um instrutor consegue gerar e finalizar um cenário em menos de cinco minutos, sem treinamento prévio na ferramenta.**

Detalhes completos em [docs/PRODUCT.md](docs/PRODUCT.md) e [docs/BACKLOG.md](docs/BACKLOG.md).

## Proposta de valor

| Antes | Com o Tactical Scenario Lab |
|---|---|
| Cenário em Word, checklist em PDF, debrief em WhatsApp | Um único fluxo web com histórico |
| Cada instrutor monta do zero | Gerador determinístico com base MARCH |
| Avaliação sem rastreabilidade | Score e debrief notes persistidos |
| Sem versão auditável | Migrations, testes e CI públicos |

## Stack técnica

- **Backend:** PHP 8.3+ · Laravel 13
- **Persistência:** SQLite (padrão do MVP; PostgreSQL/MySQL suportados via config)
- **Frontend:** Blade + Vite (Tailwind opcional)
- **Testes:** PHPUnit 12 (Feature + Unit)
- **Ferramentas de qualidade:** Pint (formatação), Pail (logs), Faker (seeders)
- **Deploy:** Docker (Dockerfile PHP-CLI); alvos previstos Railway e Render

## Requisitos

- PHP **≥ 8.3** com extensões `pdo_sqlite`, `mbstring`, `openssl`
- Composer 2.x
- Node.js 20+ e npm (para assets Vite)
- Git

## Instalação rápida

```bash
git clone https://github.com/matheusflorindo32/tactical-scenario-lab.git
cd tactical-scenario-lab

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

npm install
npm run build

php artisan serve
```

Acesse **http://localhost:8000**.

Alternativa em um comando:

```bash
composer run setup   # roda install + key + migrate + npm build
composer run dev     # sobe server + queue + logs + vite em paralelo
```

## Uso

1. Acesse a raiz do site → é redirecionado para a listagem de cenários.
2. **Criar cenário** — informe ambiente, nível de ameaça, mecanismo de lesão, número de vítimas e recursos disponíveis.
3. O sistema gera **objetivos de aprendizagem**, **ações esperadas** (com ajuste automático para ameaça ativa) e **erros críticos**.
4. **Executar** — marca início da simulação.
5. **Avaliar** — registra score e notas de debrief.

## Endpoints principais

| Método | Rota | Descrição |
|---|---|---|
| GET | `/` | Redireciona para listagem |
| GET | `/scenarios` | Lista cenários |
| GET | `/scenarios/create` | Formulário de criação |
| POST | `/scenarios` | Persiste cenário |
| GET | `/scenarios/{scenario}` | Mostra cenário |
| POST | `/scenarios/{scenario}/execute` | Marca execução |
| POST | `/scenarios/{scenario}/evaluate` | Registra avaliação e debrief |
| GET | `/health` | Healthcheck JSON `{"status":"ok"}` |

## Testes

```bash
composer test
# ou
php artisan test
```

Cobertura inicial focada no fluxo principal (`tests/Feature/ScenarioFlowTest.php`).

## Docker

```bash
docker build -t tactical-scenario-lab .
docker run -p 8080:8080 tactical-scenario-lab
```

Aplicação sobe em **http://localhost:8080**, com migrations aplicadas no boot.

## Arquitetura

```
app/
├── Http/Controllers/ScenarioController.php   # camada web
├── Models/Scenario.php                        # persistência
├── Services/ScenarioGenerator.php             # regra determinística
└── ...
database/migrations/
└── 2026_08_05_000001_create_scenarios_table.php
routes/web.php                                 # 7 rotas, incluindo /health
docs/PRODUCT.md                                # visão do produto
docs/BACKLOG.md                                # P0 / P1 / P2
```

Princípios adotados:

- **Determinismo** — o gerador não usa aleatoriedade nem IA; mesmo input produz mesmo cenário. Facilita padronização entre turmas.
- **Fluxo em três estados** — `draft` → `execute` → `evaluate`, refletidos no campo `status`.
- **Base clínica** — objetivos e ações espelham o protocolo **MARCH** (Massive hemorrhage, Airway, Respiration, Circulation, Hypothermia/Head injury), consenso operacional em cuidados táticos em combate (TCCC).

> Referência do protocolo MARCH: Committee on Tactical Combat Casualty Care (CoTCCC). *TCCC Guidelines for Medical Personnel.* Deployed Medicine, 2024. https://deployedmedicine.com/market/11

## Roadmap

Extrato de [docs/BACKLOG.md](docs/BACKLOG.md):

- **P0 (piloto deployável)** — geração determinística ✓, avaliação ✓, healthcheck ✓, Docker ✓, GitHub Actions ✓, deploy Railway/Render ⏳
- **P1 (demonstração profissional)** — autenticação, impressão da ficha, templates, MARCH pontuado, seeds
- **P2 (produto)** — organizações e turmas, indicadores por equipe, export PDF/CSV, auditoria

## Contribuindo

Ver [CONTRIBUTING.md](CONTRIBUTING.md). Em resumo: abra issue antes de PR para mudanças estruturais; PRs pequenos e focados; testes acompanham o código.

## Segurança

Encontrou uma falha? Não abra issue pública — ver [SECURITY.md](SECURITY.md) para o canal privado.

## Licença

Distribuído sob a licença **MIT**. Ver [LICENSE](LICENSE) para detalhes.

---

**Autor:** Matheus Florindo de · [GitHub](https://github.com/matheusflorindo32)

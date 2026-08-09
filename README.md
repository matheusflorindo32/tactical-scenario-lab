# Tactical Scenario Lab

**Plataforma web institucional para configurar, executar, avaliar e debriefar cenários de APH e treinamento operacional com histórico rastreável.**

[![CI](https://github.com/matheusflorindo32/tactical-scenario-lab/actions/workflows/tests.yml/badge.svg)](https://github.com/matheusflorindo32/tactical-scenario-lab/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-%5E13.0-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Estado do produto

M1–M8 consolidaram o domínio, hardening de produção, experiência operacional e Knowledge Center. O M9 fecha a linha **release-ready** com segurança/dependency audits, paridade de container, CI de release, cacheability, localização e procedimentos de release/recovery.

A identidade de um release é o **SHA exato testado**, não apenas o nome de uma branch. Consulte [`docs/RELEASE.md`](docs/RELEASE.md) para o procedimento operacional e [`CHANGELOG.md`](CHANGELOG.md) para o histórico por milestone.

## O que o produto cobre

O Tactical Scenario Lab concentra em um único fluxo institucional:

- criação determinística de cenários;
- definições versionadas com publicação e revisão controladas;
- templates institucionais;
- execução com equipes, participantes, recursos, injects e timeline append-only;
- avaliação estruturada com rubrica, evidências, erros críticos e tempos-chave;
- debrief com fatos, interpretações, recomendações e plano de ação;
- dashboards operacional e executivo;
- histórico, CSV e PDF institucional;
- pessoas, organizações e governança de acesso multi-organização;
- **Knowledge & Documentation Center** autenticado, pesquisável e contextual para orientar o uso do próprio produto.

O critério original do piloto permanece documentado em [`docs/PRODUCT.md`](docs/PRODUCT.md). O backlog inicial em [`docs/BACKLOG.md`](docs/BACKLOG.md) é histórico e não representa sozinho o estado atual da arquitetura.

## Stack técnica

- **Backend:** PHP 8.3+ · Laravel 13
- **Frontend:** Blade · Tailwind CSS v4 · Alpine.js · Vite 7
- **Produção:** PostgreSQL, com preflight fail-closed e runtime role de menor privilégio
- **Desenvolvimento/regressão:** SQLite continua suportado
- **Testes:** PHPUnit 12 em SQLite e PostgreSQL 16 no CI
- **Qualidade:** Pint · `composer validate --strict` · build Vite · Composer/npm security audit · config/route cacheability
- **Relatórios:** Dompdf para PDF institucional
- **Conhecimento:** catálogo PHP allowlisted + Markdown Git-versioned em `resources/knowledge/articles/`
- **Container de referência:** frontend build determinístico, `pdo_pgsql`, processo runtime sem migrations automáticas e usuário não-root `app`

## Requisitos locais

- PHP **≥ 8.3**
- Composer 2.x
- Node.js 22 e npm para reproduzir a linha de CI/frontend atual
- SQLite para setup local padrão, ou PostgreSQL quando quiser reproduzir o ambiente de produção
- extensões PHP requeridas pelo Laravel/driver de banco escolhido

## Instalação local rápida

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

A aplicação local fica disponível, por padrão, em `http://localhost:8000`.

Também existem os scripts Composer do projeto:

```bash
composer run setup
composer run dev
```

## Configuração de banco

O `.env.example` mantém SQLite para desenvolvimento local:

```dotenv
DB_CONNECTION=sqlite
```

Produção usa PostgreSQL e deve configurar explicitamente host, banco, usuário runtime e TLS. `DB_SSLMODE=disable` é recusado pela validação de produção; `verify-full` é a opção preferida quando o provedor oferece CA/hostname verificáveis.

Exemplo de direção de configuração:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=your-managed-postgres-host
DB_PORT=5432
DB_DATABASE=tactical_scenario
DB_USERNAME=tactical_runtime
DB_PASSWORD=use-a-secret-manager
DB_SSLMODE=verify-full
```

A chave `PII_FINGERPRINT_KEY` deve ser estável e independente de `APP_KEY`, pois participa da busca exata/duplicidade protegida de PII.

## Fluxo operacional

1. Autentique-se e opere dentro da organização ativa.
2. Crie um cenário ou reutilize um template.
3. Revise a versão em rascunho e publique a definição.
4. Crie uma execução a partir da versão publicada.
5. Configure equipe/participantes/recursos e conduza a operação pelo cockpit.
6. Registre eventos e injects durante a execução.
7. Conclua a execução e abra a avaliação.
8. Preencha rubrica, evidências, erros críticos, tempos-chave e debrief.
9. Finalize a avaliação; o conteúdo histórico fica congelado, enquanto o status autorizado das ações pode continuar evoluindo.
10. Consulte dashboards, histórico e relatórios institucionais.
11. Use a Base de Conhecimento para compreender o comportamento da interface e os invariantes do produto sem sair da experiência autenticada.

## Rotas principais

As famílias principais hoje são:

| Área | Exemplos |
|---|---|
| Sessão | `/login`, `/logout` |
| Dashboards | `/dashboard`, `/dashboard/executive` |
| Cenários | `/scenarios`, `/scenarios/{scenario}` |
| Templates | `/scenario-templates` |
| Execuções | `/executions/{execution}` + transições `start`, `complete`, `cancel` |
| Assessment | `/assessments/{assessment}` + critérios/evidências/debrief/plano de ação |
| Histórico/relatórios | `/history/executions`, `/reports/executions.csv`, PDF por execução |
| Gestão | `/people`, `/organizations`, `/access` |
| Conhecimento | `/knowledge`, `/knowledge/{slug}` |
| Health | `/health`, `/health/live`, `/health/ready` |

A fonte da verdade das rotas é [`routes/web.php`](routes/web.php).

## Interface M7 — Operational Command Center

A experiência autenticada usa:

- sidebar como navegação canônica e ability-aware;
- topbar contextual com organização ativa e conta;
- dashboard do instrutor ordenado por atenção operacional;
- visão executiva orientada primeiro a risco/pendência;
- workspace de cenários baseado na versão vigente, não em score legado;
- cockpit da execução com timeline como verdade cronológica append-only;
- assessment/debrief como workbench navegável;
- modo institucional **low-light** opcional, salvo somente no `localStorage` do navegador.

## M8 — Knowledge & Documentation Center

O M8 integra a documentação do produto à experiência autenticada sem criar uma segunda fonte de verdade clínica ou operacional.

A arquitetura é **Git-versioned** e read-only em runtime:

- `config/knowledge.php` é o catálogo allowlisted de slugs, arquivos, categorias, audiências, relações e contextos;
- os artigos vivem como Markdown versionado em `resources/knowledge/articles/`;
- slugs recebidos pela rota nunca são interpretados como caminhos de arquivo;
- o repositório restringe fontes à pasta allowlisted e falha de forma fechada quando a definição é inválida;
- Markdown é renderizado com HTML cru removido e links inseguros bloqueados;
- títulos H2/H3 recebem âncoras determinísticas e TOC quando há profundidade útil;
- a busca é server-side, case/accent-insensitive e possui ranking determinístico por título, tags, resumo/categoria e corpo;
- `contextual_for` no catálogo é a fonte única dos links **Como usar esta tela**;
- as rotas de conhecimento exigem autenticação e conta ativa;
- o conteúdo inicial explica uso do produto, versionamento, cockpit, avaliação/debrief, histórico/relatórios e governança.

O M8 permanece **sem CMS**, sem editor WYSIWYG, sem upload livre, sem nova persistência de leitura e **sem IA/RAG**. A Base de Conhecimento não prescreve conduta clínica ou tática autônoma.

## M9 — Release & Final Product Hardening

O M9 não adiciona um novo domínio funcional. Ele endurece o pacote que será operado:

- `SECURITY.md`, `.env.example`, metadata Composer e locale descrevem o produto atual;
- CI bloqueia advisories com `composer audit --locked` e `npm audit --audit-level=high`;
- o workflow principal representa apenas a linha `main` e preserva SQLite, PostgreSQL 16, least-privilege, rollback/reapply, concorrência, build e Pint;
- Laravel `config:cache` e `route:cache` são verificados no CI;
- o container de referência usa PostgreSQL, bundle frontend pré-construído, usuário não-root e não executa migrations no startup web;
- release, rollback, PITR e admissão de tráfego são descritos em [`docs/RELEASE.md`](docs/RELEASE.md) e [`docs/PRODUCTION.md`](docs/PRODUCTION.md).

O M9 não escolhe um provedor de deploy/APM e não fabrica nova tag sem política/versionamento inequívocos.

O design system está documentado em [`docs/DESIGN_SYSTEM.md`](docs/DESIGN_SYSTEM.md).

## Testes e gates de qualidade

Localmente:

```bash
composer test
vendor/bin/pint --test
npm run build
composer audit --locked
npm audit --audit-level=high
```

O GitHub Actions valida, entre outros gates definidos no workflow:

- metadados Composer;
- Composer/npm security audits;
- build dos assets;
- cacheability de configuração/rotas Laravel;
- migrations do zero;
- suíte PHPUnit em SQLite;
- suíte PHPUnit em PostgreSQL 16;
- provisionamento do runtime role de menor privilégio;
- rollback/reapply dos guards de banco;
- repetição dos invariantes de concorrência;
- Pint;
- contratos de segurança, busca e integridade do Knowledge Center;
- contratos de release M9.

## Invariantes institucionais importantes

- tenant/organização ativa é derivado do contexto autenticado, não de `organization_id` livre do cliente;
- versões publicadas não têm a definição reescrita;
- a timeline histórica de execução é append-only;
- avaliações finalizadas preservam verdade histórica;
- conteúdo das ações fica congelado após finalização, mas transições de status autorizadas permanecem possíveis;
- dashboards e histórico usam execução/assessment como verdade operacional, não `Scenario.score` legado;
- relatórios seguem autorização e contexto da organização ativa;
- interface ability-aware não substitui autorização backend;
- a Base de Conhecimento é global, read-only e não contém conteúdo controlado por tenant;
- conteúdo de conhecimento não pode ampliar autorização nem substituir protocolos institucionais;
- migrations de produção e runtime web/queue usam identidades distintas.

## Documentação técnica relevante

- [`docs/PRODUCT.md`](docs/PRODUCT.md) — problema e objetivo do produto
- [`docs/DESIGN_SYSTEM.md`](docs/DESIGN_SYSTEM.md) — tokens, componentes e UX
- [`docs/PRODUCTION.md`](docs/PRODUCTION.md) — operação/deploy de produção
- [`docs/RELEASE.md`](docs/RELEASE.md) — release, admissão, rollback e recovery
- [`CHANGELOG.md`](CHANGELOG.md) — histórico por milestone/linha release-ready
- [`docs/REPORTING.md`](docs/REPORTING.md) — semântica de reporting
- [`docs/DEMO.md`](docs/DEMO.md) — ambiente/dados demonstrativos
- [`docs/PHASE_M8_AUDIT.md`](docs/PHASE_M8_AUDIT.md) — auditoria forense do Knowledge Center
- [`docs/superpowers/specs/`](docs/superpowers/specs/) — specs por milestone
- [`docs/superpowers/plans/`](docs/superpowers/plans/) — planos de implementação

## Segurança

Encontrou uma vulnerabilidade? Não abra issue pública. Consulte [`SECURITY.md`](SECURITY.md) para o canal apropriado.

## Contribuindo

Consulte [`CONTRIBUTING.md`](CONTRIBUTING.md). Mudanças estruturais devem preservar testes, isolamento de tenant, contratos históricos, integridade do catálogo de conhecimento e contratos de release.

## Licença

MIT. Consulte [`LICENSE`](LICENSE).

---

**Autor:** Matheus Florindo de · [GitHub](https://github.com/matheusflorindo32)

# Guia de contribuição

Obrigado por considerar contribuir com o **Tactical Scenario Lab**. Este projeto é um MVP em evolução — a intenção é manter o código simples, testável e diretamente ligado ao problema do instrutor.

## Antes de abrir um PR

1. **Abra uma issue** descrevendo o problema ou a proposta. Isso evita retrabalho.
2. Confirme que sua mudança se encaixa no [BACKLOG.md](docs/BACKLOG.md). Mudanças fora do roadmap precisam de justificativa.
3. **PRs pequenos e focados** — uma responsabilidade por PR.

## Fluxo de trabalho

```bash
# 1. Fork e clone
git clone https://github.com/<seu-usuario>/tactical-scenario-lab.git
cd tactical-scenario-lab

# 2. Instale
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# 3. Crie uma branch
git checkout -b feat/nome-curto-do-que-faz

# 4. Codifique + teste
composer test

# 5. Formate
./vendor/bin/pint

# 6. Commit em português, imperativo curto
git commit -m "feat: adiciona pontuação MARCH no debrief"

# 7. Push e abra PR
```

## Padrão de commits

Baseado em [Conventional Commits](https://www.conventionalcommits.org/pt-br/) simplificado:

- `feat:` — nova funcionalidade
- `fix:` — correção de bug
- `refactor:` — mudança interna sem afetar comportamento
- `test:` — adiciona/ajusta testes
- `docs:` — só documentação
- `chore:` — infraestrutura, deps, CI
- `perf:` — melhoria de performance
- `style:` — formatação (Pint)

## Testes

- **Toda nova regra de negócio precisa de teste.** O MVP tem cobertura pequena; a barra é manter e crescer.
- Testes ficam em `tests/Feature` (fluxos web) ou `tests/Unit` (serviços puros).
- Rode `composer test` antes de qualquer push.

## Estilo de código

- PSR-12 via **Laravel Pint** (`./vendor/bin/pint`).
- Sem comentários óbvios; o código deve se explicar. Docstrings apenas quando o "porquê" não fica claro pelo nome.
- Métodos curtos, uma responsabilidade.

## Segurança

Vulnerabilidades **não** vão para issues públicas. Ver [SECURITY.md](SECURITY.md).

## Código de conduta

Seja respeitoso, direto e técnico. Discordância sobre implementação é bem-vinda; ataque pessoal, não.

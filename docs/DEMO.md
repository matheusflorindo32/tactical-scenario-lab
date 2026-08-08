# M5 — Demonstração institucional

Este roteiro prepara um ambiente **exclusivamente fictício** para demonstrar o produto institucional do Tactical Scenario Lab. Nenhum nome, e-mail, cenário, unidade ou ocorrência deste conjunto representa pessoa, organização ou fato real.

## Proteção de ambiente

`Database\Seeders\DemoSeeder` recusa execução quando `app()->environment('production')` é verdadeiro e lança `LogicException` antes de criar qualquer registro.

O `DemoSeeder` não é chamado pelo `DatabaseSeeder` padrão. A carga precisa ser solicitada explicitamente.

## Preparação local

Em um ambiente local/de demonstração já configurado:

```bash
php artisan migrate:fresh --force
php artisan db:seed --class=DemoSeeder
```

Executar o mesmo `DemoSeeder` novamente é seguro: a organização fictícia de demonstração funciona como marcador transacional e o grafo não é duplicado.

## Credenciais fictícias

- E-mail: `demo.manager@example.test`
- Senha: `Demo-M5-2026!`
- Organização: `Centro Aurora de Simulação Integrada`

O usuário demo recebe as abilities institucionais necessárias para navegar pelos fluxos M1–M5. As credenciais existem somente para ambientes de demonstração e nunca devem ser reutilizadas em produção.

## O que é criado

A carga demonstra, de forma conectada:

1. uma organização fictícia ativa;
2. duas unidades fictícias;
3. duas pessoas fictícias com vínculos institucionais separados por unidade;
4. três cenários publicados com definições versionadas;
5. vítimas/coortes agregados em um cenário multivítimas;
6. um template institucional criado de uma versão publicada;
7. uma execução concluída com participantes e atribuição histórica congelada;
8. uma execução em andamento para alimentar a fila operacional;
9. timeline observacional;
10. avaliação M4 finalizada com critérios, evidências e resultado calculado pelo domínio;
11. debrief estruturado em fato, interpretação e recomendação;
12. plano de ação com responsável institucional fictício e prazo.

## Percurso recomendado

### 1. Dashboard do instrutor

Entre com o usuário demo e abra `/dashboard`.

Confirme a presença da execução em andamento e das filas operacionais do contexto ativo.

### 2. Dashboard executivo

Abra `/dashboard/executive`.

As métricas devem refletir `ScenarioExecution` e `ExecutionAssessment` M4. A avaliação finalizada do cenário multivítimas alimenta a taxa de resultado sem depender de `Scenario.score` legado.

### 3. Histórico institucional

Abra `/history/executions`.

Use os filtros de cenário, período e unidade. A execução concluída contém participantes de `Núcleo Alfa` e `Núcleo Bravo`, preservados por snapshot histórico.

### 4. Cenários e versões

Abra `/scenarios` e visite:

- `Incidente Multivítimas — Estação Aurora`;
- `Ameaça Ativa — Complexo Horizonte`;
- `Evacuação Técnica — Edifício Boreal`.

As versões publicadas permanecem congeladas para referência histórica.

### 5. Templates

Abra `/scenario-templates`.

Use `Template — Incidente Multivítimas` para criar um novo cenário. O resultado deve ser uma nova identidade de cenário com versão 1 em `draft`, novos UUIDs e sem copiar execuções, avaliações, evidências ou debriefs da fonte.

### 6. Avaliação e debrief

Na execução concluída do cenário `Incidente Multivítimas — Estação Aurora`, consulte a avaliação finalizada.

Verifique os critérios, evidências ligadas à timeline, resultado calculado, debrief estruturado e action item. O conteúdo finalizado é histórico e não deve voltar ao estado editável.

### 7. Reporting

No histórico institucional, valide:

- exportação CSV institucional;
- PDF de uma execução autorizada;
- filtros tenant-safe;
- ausência de contatos e identificadores pessoais no PDF.

## Reinicialização

Para restaurar o estado inicial da demonstração em ambiente descartável:

```bash
php artisan migrate:fresh --force
php artisan db:seed --class=DemoSeeder
```

Nunca execute `migrate:fresh` contra um banco que contenha dados que precisem ser preservados.

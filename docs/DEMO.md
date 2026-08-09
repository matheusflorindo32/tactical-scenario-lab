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
5. vítimas individuais e cohort agregado em um cenário multivítimas;
6. um template institucional criado de uma versão publicada;
7. uma execução concluída com participantes de múltiplas unidades e atribuição histórica congelada;
8. uma execução em andamento para alimentar a fila operacional;
9. uma segunda execução concluída com avaliação M4 ainda em `draft`;
10. timeline observacional vinculada às avaliações;
11. uma avaliação M4 finalizada com critérios, evidências e resultado calculado pelo domínio;
12. uma ocorrência crítica observada vinculada ao catálogo/versionamento da execução;
13. um key time cujo tempo decorrido é calculado pelo domínio a partir do início da execução;
14. debrief estruturado em fato, interpretação e recomendação;
15. dois action items: um `open` e outro transicionado explicitamente para `in_progress` após a finalização da avaliação.

Esse grafo completo é protegido por teste de integração determinístico. O gate forense do M5 confirmou o contrato ampliado no CI run #662, após um RED deliberado no run #661.

## Percurso recomendado

### 1. Dashboard do instrutor

Entre com o usuário demo e abra `/dashboard`.

Confirme:

- uma execução `running`;
- uma execução concluída com avaliação ainda em `draft`;
- action items em estados `open` e `in_progress`;
- filas operacionais restritas à organização ativa.

### 2. Dashboard executivo

Abra `/dashboard/executive`.

As métricas devem refletir `ScenarioExecution` e `ExecutionAssessment` M4. A avaliação finalizada do cenário multivítimas alimenta a taxa de resultado sem depender de `Scenario.score` legado.

No ranking de erros críticos, confirme que a ocorrência exibida vem do registro observado na avaliação — e não apenas do catálogo previsto no cenário.

### 3. Histórico institucional

Abra `/history/executions`.

Use os filtros de cenário, período e unidade. A execução concluída do cenário multivítimas contém participantes de `Núcleo Alfa` e `Núcleo Bravo`, preservados por snapshot histórico. A execução deve aparecer uma única vez mesmo quando há múltiplas unidades associadas.

### 4. Cenários e versões

Abra `/scenarios` e visite:

- `Incidente Multivítimas — Estação Aurora`;
- `Ameaça Ativa — Complexo Horizonte`;
- `Evacuação Técnica — Edifício Boreal`.

As versões publicadas permanecem congeladas para referência histórica.

### 5. Templates

Abra `/scenario-templates`.

Use `Template — Incidente Multivítimas` para criar um novo cenário. O resultado deve ser uma nova identidade de cenário com versão 1 em `draft`, novos UUIDs e sem copiar execuções, avaliações, evidências ou debriefs da fonte.

### 6. Avaliação finalizada e debrief

Na execução concluída do cenário `Incidente Multivítimas — Estação Aurora`, consulte a avaliação finalizada.

Verifique:

- critérios e notas estruturadas;
- evidências ligadas à timeline;
- resultado calculado;
- ocorrência crítica observada;
- key time;
- debrief em fato, interpretação e recomendação;
- action items `open` e `in_progress`.

O conteúdo da avaliação finalizada é histórico e não deve voltar ao estado editável. A evolução do status dos action items segue a máquina de estados explícita do domínio.

### 7. Avaliação em rascunho

No cenário `Ameaça Ativa — Complexo Horizonte`, abra a execução concluída que ainda possui avaliação em `draft`.

Ela existe para demonstrar a fila de trabalho M4/M5 e a diferença entre uma execução concluída e uma avaliação ainda não finalizada.

### 8. Reporting

No histórico institucional, valide:

- exportação CSV institucional;
- PDF de uma execução autorizada;
- filtros tenant-safe;
- atribuição histórica de unidade;
- ausência de contatos e identificadores pessoais no PDF;
- comportamento multi-unidade sem duplicação da execução.

## Reinicialização

Para restaurar o estado inicial da demonstração em ambiente descartável:

```bash
php artisan migrate:fresh --force
php artisan db:seed --class=DemoSeeder
```

Nunca execute `migrate:fresh` contra um banco que contenha dados que precisem ser preservados.

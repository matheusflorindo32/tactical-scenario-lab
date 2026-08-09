# Cenários e versionamento

Cenários são a porta de entrada para preparar exercícios no Tactical Scenario Lab. A interface separa identidade de cenário e definição versionada para que o histórico não seja alterado silenciosamente quando um exercício evolui. Este guia descreve o comportamento do produto; decisões pedagógicas, clínicas ou táticas continuam pertencendo às referências e autoridades competentes da instituição.

## Cenário e versão não são a mesma coisa

O cenário representa o exercício como entidade persistente. A versão contém a definição utilizada em determinado momento, incluindo os campos de preparação mantidos pelo domínio. Um novo cenário começa com uma versão em rascunho. Enquanto essa versão estiver em rascunho, o fluxo de edição autorizado pode evoluir sua definição. O workspace apresenta esse ciclo explicitamente para que o usuário saiba qual estado está operando.

## Publicar congela a definição

Publicação transforma a versão em referência estável para execução. Depois de publicada, a definição não deve ser reescrita. Essa regra existe no domínio e também recebe proteção no PostgreSQL de produção. Se o cenário precisar mudar, a evolução correta é criar uma revisão que gere nova versão em rascunho, preservando a versão publicada anterior e qualquer execução que já tenha sido vinculada a ela.

## Templates reutilizam sem reescrever histórico

Templates permitem reaproveitar uma definição institucional como ponto de partida para um novo rascunho. Usar um template não cria histórico fictício nem modifica a versão que serviu de origem. Templates arquivados deixam de ser reutilizáveis conforme as regras do produto. A biblioteca de templates existe para acelerar preparação mantendo separação entre fonte reutilizável e execução real.

## Da versão publicada para a execução

Uma execução é criada a partir de uma versão publicada. Essa ligação torna explícita a definição que estava válida quando o exercício começou. Execuções diferentes podem partir da mesma versão e receber números sequenciais próprios. O cockpit passa então a registrar equipes, participantes, recursos, injects e timeline sem alterar a definição publicada que originou aquela execução.

## Como ler o workspace

Na listagem de Cenários, o estado apresentado deve ser interpretado pelo ciclo rascunho → publicado → preparar → executar → avaliar → histórico. O produto não usa o antigo `Scenario.score` como verdade institucional de avaliação. Resultados oficiais vêm das execuções e de suas avaliações estruturadas. Para entender o registro durante a atividade, continue pelo guia do Cockpit de execução.

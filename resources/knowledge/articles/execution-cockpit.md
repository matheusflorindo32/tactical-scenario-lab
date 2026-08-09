# Cockpit de execução

O Cockpit de execução reúne o estado de uma execução e os controles que o usuário autorizado pode utilizar durante o exercício. Ele descreve e registra o comportamento do produto; este guia não prescreve conduta clínica ou tática, não escolhe tratamento e não substitui protocolos institucionais. O objetivo é deixar claro onde registrar cada informação e quais partes do histórico não podem ser reescritas.

## Ciclo de vida da execução

A região superior apresenta identidade do cenário, versão utilizada, número da execução, estado e horários relevantes. Ações de iniciar, concluir ou cancelar aparecem somente quando o domínio e as abilities permitem. Esses comandos alteram o estado da execução conforme uma matriz explícita; não são atalhos visuais capazes de contornar as validações do servidor. Uma execução concluída segue para avaliação, enquanto uma execução cancelada permanece como registro do que ocorreu no sistema.

## Equipes, participantes e recursos

Equipes e participantes dão contexto institucional ao exercício. Participantes são vinculados a partir de memberships válidos e o sistema preserva atribuição histórica suficiente para que mudanças posteriores de unidade ou contexto não reescrevam o registro daquela execução. Recursos são copiados da versão publicada na criação da execução e podem evoluir conforme as regras operacionais existentes, sem alterar retroativamente a versão de cenário que serviu de origem.

## Injects e condução do exercício

Injects são elementos planejados que podem ser entregues ou cancelados dentro dos estados permitidos. A entrega de um inject durante uma execução em andamento produz o efeito histórico correspondente uma única vez. O produto possui proteção contra duplicidade e concorrência para evitar que duas ações simultâneas criem efeitos repetidos. O conteúdo do inject orienta a simulação configurada pela instituição; o sistema não decide qual conduta clínica ou tática os participantes devem adotar.

## Timeline como registro append-only

A timeline é a verdade cronológica da execução e funciona como registro append-only: novos eventos podem ser acrescentados quando o estado permite, mas eventos existentes não são editados ou apagados para “corrigir” o passado. Metadados aceitos são controlados, referências a equipes ou participantes precisam pertencer à mesma execução e o PostgreSQL de produção reforça a imutabilidade dos eventos históricos.

## Entrada para avaliação e debrief

Depois que a execução atinge o estado apropriado, o cockpit apresenta a entrada para avaliação. A avaliação utiliza a execução e sua timeline como evidência contextual, mantendo uma separação clara entre o que aconteceu e a interpretação estruturada posterior. Para entender rubrica, evidências, erros críticos, tempos-chave, debrief e finalização, consulte o guia Avaliação e debrief.

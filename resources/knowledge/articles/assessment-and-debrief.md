# Avaliação e debrief

A avaliação transforma uma execução concluída em um registro institucional estruturado. O workbench organiza critérios, evidências, ocorrências relevantes, tempos, debrief e plano de ação sem alterar a timeline que registrou o exercício. Este guia explica o funcionamento do produto e não define protocolo clínico, nota mínima institucional externa ou decisão profissional fora das regras já configuradas na aplicação.

## Rascunho de avaliação

Enquanto a avaliação está em rascunho, o avaliador autorizado pode preencher as seções disponíveis. O resumo mostra estado e pontuação calculada conforme a estrutura existente. A navegação interna usa âncoras para facilitar deslocamento entre rubrica e evidências, erros críticos, tempos-chave, debrief, plano de ação e finalização. O estado visual de rascunho indica que o conteúdo ainda pode evoluir conforme os guards do domínio.

## Rubrica, evidências e observações

Critérios representam a rubrica estruturada da avaliação. Evidências podem referenciar eventos da mesma execução, conectando a interpretação do avaliador ao registro cronológico existente. Erros críticos e tempos-chave também obedecem a relações e validações próprias. O sistema não permite usar eventos de outra execução como evidência nem aceita valores de tempo forjados fora da janela operacional registrada.

## Debrief e plano de ação

O debrief organiza fatos, interpretações e recomendações nos campos estruturados do produto. O plano de ação transforma pontos de melhoria em itens com responsável, prazo e status. Antes da finalização, conteúdo e estrutura ainda podem ser ajustados dentro das permissões. O objetivo é manter um registro rastreável do aprendizado sem misturar a descrição do que ocorreu com alterações retroativas da execução.

## Finalização é uma transição irreversível do conteúdo

A finalização exige que os invariantes previstos estejam satisfeitos. Quando concluída, a avaliação preserva snapshots de pontuação e resultado e o conteúdo histórico fica congelado. Critérios, evidências, erros críticos, tempos, debrief e conteúdo dos itens de ação não são reabertos para edição comum. Essa proteção existe no domínio e recebe reforço no PostgreSQL de produção.

## Exceção controlada: status das ações

Após a finalização, o conteúdo de um item de ação permanece congelado, mas seu status pode avançar por transições explicitamente autorizadas. Essa exceção permite acompanhar a execução do plano sem reescrever o que foi decidido no debrief original. A interface diferencia esse acompanhamento operacional do conteúdo histórico finalizado para reduzir ambiguidade.

# Pessoas, organizações e acessos

As áreas de gestão organizam quem participa da instituição e quais contextos podem ser utilizados dentro do Tactical Scenario Lab. O produto separa pessoa, vínculo organizacional, função contextual e acesso à conta para preservar histórico e reduzir confusão entre identidade humana e autorização do sistema. Este guia descreve a governança da aplicação e não substitui políticas administrativas da organização.

## Organização ativa e unidades

A organização ativa define o contexto institucional da sessão. Organizações podem conter unidades para representar estruturas internas. Uma unidade pertence à sua organização e relações de hierarquia precisam permanecer dentro desse mesmo limite. Desativar uma organização ou unidade preserva histórico em vez de apagar registros que já participaram de cenários, execuções ou atribuições anteriores.

## Pessoas e memberships

Pessoa representa o cadastro institucional do indivíduo. Documentos e contatos sensíveis usam armazenamento protegido e apresentação mascarada conforme as regras existentes. Um membership registra a ligação da pessoa com uma organização e, quando aplicável, uma unidade. Encerrar um vínculo não apaga o passado; novos usos operacionais dependem de memberships válidos no contexto exigido.

## Funções e abilities

Funções contextuais podem reunir abilities relacionadas à atuação da pessoa. Elas não substituem o acesso da conta autenticada. O sistema valida organização, vigência do membership e valores de ability aceitos antes de criar ou alterar relações. Uma função encerrada permanece como parte do histórico institucional e não deve ser recriada artificialmente para mudar fatos anteriores.

## Acesso da conta

`UserOrganizationAccess` controla quais organizações uma conta pode utilizar e quais abilities estão ativas naquele contexto. Concessão, alteração, expiração e revogação possuem guards próprios e eventos de auditoria. O sistema protege situações críticas, como remover o último administrador de acesso válido. Uma conta inativa não pode continuar operando apenas porque uma sessão antiga ainda existe.

## Interface versus autorização backend

A sidebar e os botões são ability-aware para reduzir ruído e impedir que o usuário seja convidado a realizar ações não disponíveis. Essa visibilidade, porém, é apenas uma camada de UX. A segurança real continua no backend, que valida cada leitura e escrita relevante. Ocultar um link nunca transforma a interface em fronteira de autorização, e um request criado manualmente continua sujeito às mesmas regras do servidor.

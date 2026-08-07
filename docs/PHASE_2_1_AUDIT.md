# Auditoria técnica — Fase 2.1

Status: validação final em andamento  
Branch de trabalho: `feature/phase-2-1-elite`  
Origem preservada: `backup/claude-phase-2-1-wip`

## Objetivo

Consolidar a fundação de organizações, unidades e pessoas sem quebrar o MVP de cenários, mantendo cadastro progressivo, documentos opcionais, papéis contextuais, UUID público separado da chave interna e proteção realista de dados pessoais.

## Entregas consolidadas

### Fundação institucional

- organizações com criação, edição e inativação sem exclusão histórica;
- unidades hierárquicas com proteção contra autorreferência e ciclos;
- pessoas globais com cadastro mínimo válido;
- vínculos institucionais múltiplos e encerramento histórico;
- papéis contextuais por organização, com revogação preservada;
- UUID público separado do `id` interno.

### Dados pessoais

- identificadores e contatos armazenados de forma criptografada;
- fingerprints HMAC pesquisáveis com chave dedicada;
- máscaras para exibição;
- busca exata por fingerprint e parcial apenas por nomes;
- duplicidade supervisionada, sem mesclagem automática;
- migration incremental para estruturas legadas;
- fallback de chave permitido apenas em desenvolvimento e testes.

### Integridade e auditoria

- criação de pessoa, vínculo, papel, contato e documento auditada;
- atualização e inativação de pessoa, organização e unidade auditada;
- encerramento de vínculo e revogação de papel auditados;
- payload de auditoria sanitizado para não copiar PII e textos livres;
- troca de contato principal realizada em transação;
- encerramento de vínculo e revogação contextual de papéis em transação;
- ações repetidas de inativação, encerramento e revogação tratadas de forma idempotente.

### Interface

- fluxo Blade + Alpine responsivo;
- ficha operacional da pessoa com ações diretas;
- estados ativo, incompleto, inativo, encerrado e revogado claramente diferenciados;
- gestão institucional de organizações, unidades, vínculos e papéis;
- identidade visual institucional clara, sem estética de jogo.

## Catálogo de papéis e habilidades

Papéis e habilidades aceitos são definidos no backend e reutilizados pela interface. Habilidades arbitrárias enviadas manualmente são rejeitadas pela validação. Um papel só pode ser concedido quando existe vínculo ativo e sem data de encerramento com a organização.

## Estado de validação

O workflow do GitHub Actions executa:

1. Laravel Pint;
2. instalação e build Vite;
3. migrations;
4. PHPUnit em PHP 8.4.

O Pull Request deve permanecer em draft até o último commit concluir todos os checks com sucesso.

## Limitação crítica ainda aberta

A aplicação ainda não possui autenticação de usuários nem resolução de organização ativa por sessão. Consequentemente:

- não existe autorização institucional completa;
- os `FormRequest::authorize()` permanecem permissivos;
- não há garantia de isolamento multi-institucional baseada em usuário autenticado;
- o sistema não deve ser classificado como pronto para produção multi-institucional.

Essa limitação não é mascarada por filtros de formulário ou validações de relacionamento. A próxima fase deve implementar autenticação, associação usuário-pessoa, contexto institucional ativo, policies e testes de acesso cruzado antes da publicação com dados reais.

## Critérios de fechamento da Fase 2.1

- [x] cadastro progressivo de pessoas;
- [x] organizações e unidades;
- [x] documentos e contatos protegidos;
- [x] busca universal segura;
- [x] duplicidade supervisionada;
- [x] vínculos e papéis contextuais;
- [x] edição, inativação e encerramento histórico;
- [x] auditoria sanitizada;
- [x] testes automatizados dos fluxos implementados;
- [ ] último commit com CI totalmente verde;
- [ ] revisão final do Pull Request;
- [ ] autenticação e policies, programadas para a fase seguinte.

## Decisão de implantação

Permitido para desenvolvimento e homologação com dados fictícios. Não autorizado para dados pessoais reais ou operação multi-institucional até que autenticação, autorização e isolamento por organização sejam concluídos e testados.

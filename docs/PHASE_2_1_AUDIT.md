# Auditoria técnica — Fase 2.1

Status: em execução  
Branch de trabalho: `feature/phase-2-1-elite`  
Origem preservada: `backup/claude-phase-2-1-wip`

## Objetivo

Concluir a fundação de organizações, unidades e pessoas sem quebrar o MVP de cenários, mantendo documentos opcionais, papéis contextuais, UUID público separado da chave interna e proteção realista de dados pessoais.

## Estado encontrado

O trabalho parcial contém oito migrations, oito models, documentação de arquitetura, alterações amplas no fluxo de cenários e uma base visual relevante. A implementação ainda não contém o fluxo completo de pessoas, busca universal, requests, controllers, telas, prevenção de duplicidades e testes específicos da Fase 2.1.

## Classificação inicial

| Área | Situação | Decisão |
|---|---|---|
| Plano de expansão | consistente como direção | APROVEITAR e atualizar conforme a implementação real |
| Curadoria visual | útil, porém ainda conceitual | APROVEITAR com validação de licença e uso efetivo |
| Migrations de organizações e unidades | base adequada | CORRIGIR detalhes de integridade e testar rollback |
| Model `Organization` | geração manual de UUID correta, mas duplicada | REFACTOR para concern reutilizável |
| Models `Unit` e `Person` | usavam `HasUuids` com PK BIGINT | CORRIGIR imediatamente |
| Identificadores pessoais | valor bruto e normalizado em texto simples | REESCREVER estratégia antes de uso real |
| Restrição única de documentos | conflita com duplicidade não bloqueante | CORRIGIR |
| Escopo institucional | descrito, mas não implementado | BLOQUEADOR para produção multi-institucional |
| Auditoria | model e migration iniciais | CORRIGIR para sanitizar PII e admitir ator nulo |
| Alterações em cenários | extensas e misturadas ao incremento | AUDITAR antes de integrar |
| Testes da Fase 2.1 | ausentes | IMPLEMENTAR |

## Correções já iniciadas

1. Criada a trait `App\Models\Concerns\HasPublicUuid`.
2. `Organization`, `Unit` e `Person` passaram a usar UUID público separado de `id`.
3. Mantido `id` BIGINT autoincremental para relações internas.
4. Criados testes de fundação para UUID, chave primária e cadastro de pessoa sem documento.

## Riscos críticos

### PII pesquisável

`person_identifiers.value_normalized` não deve guardar CPF ou RG integral em texto simples apenas para facilitar busca. A implementação será revisada para separar valor protegido, impressão digital determinística e exibição mascarada.

### Duplicidade

A regra de negócio exige aviso e decisão humana. Um índice único rígido não pode ser a única proteção porque impediria a criação consciente de um novo registro em casos legítimos.

### Pessoa global

Sem autenticação, autorização e escopo institucional completo, a Fase 2.1 pode ser usada para desenvolvimento e homologação, mas não deve ser tratada como pronta para produção multi-institucional.

### Mistura de escopo

O commit parcial também altera fortemente cenários, dashboard, landing e layouts. Essas mudanças serão mantidas apenas se os testes de regressão e a auditoria funcional demonstrarem que pertencem ao incremento e não degradam o MVP.

## Próximos blocos

1. Revisar todas as migrations e models.
2. Corrigir a estratégia de PII e normalização.
3. Implementar factories, requests, services e policies mínimas.
4. Implementar cadastro rápido e progressivo.
5. Implementar busca universal segura.
6. Implementar detecção não bloqueante de duplicidades.
7. Implementar interface Blade + Alpine responsiva.
8. Ampliar testes de segurança e regressão.
9. Atualizar documentação e roteiro manual.
10. Manter Pull Request em draft até build e testes verdes.

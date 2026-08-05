# Política de Segurança

## Versões suportadas

Enquanto o projeto está em fase de MVP, apenas a branch `main` recebe correções de segurança.

| Versão | Suportada |
|---|---|
| main (HEAD) | ✅ |
| tags anteriores | ❌ |

## Reportando uma vulnerabilidade

**Não abra issue pública.** Vulnerabilidades devem ser reportadas em canal privado:

1. Envie e-mail para **matheusdideusf@gmail.com** com o assunto `[SECURITY] tactical-scenario-lab`
2. Descreva:
   - O que é a falha
   - Como reproduzir (passo a passo)
   - Impacto potencial
   - Sugestão de correção, se tiver

Compromisso de resposta:

- **Acuse de recebimento:** até 72 horas
- **Triagem inicial:** até 7 dias corridos
- **Divulgação coordenada:** após correção publicada, com crédito ao pesquisador (se desejado)

## Escopo

Estão dentro do escopo desta política:

- Injeção (SQL, XSS, CSRF, template)
- Escalonamento de privilégio
- Exposição de dados sensíveis
- Falhas de autenticação e sessão
- Vulnerabilidades em dependências diretas listadas em `composer.json`

Estão **fora** do escopo:

- Ataques que exigem acesso físico à máquina do usuário
- Engenharia social contra mantenedores
- DoS por consumo excessivo de recursos em ambientes sem rate-limit configurado (o MVP não impõe rate-limit por design)

## Boas práticas para operadores

- **Nunca** commite `.env`. O `.gitignore` já bloqueia, mas confirme antes de cada push.
- Rode `php artisan key:generate` em cada ambiente novo.
- Use HTTPS em produção (o Dockerfile fornecido roda HTTP para simplicidade em ambiente de piloto).
- Ative autenticação (planejada em P1) antes de expor a instalação para múltiplos usuários.

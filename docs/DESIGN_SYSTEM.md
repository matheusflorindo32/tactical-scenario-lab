# Design System — Tactical Scenario Lab

Versão: **0.2.0** · Última revisão: **2026-08-09**

Este documento é a fonte da verdade para identidade visual, tokens, componentes Blade e padrões de interação do Tactical Scenario Lab. Toda decisão visual reutilizável deve ser refletida aqui junto com a implementação.

---

## 1. Direção visual

O produto atende instrutores de APH, primeiros socorros e treinamento operacional. A linguagem visual deve comunicar simultaneamente:

- **Confiança institucional** — aparência de produto operacional, não de template genérico.
- **Precisão clínica** — cores semânticas e estados explícitos.
- **Sobriedade tática** — sem neon, parallax gratuito ou gradientes decorativos.
- **Foco na atenção** — prioridade operacional vem antes de decoração.
- **Verdade histórica** — conteúdo congelado, append-only ou mutável deve ser visualmente distinguível.

O M7 organiza a aplicação como um **Operational Command Center**: sidebar canônica, topbar contextual, dashboards orientados a atenção, cockpit de execução e workbench de avaliação/debrief.

---

## 2. Tokens

Todos os tokens vivem em `resources/css/app.css`, dentro do bloco `@theme` do Tailwind CSS v4. Consumo em Blade deve usar classes utilitárias/tokens semânticos; hex hardcoded fica restrito à própria definição de tokens e metadados que o exijam.

### 2.1 Cores

#### Escala primária — Navy tático

| Token | Hex | Uso principal |
|---|---|---|
| `navy-50` | `#eef3fa` | Fundo suave de destaque |
| `navy-100` | `#d6e2f1` | Badges e superfícies brand |
| `navy-200` | `#adc4e2` | Bordas brand |
| `navy-500` | `#2f5c94` | Foco e acento |
| `navy-700` | `#1a3759` | Links e ícones |
| `navy-900` | `#0b1a2c` | Ações primárias e títulos |
| `navy-950` | `#060f1c` | Superfícies táticas profundas |

#### Escala neutra — Stone

| Token | Hex | Uso principal |
|---|---|---|
| `stone-25` | `#fbfaf7` | Canvas claro |
| `stone-50` | `#f7f5f1` | Superfície secundária |
| `stone-100` | `#efece5` | Hover/zebra suave |
| `stone-200` | `#e3ded2` | Bordas |
| `stone-300` | `#cec7b5` | Bordas de campos |
| `stone-500` | `#7a715f` | Elementos neutros despriorizados |

#### Escala de texto — Ink

| Token | Hex | Uso |
|---|---|---|
| `ink-900` | `#101822` | Texto principal |
| `ink-700` | `#2f3846` | Corpo |
| `ink-500` | `#55606f` | Texto secundário |
| `ink-300` | `#9aa3b0` | Placeholder/estado de baixa ênfase |

#### Escalas semânticas

| Família | Uso |
|---|---|
| `emergency-*` | ação destrutiva, erro crítico, risco operacional |
| `clinical-*` | sucesso, conclusão, estado positivo |
| `alert-*` | atenção, rascunho, pendência |

Não usar `green-*`, `red-*` ou `amber-*` genéricos quando a intenção é semântica institucional.

### 2.2 Low-light institucional

O M7 adiciona tokens `lowlight-*` para uma apresentação de baixa luminosidade:

- `lowlight-canvas`
- `lowlight-surface`
- `lowlight-elevated`
- `lowlight-border`
- `lowlight-text`
- `lowlight-muted`
- `lowlight-emergency`
- `lowlight-clinical`
- `lowlight-alert`

Regras:

- modo claro é o estado inicial do SSR (`data-theme="light"`);
- low-light é opt-in via `data-theme="low-light"` no elemento `<html>`;
- a preferência é local ao navegador em `localStorage` com a chave `tsl-theme`;
- não existe endpoint, cookie de aplicação ou persistência no banco para tema;
- significado nunca depende apenas de cor;
- `prefers-reduced-motion` continua válido em ambos os modos.

### 2.3 Tipografia

As famílias são carregadas via Bunny Fonts pela aplicação:

| Família | Uso | Pesos |
|---|---|---|
| **Inter** | UI, parágrafos e formulários | 400, 500, 600, 700 |
| **Instrument Sans** | H1–H4, KPIs e marca | 500, 600, 700 |
| **JetBrains Mono** | IDs, timestamps e valores tabulares | 400, 500 |

Escala recomendada:

- H1: `text-3xl sm:text-4xl` + `font-display font-semibold tracking-tight`
- H2: `text-xl` + `font-display font-semibold`
- H3: `text-base font-semibold`
- Corpo: `text-sm text-ink-700 leading-relaxed`
- Micro: `text-xs text-ink-500`
- Eyebrow: `text-[11px] font-semibold uppercase tracking-[0.14em]`

### 2.4 Espaçamento, raios e sombras

- Base de espaçamento: múltiplos de 4 px.
- Cards: normalmente `p-4` a `p-6`, conforme densidade operacional.
- Gap padrão entre grupos: `gap-4` a `gap-6`.
- Container: `.tsl-container`, `max-width: 80rem`, padding lateral responsivo.
- Raios: `rounded-md` para controles, `rounded-lg`/`rounded-xl` para superfícies.
- Sombras: `shadow-xs`/`shadow-sm` por padrão; elevação maior apenas quando a hierarquia exigir.
- Foco: `--shadow-focus`, nunca removido sem substituto equivalente.

---

## 3. Componentes Blade

Vivem em `resources/views/components/` e são consumidos via `<x-nome />`.

| Componente | Arquivo | Contrato principal |
|---|---|---|
| `x-brand` | `brand.blade.php` | `inverse`, `variant` |
| `x-button` | `button.blade.php` | `variant`, `size`, `href`, `block`, `type` |
| `x-card` | `card.blade.php` | `title`, `subtitle`, `accent`, `padding`, `actions` |
| `x-badge` | `badge.blade.php` | `variant`, `size`, `dot` |
| `x-status-pill` | `status-pill.blade.php` | `status` |
| `x-input` | `input.blade.php` | `label`, `name`, `type`, `hint`, `error`, `required` |
| `x-select` | `select.blade.php` | `label`, `name`, `options`, `selected`, `error` |
| `x-textarea` | `textarea.blade.php` | `label`, `name`, `rows`, `error` |
| `x-alert` | `alert.blade.php` | `variant`, `title` |
| `x-modal` | `modal.blade.php` | `name`, `title`, `maxWidth`, `footer` |
| `x-dropdown` | `dropdown.blade.php` | `align`, `width`, `trigger`, `content` |
| `x-progress` | `progress.blade.php` | `value`, `max`, `label`, `variant` |
| `x-stepper` | `stepper.blade.php` | `steps`, `current` |
| `x-empty-state` | `empty-state.blade.php` | `title`, `description`, `icon`, `actions` |
| `x-stats-card` | `stats-card.blade.php` | `label`, `value`, `hint`, `trend`, `icon`, `accent` |
| `x-table` | `table.blade.php` | `label`, `empty`, `emptyTitle`, `emptyDescription`, slot de tabela |
| `x-section-nav` | `section-nav.blade.php` | `items`, `label`; links âncora nativos e estado atual |
| `x-attention-item` | `attention-item.blade.php` | `title`, `metadata`, `variant`, `href`, slot |
| `x-topbar` | `topbar.blade.php` | contexto global, organização ativa, tema, conta |
| `x-sidebar` | `sidebar.blade.php` | `current`; navegação canônica por abilities |
| `x-breadcrumb` | `breadcrumb.blade.php` | `items` |
| `x-timeline` | `timeline.blade.php` | `items` |
| `x-checklist` | `checklist.blade.php` | `items`, `variant` |
| `x-score-indicator` | `score-indicator.blade.php` | `score`, `label` |
| `x-toast` | `toast.blade.php` | feedback global via Alpine/session/errors |
| `x-footer` | `footer.blade.php` | `variant` |

### 3.1 Componentes M7

#### `x-table`

Usar para listas institucionais tabulares. Exige `label` acessível e fornece wrapper responsivo com `overflow-x-auto`. Quando `empty=true`, delega apresentação ao `x-empty-state`.

#### `x-section-nav`

Usar em workbenches longos. `items` contém `label`, `href` e opcionalmente `state` (`current`, `complete`, `attention`). Não usar JavaScript para substituir navegação por âncoras nativas quando âncoras resolvem.

#### `x-attention-item`

Usar em filas operacionais do dashboard. `variant` é semântico (`navy`, `emergency`, `clinical`, `alert`). O componente vira link apenas quando `href` existe.

### 3.2 Convenções

- Ícones: SVG inline `viewBox="0 0 24 24"`, traço coerente e `aria-hidden` quando decorativos.
- Estados vazios: usar `x-empty-state`.
- Controles interativos: elementos nativos (`button`, `a`, `input`, `select`, `textarea`).
- Feedback: `x-alert`/`x-toast`, não texto solto sem contexto.
- Navegação canônica: sidebar; topbar não replica um segundo menu completo.
- Nenhum item canônico de navegação pode usar `href="#"` como placeholder.

---

## 4. Layouts e arquitetura de informação

### 4.1 Shell autenticado

`layouts/app.blade.php` contém:

- skip link para `#main`;
- `x-topbar` contextual;
- `x-sidebar` como navegação principal;
- região `main`;
- `x-toast`;
- estado SSR de tema claro.

### 4.2 Hierarquia M7

- **Painel do instrutor:** atenção operacional antes de panorama agregado.
- **Visão executiva:** risco/pendência antes de desempenho.
- **Cenários:** ciclo `rascunho → publicado → preparar → executar → avaliar → histórico`.
- **Execução:** cockpit com lifecycle, timeline append-only, equipes, recursos, injects e assessment.
- **Assessment:** workbench com resumo, rubrica/evidências, erros críticos, tempos-chave, debrief, plano de ação e finalização.
- **Gestão:** Pessoas, Organizações e Acessos compartilham tabela, badges, filtros e linguagem institucional.

---

## 5. Acessibilidade

O alvo de autoria é **WCAG 2.2 AA** para as superfícies controladas pela aplicação.

Requisitos:

- foco visível global;
- contraste suficiente em light e low-light;
- labels/nomes acessíveis para controles;
- `aria-current="page"` na navegação canônica ativa;
- `aria-current="location"` em navegação interna quando aplicável;
- skip link preservado;
- drawer mobile fecha com Escape;
- alvos primários mobile próximos de 44 × 44 CSS px ou maiores;
- status comunicado por texto além da cor;
- tabelas com cabeçalhos e `aria-label`/contexto acessível;
- `prefers-reduced-motion` respeitado;
- ausência de `div onclick` para ações canônicas.

---

## 6. UX writing

- Português brasileiro.
- CTAs com verbos claros: *Criar cenário*, *Iniciar execução*, *Finalizar avaliação*.
- Sem promessa quantitativa sem evidência.
- Sem adjetivos genéricos de marketing.
- Mensagens de erro devem dizer o que ocorreu e, quando possível, o próximo passo.
- Estados imutáveis devem usar linguagem explícita como **conteúdo histórico congelado** ou **registro histórico · somente acréscimo**.

---

## 7. Movimento

Movimento existe apenas quando aumenta compreensão:

| Contexto | Duração de referência |
|---|---|
| Hover/active | ~180 ms |
| Troca de passo | ~200 ms |
| Toast | ~200 ms |
| Modal | ~150 ms |
| Progress | ~300 ms |

Nunca usar parallax gratuito, texto letra por letra ou elementos flutuando sem função operacional.

---

## 8. Roadmap do design system

Implementado no M7:

- [x] `x-table` responsivo e acessível para listas institucionais.
- [x] `x-section-nav` para workbenches longos.
- [x] `x-attention-item` para filas de prioridade operacional.
- [x] modo institucional low-light, local ao navegador e sem backend.

Próximas extensões possíveis — **fora do escopo M7**:

- [ ] ordenação/paginação enriquecida dentro do contrato de `x-table` quando houver requisito real;
- [ ] tokens de foco distintos por família de controle, se necessários;
- [ ] catálogo visual/Storybook para componentes Blade.

---

## 9. Como evoluir o sistema

Ao criar ou alterar uma tela:

1. Reutilize um componente existente antes de criar markup local repetido.
2. Estenda um componente somente quando a necessidade for reutilizável.
3. Novos tokens entram em `app.css` e neste documento na mesma PR.
4. Novos componentes entram na tabela da seção 3.
5. Mudanças visuais devem preservar autorização backend, tenant isolation e invariantes de histórico; esconder um botão nunca substitui autorização.
6. Testes de contrato devem acompanhar navegação, estados imutáveis e comportamentos críticos sempre que viável.

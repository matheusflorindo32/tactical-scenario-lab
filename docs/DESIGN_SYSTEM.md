# Design System — Tactical Scenario Lab

Versão: **0.1.0** · Última revisão: **2026-08-05**

Este documento é a fonte da verdade para a identidade visual, os tokens e os componentes Blade do Tactical Scenario Lab. Toda decisão de design nova deve ser incorporada aqui antes ou junto com a implementação.

---

## 1. Direção visual

O produto atende instrutores de APH, primeiros socorros e treinamento operacional. A linguagem visual precisa comunicar simultaneamente:

- **Confiança institucional** — o instrutor vai apresentar isso à turma; não pode parecer template.
- **Precisão clínica** — cores e ícones seguem convenções de saúde e segurança pública.
- **Sobriedade tática** — sem entretenimento visual, sem gradientes decorativos, sem neon.
- **Foco no conteúdo** — tipografia editorial, hierarquia rígida, ampla respiração.

**Referências gerais** (uso para inspiração de composição, hierarquia e microinterações — sem cópia direta): NextCode Eagle, ReactBits, 21st.dev, shadcn/ui.

---

## 2. Tokens

Todos os tokens vivem em `resources/css/app.css`, dentro do bloco `@theme` do Tailwind v4. Consumo em Blade sempre via classes utilitárias (`bg-navy-900`, `text-emergency-500` etc.), nunca hex hardcoded.

### 2.1 Cores

#### Escala primária — Navy tático
Usada em cabeçalhos, texto principal, superfícies escuras, elementos de foco e ações primárias.

| Token | Hex | Uso principal |
|---|---|---|
| `navy-50`  | `#eef3fa` | Fundo suave de destaque |
| `navy-100` | `#d6e2f1` | Anéis de foco tênues, badges |
| `navy-200` | `#adc4e2` | Borda em cards discretos |
| `navy-500` | `#2f5c94` | Foco, acento em progress |
| `navy-700` | `#1a3759` | Texto de link, ícones |
| `navy-900` | `#0b1a2c` | Ações primárias, títulos |
| `navy-950` | `#060f1c` | Fundo escuro (footer, aviso) |

#### Escala neutra — Stone (off-white quente)
Superfícies de aplicação e cinzas de UI.

| Token | Hex | Uso principal |
|---|---|---|
| `stone-25`  | `#fbfaf7` | Fundo global |
| `stone-100` | `#efece5` | Zebra sutil, hover |
| `stone-200` | `#e3ded2` | Bordas de cards |
| `stone-500` | `#7a715f` | Ícones desativados |

#### Escala de texto — Ink
Neutros frios com contraste alto (WCAG AA garantido sobre `stone-25`).

| Token | Hex | Uso |
|---|---|---|
| `ink-900` | `#101822` | Texto principal (contraste 15.6:1) |
| `ink-700` | `#2f3846` | Texto de parágrafo (contraste 10.8:1) |
| `ink-500` | `#55606f` | Texto secundário (contraste 6.5:1) |
| `ink-300` | `#9aa3b0` | Placeholder (uso restrito) |

#### Escalas semânticas

| Token | Hex | Uso |
|---|---|---|
| `emergency-500` | `#c21807` | Vermelho médico. Ações destrutivas, erros críticos, ameaça ativa. |
| `emergency-700` | `#7a0c04` | Texto sobre `emergency-50`. |
| `clinical-500`  | `#0f7a4a` | Verde institucional. Sucesso, avaliação positiva, itens concluídos. |
| `alert-500`     | `#b7791f` | Âmbar. Rascunhos, avisos operacionais, ameaça potencial. |

Cada semântica tem `-50` (fundo suave) e `-100` (anel de foco). Não use verdes/vermelhos genéricos do Tailwind (`green-*`, `red-*`) — sempre `clinical-*` e `emergency-*`.

### 2.2 Tipografia

Três famílias, todas via Bunny Fonts (self-hosted).

| Família | Uso | Pesos disponíveis |
|---|---|---|
| **Inter** (sans) | Texto de UI, parágrafos, formulários | 400, 500, 600, 700 |
| **Instrument Sans** (display) | Títulos H1–H4, KPIs, marca | 500, 600, 700 |
| **JetBrains Mono** (mono) | Códigos, IDs, timestamps, valores tabulares | 400, 500 |

Escala tipográfica (`--text-*` do Tailwind v4). Usar:
- H1: `text-3xl sm:text-4xl` + `font-display font-semibold tracking-tight`
- H2: `text-xl` + `font-display font-semibold`
- H3: `text-base` + `font-semibold`
- Corpo: `text-sm text-ink-700 leading-relaxed`
- Micro: `text-xs text-ink-500`
- Rótulos "eyebrow": `text-[11px] font-semibold uppercase tracking-[0.14em]`

### 2.3 Espaçamento

Baseado em múltiplos de `4px` (`--spacing: 0.25rem`).

- **Padding padrão de cards:** `p-6` (24 px).
- **Gap entre cards:** `gap-6` (24 px).
- **Container:** `.tsl-container` — `max-w-7xl`, padding lateral responsivo (24 → 32 px em `lg`).

### 2.4 Raios

| Token | Valor | Uso |
|---|---|---|
| `rounded-sm` | 6 px | Badges, focus ring |
| `rounded-md` | 8 px | Botões, inputs, itens de menu |
| `rounded-lg` | 12 px | Cards, alertas |
| `rounded-xl` | 16 px | Superfícies destacadas (raro) |
| `rounded-full` | ∞ | Pills, avatares, badges |

### 2.5 Sombras

Todas suaves e com tinta navy (nunca preto puro). Aumentam elevação apenas quando o componente exige distinção real.

| Token | Uso |
|---|---|
| `shadow-xs` | Cards padrão |
| `shadow-sm` | Cards com hover |
| `shadow-md` | Modais em desktop |
| `shadow-lg` | Modais em mobile, popovers |
| `shadow-focus` | Anel de foco global (via `:focus-visible`) |

### 2.6 Estados

- **Foco:** `:focus-visible` global aplica `--shadow-focus` (anel navy 3px). Nunca remover.
- **Hover:** transição de cor em 180 ms, `ease-standard` (`cubic-bezier(0.2, 0, 0, 1)`).
- **Ativo:** escurece em uma escala (ex.: `navy-900` → `navy-950`).
- **Desabilitado:** `opacity-50` + `cursor-not-allowed`.
- **Erro (input):** `ring-emergency-500`, mensagem `text-emergency-600` abaixo.
- **Sucesso (input):** sem estilo especial — feedback via toast global.

---

## 3. Componentes Blade

Vivem em `resources/views/components/`. Uso via `<x-nome ... />`.

| Componente | Arquivo | Props principais |
|---|---|---|
| `x-brand` | `brand.blade.php` | `inverse`, `variant=full\|mark` |
| `x-button` | `button.blade.php` | `variant`, `size`, `href`, `block`, `type` |
| `x-card` | `card.blade.php` | `title`, `subtitle`, `accent`, `padding`, slot `actions` |
| `x-badge` | `badge.blade.php` | `variant`, `size`, `dot` |
| `x-status-pill` | `status-pill.blade.php` | `status` (`draft\|running\|completed`) |
| `x-input` | `input.blade.php` | `label`, `name`, `type`, `hint`, `error`, `required` |
| `x-select` | `select.blade.php` | `label`, `name`, `options`, `selected`, `error` |
| `x-textarea` | `textarea.blade.php` | `label`, `name`, `rows`, `error` |
| `x-alert` | `alert.blade.php` | `variant` (`info\|success\|warning\|danger`), `title` |
| `x-modal` | `modal.blade.php` | `name`, `title`, `maxWidth`, slot `footer` |
| `x-dropdown` | `dropdown.blade.php` | `align`, `width`, slots `trigger`/`content` |
| `x-progress` | `progress.blade.php` | `value`, `max`, `label`, `variant` |
| `x-stepper` | `stepper.blade.php` | `steps`, `current` |
| `x-empty-state` | `empty-state.blade.php` | `title`, `description`, `icon`, slot `actions` |
| `x-stats-card` | `stats-card.blade.php` | `label`, `value`, `hint`, `trend`, `icon`, `accent` |
| `x-topbar` | `topbar.blade.php` | `current` |
| `x-sidebar` | `sidebar.blade.php` | `current` |
| `x-breadcrumb` | `breadcrumb.blade.php` | `items` (array de `[label, href?]`) |
| `x-timeline` | `timeline.blade.php` | `items` (array de `[title, subtitle, time, status]`) |
| `x-checklist` | `checklist.blade.php` | `items`, `variant` |
| `x-score-indicator` | `score-indicator.blade.php` | `score` (0–100), `label` |
| `x-toast` | `toast.blade.php` | escuta `session('success')` e `$errors` automaticamente |
| `x-footer` | `footer.blade.php` | `variant=public\|app` |

### Convenções

- **Ícones:** SVG inline, `viewBox="0 0 24 24"`, `stroke-width` 1.5–2 (2 para ícones de UI, 1.5 para ilustrativos). Nunca misturar bibliotecas.
- **Estados vazios:** sempre usar `x-empty-state`. Nunca `<div>Nenhum item</div>`.
- **Formulários:** sempre usar os componentes de input/select/textarea; nunca `<input>` direto.
- **Feedback:** flash de sessão vira toast via Alpine store (`$store.ui.toast`).

---

## 4. Layouts

- `layouts/app.blade.php` — shell autenticado: `x-topbar` + `x-sidebar` + `main` + `x-toast`. Slots: `title`, `header`, `breadcrumbs`. Prop: `current` para marcar item ativo do menu.
- `layouts/public.blade.php` — shell marketing: header simples, `main`, `x-footer variant=public`, `x-toast`.
- `errors/layout.blade.php` — chassi para 404, 500, 503, 419, 403. Reutiliza tokens, sem sidebar.

---

## 5. Acessibilidade

Requisitos que devem ser atendidos em qualquer nova tela ou componente:

- **Foco visível global** via `:focus-visible` — anel de 3 px em `navy-500`. Não remover em nenhum contexto.
- **Contraste WCAG AA** — todo texto sobre superfície tem contraste ≥ 4.5:1 (validado com `ink-500` em `stone-25`). Elementos grandes tolerados em 3:1.
- **Labels obrigatórios** — todo input tem `<label>` associado. `x-input`, `x-select`, `x-textarea` já fazem isso.
- **ARIA quando necessário** — `aria-current="page"` em navegação, `aria-invalid` em campos com erro, `role="alert"` em `x-alert`, `role="progressbar"` em `x-progress`.
- **Navegação por teclado** — todos os elementos interativos são `<button>`, `<a>` ou `<input>`. Não usar `<div onclick>`. Dropdown e modal fecham com `Escape` e trap de foco.
- **Skip link** — presente em `layouts/app.blade.php` (`Pular para o conteúdo`).
- **Área clicável** — botões e itens de menu com altura mínima de 40 px (44 px em mobile).
- **Responsividade** — layouts pensados de `sm` (640 px) até `2xl` (1536 px). Sidebar colapsa em drawer em telas < `lg`.
- **`prefers-reduced-motion`** — todas as transições e animações são zeradas via `@media (prefers-reduced-motion: reduce)` em `app.css`.

---

## 6. UX writing — princípios

- **Português brasileiro** sempre. Sem estrangeirismos desnecessários (usar "cenário", não "case").
- **Verbos no imperativo** em CTAs: *Criar cenário*, *Iniciar execução*, *Finalizar avaliação*.
- **Sem promessas** — "reduza tempo em X%" só se tivermos evidência. Preferir "cenário em menos de cinco minutos" (que é o critério de sucesso documentado do MVP).
- **Sem adjetivos de marketing genérico** — nada de "revolucionário", "poderoso", "inteligente".
- **Contexto sempre presente** — mensagens de erro dizem o que fazer, não só o que aconteceu.

---

## 7. Animações e movimento

Usar movimento apenas quando aumentar a compreensão:

| Contexto | Duração | Curva |
|---|---|---|
| Hover / active | 180 ms | `ease-standard` |
| Troca de passo do wizard | 200 ms | `ease-standard` |
| Entrada de toast | 200 ms | `ease-out` |
| Modal | 150 ms | `ease-in` (saída) / `ease-out` (entrada) |
| Progress fill | 300 ms | `transition-all` |

**Nunca**: parallax gratuito, ícones que giram sem ação, cards que "flutuam" no scroll, texto que aparece letra por letra.

---

## 8. Roadmap do design system

- **P1**: adicionar componente `x-table` com ordenação e paginação nativa.
- **P1**: dark mode institucional (baseado em `navy-950` / `stone-25` invertido).
- **P2**: tokens em `--radius-focus` distintos por família (form inputs vs botões).
- **P2**: publicar Storybook estático (ou variante Blade) com todos os componentes documentados visualmente.

---

## 9. Como usar este documento

Ao criar uma nova tela:

1. **Reutilize** — se um componente listado na seção 3 resolve, use-o.
2. **Estenda o componente** antes de criar utilitários locais.
3. **Novos tokens** entram em `app.css` **e** aqui, na mesma PR.
4. **Novos componentes** entram em `resources/views/components/` **e** aqui, na tabela da seção 3.

Toda PR que altere o visual deve incluir screenshot antes/depois no corpo do PR.

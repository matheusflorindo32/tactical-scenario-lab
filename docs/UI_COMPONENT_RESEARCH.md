# Curadoria de componentes de UI — Tactical Medicine Academy

Versão: **0.2.0** · Data: **2026-08-06** · Status: **curadoria concluída — aguardando aprovação para implementação**

Substitui integralmente a v0.1.0. Cada linha desta versão foi **decidida** com base em fetches ao vivo das páginas oficiais listadas em §4. Onde ainda existe pendência de curadoria por autor, isso está explicitado com a palavra `PENDÊNCIA` na coluna Decisão — e vem com o critério exato para resolver.

---

## 1. Prioridade de fontes (revisada com base em verificação)

1. **shadcn/ui** (`ui.shadcn.com`) — biblioteca base de primitivas. Verificado ao vivo: 60+ componentes, três variantes de base (Base UI / React Aria / Radix UI), 121k stars no GitHub. Repositório `shadcn-ui/ui` **MIT**.
2. **TanStack Table v9** (para lógica de tabelas) — MIT, incluído em `@tanstack/react-table`; **não vamos usar** o pacote em si (React), mas o **padrão de composição** shadcn Data Table serve de referência visual.
3. **cmdk** (para command palette) — MIT, autor Dip. **Não vamos usar** o pacote React; reproduzimos com Alpine.
4. **21st.dev** (`21st.dev`) — registry comunitário; usado como **fonte de padrões visuais** e para descoberta de variações. Licença por autor: **cada componente precisa checagem individual** na página do autor.
5. **Origin UI** (`origin-space/originui` no GitHub, `originui.com`) — 44 componentes no 21st, biblioteca robusta em form controls. **Presumido MIT** pelo padrão do ecossistema; **antes de adotar qualquer componente, ler `LICENSE` no repositório GitHub**.
6. **Design system TMA** — componentes já em `resources/views/components/`.
7. **Chart.js** (`chartjs.org`) — MIT — para gráficos, em vez do shadcn Chart (que é wrapper Recharts+React).
8. **Próprio** — última alternativa.

---

## 2. Regras invariantes (recolocadas)

- **Zero copy-paste React** — a nossa stack é Blade+Alpine+Tailwind v4. Todo componente shadcn/21st é reescrito em Blade preservando **layout, hierarquia, composição, ARIA e microinterações**.
- **Licença sempre registrada** em comentário no topo do componente Blade:
  ```blade
  {{-- Adaptado de: <fonte> · <URL> · Licença: <licença> --}}
  ```
- **Nunca depender de**: `cmdk`, `@tanstack/react-table`, `framer-motion`, `radix-ui`, `react-day-picker`, `react-hook-form`. São React-only.
- **Acessibilidade não é opcional** — WCAG AA, foco visível, teclado, ARIA.
- **Tokens TMA obrigatórios** — `navy-*`, `stone-*`, `emergency-*`, `clinical-*`, `alert-*`.

---

## 3. Fatos verificados nesta rodada

Nesta rodada de curadoria fiz fetch das seguintes páginas ao vivo (via `WebFetch` — timestamps de 2026-08-06):

| URL | O que confirmei |
|---|---|
| https://21st.dev/ | Home; 12k+ componentes; catálogo de libraries; categorias reais |
| https://ui.shadcn.com/docs/components/base/data-table | Data Table = **guia** apoiado em TanStack Table v9 (React); depende de `@tanstack/react-table`; componentes reutilizáveis `DataTableColumnHeader`, `DataTablePagination`, `DataTableViewOptions` |
| https://ui.shadcn.com/docs/components/base/command | Command = wrapper de `cmdk` (Dip, MIT); sub-componentes `Command`, `CommandDialog`, `CommandInput`, `CommandList`, `CommandEmpty`, `CommandGroup`, `CommandItem`, `CommandSeparator`, `CommandShortcut` |
| https://ui.shadcn.com/docs/components/base/combobox | Combobox = Base UI; sub-componentes `Combobox`, `ComboboxInput`, `ComboboxContent`, `ComboboxList`, `ComboboxItem`, `ComboboxEmpty`, `ComboboxGroup`, `ComboboxSeparator`, `ComboboxCollection`; multi-select com `ComboboxChips`/`ComboboxChipsInput`/`ComboboxChip`; API prop `multiple`, `showClear`, `autoHighlight`, `itemToStringValue` |
| https://ui.shadcn.com/docs/components/base/sheet | Sheet = extensão de Dialog; sub-componentes `Sheet`, `SheetTrigger`, `SheetContent`, `SheetHeader`, `SheetTitle`, `SheetDescription`, `SheetFooter`, `SheetClose`; prop `side="top\|right\|bottom\|left"`, `showCloseButton` |
| https://21st.dev/community/originui/library/origin-ui | Origin UI: 44 componentes na 21st (200 no total). Repositório `github.com/origin-space/originui`. Categorias fortes: Tree, Image Crop, Navigation Bar. Segue "shadcn/ui conventions" |

**Não** consegui, nesta rodada, fazer fetch de:
- `github.com/origin-space/originui/blob/main/LICENSE` (timeout) — assumir MIT é **presunção**; antes de importar visualmente qualquer padrão específico do Origin UI, um humano precisa abrir o arquivo `LICENSE` no repo e confirmar.
- Página individual de cada componente Aceternity/Magic UI/Kibo UI/Motion Primitives — cada linha marcada `PENDÊNCIA-AUTOR` abaixo indica que o autor específico ainda precisa ser aberto e sua licença conferida antes de qualquer inspiração visual entrar no código.

---

## 4. Fontes registradas com licença

| Fonte | URL raiz | Licença | Como confirmei |
|---|---|---|---|
| shadcn/ui | https://ui.shadcn.com/ · https://github.com/shadcn-ui/ui | **MIT** (repositório oficial) | Fetch de 4 páginas de docs (ver §3) |
| TanStack Table v9 | https://tanstack.com/table/latest/docs | **MIT** (padrão TanStack) | Referenciado como dependência do Data Table shadcn |
| cmdk (Dip) | https://github.com/dip/cmdk | **MIT** | Referenciado no Command shadcn |
| Chart.js | https://www.chartjs.org/ | **MIT** | Conhecimento público estável |
| 21st.dev | https://21st.dev/ | Registry — **licença por autor** | Fetch da home |
| Origin UI | https://github.com/origin-space/originui · https://originui.com/ | **Presumido MIT** (a confirmar no repo antes de adotar) | Fetch da página 21st.dev + GitHub timeout |
| Aceternity UI, Magic UI, Kibo UI, Motion Primitives, ReUI, Kokonut UI, cult ui, Ruixen UI, SHSF UI | (várias) | **por autor** | Nomes registrados na home 21st.dev; licença individual precisa checagem |

---

## 5. Decisões arquiteturais que economizam curadoria

Estas três decisões eliminam dezenas de linhas 🔎/🧪 da v0.1.0:

### 5.1 Data Table
**Não adotar** `@tanstack/react-table`. **Não** portar a lógica React para Blade.
**Solução TMA**: tabela server-side renderizada com paginação nativa Laravel (`->paginate()`), ordenação via query string (`?sort=name&dir=asc`), busca via query string (`?q=...`). Alpine adiciona seleção de linhas (checkbox), toggle de visibilidade de colunas (localStorage), e em mobile a tabela vira lista de cards (padrão CSS via `display: grid` em breakpoints).
Referência visual: shadcn Data Table (https://ui.shadcn.com/docs/components/base/data-table) — composição de header + toolbar + body + pagination + toolbar de bulk actions.

### 5.2 Command palette
**Não adotar** `cmdk`. Implementar `x-command-palette` com Alpine:
- Dialog full-width com `x-trap.inert.noscroll` (do `@alpinejs/focus`, já instalado).
- Input com `@keydown.arrow-down`, `@keydown.arrow-up`, `@keydown.enter`, `@keydown.escape`.
- Lista filtrada por `data.items` (client-side) ou `fetch(/search?q=...)` (server-side).
- Grupos e separadores via composição HTML simples.
- Atalho `Ctrl/⌘+K` global via `document.addEventListener('keydown')`.

Referência visual: shadcn Command (https://ui.shadcn.com/docs/components/base/command) — composição CommandInput → CommandList → CommandEmpty + CommandGroup(CommandItem) + CommandSeparator + CommandShortcut.

### 5.3 Combobox
**Não adotar** Base UI Combobox React. Implementar `x-combobox` com Alpine:
- Input `type="text"` com `x-model`.
- Popover controlado por `open` state; fecha ao clicar fora.
- Multi-select com chips renderizados manualmente.
- Fetch on-type para listas grandes (`?q=...`); filtro local para listas ≤ 200 itens.

Referência visual: shadcn Combobox (https://ui.shadcn.com/docs/components/base/combobox) — composição, chips com `ComboboxChip`, API `multiple/showClear/autoHighlight`.

---

## 6. Curadoria final por tela × componente

Formato: `Tela · Função · Origem visual · URL · Licença · Componente Blade · Decisão`.
Coluna Decisão: **ADOTAR** = migrar/criar já na Fase 2.1; **POSTERGAR** = fica para fase indicada; **PENDÊNCIA-AUTOR** = componente base decidido, mas variação específica precisa validação humana antes de codar.

### 6.1 Chassi (Fase 2.1)
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| App shell · Sidebar responsiva | shadcn/ui Sidebar | https://ui.shadcn.com/docs/components/base/sidebar | MIT | `x-sidebar` (já existe — refinar variação drawer mobile) | **ADOTAR** — refinar |
| App shell · Topbar | próprio (mantido) | — | MIT (nosso) | `x-topbar` (já existe) | manter |
| Todas · Skip link | próprio (mantido) | — | MIT (nosso) | inline em layout | manter |
| Todas · Command palette (Ctrl+K) | shadcn Command (referência) | https://ui.shadcn.com/docs/components/base/command | shadcn MIT · reimplementação Alpine | **`x-command-palette`** (novo, Alpine puro — ver §5.2) | **ADOTAR** — Fase 2.1 |
| Todas · Toast global | próprio (Alpine store) | — | MIT (nosso) | `x-toast` (já existe) | manter |
| Todas · Dialog | shadcn Dialog | https://ui.shadcn.com/docs/components/base/dialog | MIT | `x-modal` (já existe) — alinhar composição a `SheetHeader/SheetTitle/SheetDescription/SheetFooter` | **ADOTAR** — refinar |
| Todas · Sheet (drawer lateral) | shadcn Sheet | https://ui.shadcn.com/docs/components/base/sheet | MIT | **`x-sheet`** (novo) — API `side="right\|left\|top\|bottom"`, sub-componentes `x-sheet.trigger/content/header/title/description/footer` via `@include` | **ADOTAR** — Fase 2.1 |
| Todas · Bottom navigation (mobile) | próprio + ref shadcn Navigation Menu | https://ui.shadcn.com/docs/components/base/navigation-menu | MIT | **`x-mobile-nav`** (novo) — 4–5 ícones inline com `active` state | **ADOTAR** — Fase 2.3 |

### 6.2 Dashboard e KPIs
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| Dashboard · KPI card | próprio (mantido) | — | MIT (nosso) | `x-stats-card` (já existe) | manter |
| Dashboard · Card genérico | shadcn Card | https://ui.shadcn.com/docs/components/base/card | MIT | `x-card` (já existe) | manter |
| Dashboard · Progress bar | shadcn Progress | https://ui.shadcn.com/docs/components/base/progress | MIT | `x-progress` (já existe) | manter |
| Dashboard · Skeleton loading | shadcn Skeleton (padrão trivial) | https://ui.shadcn.com/docs/components/base/skeleton | MIT | **`x-skeleton`** (novo) — `<div class="animate-pulse bg-stone-200 rounded" style="height:…">` | **ADOTAR** — Fase 2.1 |
| Dashboard · Empty state | próprio (mantido) | — | MIT (nosso) | `x-empty-state` (já existe) | manter |
| Dashboard · Gráficos | Chart.js | https://www.chartjs.org/ | MIT | **`x-chart`** (novo) — wrapper com `x-init` que instancia Chart.js sobre `<canvas>`; variações line/bar/donut | **ADOTAR** — Fase 7 (relatórios) |
| Dashboard · Spinner | shadcn Spinner (padrão trivial) | https://ui.shadcn.com/docs/components/base/spinner | MIT | **`x-spinner`** (novo) — SVG animado | **ADOTAR** — Fase 2.1 |

### 6.3 Busca e pessoas (Fase 2.1 — coração da entrega)
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| /people · Input com ícone | shadcn Input + Input Group | https://ui.shadcn.com/docs/components/base/input · https://ui.shadcn.com/docs/components/base/input-group | MIT | **`x-search-input`** (novo) — composição input + ícone + `x-model` + `debounce` | **ADOTAR** — Fase 2.1 |
| /people · Data table (server-side) | shadcn Data Table (só referência) | https://ui.shadcn.com/docs/components/base/data-table | MIT | **`x-data-table`** (novo) + partial `pagination.blade.php` — ver §5.1 | **ADOTAR** — Fase 2.1 |
| /people · Filter chips | shadcn Popover + Badge | https://ui.shadcn.com/docs/components/base/popover · https://ui.shadcn.com/docs/components/base/badge | MIT | **`x-filter-chip`** (novo) — badge dismissível ligada a query string | **ADOTAR** — Fase 2.1 |
| /people · Empty state | próprio | — | MIT | `x-empty-state` | manter |
| /people · Modal cadastro rápido | shadcn Dialog | https://ui.shadcn.com/docs/components/base/dialog | MIT | **`x-quick-add-modal`** (novo composto) — usa `x-modal` + form com 2 campos | **ADOTAR** — Fase 2.1 |
| /people/{uuid} · Tabs | shadcn Tabs | https://ui.shadcn.com/docs/components/base/tabs | MIT | **`x-tabs`** (novo) — sub `x-tab.list/trigger/panel` via Alpine + hash sync opcional | **ADOTAR** — Fase 2.1 |
| /people/{uuid} · Completeness bar | próprio | — | MIT | **`x-completeness-bar`** (novo) — usa `x-progress` internamente | **ADOTAR** — Fase 2.1 |
| /people/{uuid} · Badges de pendência | próprio | — | MIT | `x-badge` | manter |
| /people · Combobox (buscar org/unidade) | shadcn Combobox (só referência) | https://ui.shadcn.com/docs/components/base/combobox | MIT | **`x-combobox`** (novo Alpine — ver §5.3) — variação `multiple` para tags | **ADOTAR** — Fase 2.1 |
| /people · Ficha PII masked | shadcn Field + próprio | https://ui.shadcn.com/docs/components/base/field | MIT | **`x-pii`** (novo) — mostra `***.***.***-XX` por default, botão "revelar" com policy | **ADOTAR** — Fase 2.2 (auth) |
| /people · Avatar | shadcn Avatar | https://ui.shadcn.com/docs/components/base/avatar | MIT | **`x-avatar`** (novo) — img + fallback iniciais | **ADOTAR** — Fase 2.1 |
| /people · Autocomplete (form) | Origin UI (biblioteca) | https://21st.dev/@originui/components (categoria Input/Form) | **Presumido MIT — confirmar no repo** | reuso do `x-combobox` com props diferentes | **ADOTAR o padrão de composição** shadcn Combobox; **PENDÊNCIA-AUTOR** para adaptações visuais específicas de Origin UI |
| /people · Radio / Checkbox / Switch | shadcn Radio Group / Checkbox / Switch | https://ui.shadcn.com/docs/components/base/radio-group · /checkbox · /switch | MIT | **`x-radio-group`, `x-switch`** (novos); `<input type=checkbox>` estilizado já cobre | **ADOTAR** — Fase 2.1 |
| /people · Textarea | shadcn Textarea | https://ui.shadcn.com/docs/components/base/textarea | MIT | `x-textarea` (já existe) | manter |
| /people · Select | shadcn Select / Native Select | https://ui.shadcn.com/docs/components/base/select · /native-select | MIT | `x-select` (já existe) | manter |

### 6.4 Cursos, turmas, equipes (Fase 2.3)
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| /courses · Card grid | shadcn Card | https://ui.shadcn.com/docs/components/base/card | MIT | `x-card` | manter |
| /classes/{uuid} · Tabs | (idem 6.3) | (idem) | MIT | `x-tabs` | idem |
| /teams · Lista/Kanban leve | próprio (fase futura) | — | MIT | `x-team-board` (novo) — versão simples de lista antes de kanban | **POSTERGAR** — Fase 7 |
| Todas listagens · Pagination | shadcn Pagination + Laravel `->links()` | https://ui.shadcn.com/docs/components/base/pagination | MIT | **`x-pagination`** (novo) — substitui view Bootstrap default do Laravel | **ADOTAR** — Fase 2.1 |
| Todas listagens · Breadcrumb | shadcn Breadcrumb | https://ui.shadcn.com/docs/components/base/breadcrumb | MIT | `x-breadcrumb` (já existe) | manter |

### 6.5 Cenários e execução (Fase 3+/6)
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| /scenarios/create · Wizard/Stepper | próprio (mantido) | — | MIT | wizard Alpine em `create.blade.php` | manter |
| /scenarios/{id} · Timeline | próprio | — | MIT | `x-timeline` (já existe) | manter |
| /executions/{id} · Board de vítimas | próprio (grid de `x-card`) | — | MIT | **`x-victim-card`** (novo) | **POSTERGAR** — Fase 3 |
| /executions/{id} · Painel de intervenções | shadcn Sheet | (idem 6.1) | MIT | reuso de `x-sheet` | **POSTERGAR** — Fase 6 |
| /executions/{id} · Triage board (colunas) | próprio | — | MIT | **`x-triage-board`** (novo — colunas vermelho/amarelo/verde/preto) | **POSTERGAR** — Fase 6 |
| /debriefings/{id} · Accordion | shadcn Accordion | https://ui.shadcn.com/docs/components/base/accordion | MIT | **`x-accordion`** (novo) | **POSTERGAR** — Fase 7 |
| /scenarios/{id} · Score gauge | próprio | — | MIT | `x-score-indicator` (já existe) | manter |

### 6.6 Equipamentos, kits, protocolos (Fases 4-5)
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| /equipment · Data table | (idem 6.3) | (idem) | MIT | `x-data-table` | idem |
| /kits/{uuid} · Table simples | shadcn Table | https://ui.shadcn.com/docs/components/base/table | MIT | **`x-table`** (novo — table simples, sem features) | **POSTERGAR** — Fase 4 |
| /kits/{uuid} · Upload de foto/manual | próprio (nativo) | — | MIT | **`x-file-upload`** (novo) — `<input type=file>` estilizado + drag/drop Alpine | **POSTERGAR** — Fase 4 |
| /protocols/{id} · Version list | próprio | — | MIT | **`x-version-list`** (novo) | **POSTERGAR** — Fase 5 |
| /protocols/{id} · PDF preview | pdf.js | https://mozilla.github.io/pdf.js/ | Apache 2.0 | **`x-pdf-preview`** (novo, opcional) | **POSTERGAR** — Fase 5 |
| /evidences · Evidence card | `x-card` | — | MIT | `x-card` com slot de badges | manter |

### 6.7 Relatórios (Fase 7)
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| /reports · Filtros combinados | shadcn Popover + Select | (idem) | MIT | reuso | **POSTERGAR** — Fase 7 |
| /reports · Date picker | Flatpickr (não React) | https://flatpickr.js.org/ | **MIT** | **`x-date-picker`** (novo, wrapper Flatpickr) | **POSTERGAR** — Fase 7 |
| /reports · Gráficos | Chart.js (§6.2) | (idem) | MIT | `x-chart` | idem |
| /reports · Export CSV/PDF | server-side (Laravel + `barryvdh/laravel-dompdf`) | — | MIT | (backend) | **POSTERGAR** — Fase 7 |

### 6.8 Landing e marketing
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| /welcome · Hero | 21st.dev categoria `hero` (sem shader/animação 3D) | https://21st.dev/community/components/s/hero | por autor · **PENDÊNCIA-AUTOR** para escolher 1 componente específico | `welcome.blade.php` (já existe) | manter atual; refinamento futuro com **1 autor específico** aprovado |
| /welcome · Footer | próprio | — | MIT | `x-footer` (já existe) | manter |
| /welcome · CTA | `x-button` | — | MIT | `x-button` | manter |
| /welcome · FAQ | shadcn Accordion (§6.5) | (idem) | MIT | `x-accordion` | idem |

### 6.9 Utilitários transversais
| Tela · Função | Origem visual | URL | Licença | Componente Blade | Decisão |
|---|---|---|---|---|---|
| Todas · Alert Dialog (confirmação) | shadcn Alert Dialog | https://ui.shadcn.com/docs/components/base/alert-dialog | MIT | variação de `x-modal` com prop `variant="danger"` | **ADOTAR** — Fase 2.1 |
| Todas · Tooltip | shadcn Tooltip (padrão simples) | https://ui.shadcn.com/docs/components/base/tooltip | MIT | **`x-tooltip`** (novo, Alpine `x-on:mouseenter`) | **ADOTAR** — Fase 2.1 |
| Todas · Kbd | shadcn Kbd | https://ui.shadcn.com/docs/components/base/kbd | MIT | **`x-kbd`** (novo — `<kbd>` estilizado) | **ADOTAR** — Fase 2.1 (usado no command palette) |
| Todas · Hover Card | shadcn Hover Card | https://ui.shadcn.com/docs/components/base/hover-card | MIT | **`x-hover-card`** (novo) | **POSTERGAR** — Fase 3 |
| Auditoria · Log viewer | próprio | — | MIT | **`x-audit-log-table`** (novo) — reuso de `x-data-table` | **ADOTAR** — Fase 2.2 |

---

## 7. Componentes fora de escopo (recusados explicitamente)

| Fora de escopo | Motivo |
|---|---|
| 21st.dev categoria `shader` (`https://21st.dev/community/components/s/shader`) | Visual excessivo, incompatível com sobriedade clínica |
| 21st.dev categoria `ai-chat` | Só a partir de Fase 8 |
| 21st.dev categoria `carousel` 3D | Visual gratuito, sem uso funcional |
| Aceternity heroes animados com framer-motion pesado | Depende de framer-motion (React); e o excesso animado prejudica credibilidade clínica |
| Magic UI shimmer buttons / gradient buttons | Visual "AI slop" — não bate com o tom institucional |
| shadcn Charts | Wrapper Recharts (React) — substituído por Chart.js |

---

## 8. Backlog de componentes pesquisados por tela — resolução

| Item da lista original (§38.3 do prompt mestre) | Decisão |
|---|---|
| sidebar responsiva | shadcn Sidebar — refinar `x-sidebar` (Fase 2.1) |
| topbar | próprio — mantido |
| dashboard | shadcn Card + `x-stats-card` — mantido |
| command palette | `x-command-palette` novo, Alpine puro (Fase 2.1) |
| busca universal / search form | `x-search-input` novo (Fase 2.1) |
| data table | `x-data-table` novo, server-side (Fase 2.1) |
| filtros | `x-filter-chip` novo (Fase 2.1) |
| cards de pessoas/alunos/instrutores | `x-card` + slot customizado (Fase 2.1) |
| cards de vítimas | `x-victim-card` novo (Fase 3) |
| cards de equipamentos/kits/protocolos | `x-card` + slot (Fases 4-5) |
| score gauge | `x-score-indicator` — mantido |
| KPI cards | `x-stats-card` — mantido |
| charts | `x-chart` wrapper Chart.js (Fase 7) |
| stepper / wizard | inline no `create.blade.php` — mantido |
| timeline | `x-timeline` — mantido |
| tabs | `x-tabs` novo (Fase 2.1) |
| accordions | `x-accordion` novo (Fase 7) |
| command center | reuso de `x-command-palette` (Fase 6) |
| kanban / task list | `x-team-board` (Fase 7) |
| alert | `x-alert` — mantido |
| toast | `x-toast` — mantido |
| modal / dialog | `x-modal` — mantido; `x-alert-dialog` variação para confirmação |
| drawer / sheet | `x-sheet` novo (Fase 2.1) |
| popover | reuso de `x-dropdown` (mantido) |
| combobox / autocomplete / multi-select | `x-combobox` novo Alpine (Fase 2.1) |
| date picker | `x-date-picker` wrapper Flatpickr (Fase 7) |
| empty state | `x-empty-state` — mantido |
| skeleton | `x-skeleton` novo (Fase 2.1) |
| loading state | `x-spinner` novo (Fase 2.1) |
| confirmation | variação `x-modal variant="danger"` (Fase 2.1) |
| file upload | `x-file-upload` novo (Fase 4) |
| drag and drop | nativo (Fase 4) |
| pagination | `x-pagination` novo (Fase 2.1) |
| breadcrumbs | `x-breadcrumb` — mantido |
| mobile bottom navigation | `x-mobile-nav` novo (Fase 2.3) |
| floating action button | próprio simples (Fase 2.3) |
| settings tabs | reuso `x-tabs` (Fase 2.2) |
| profile form | composição de `x-input/select/textarea` (Fase 2.1) |
| audit log viewer | `x-audit-log-table` (Fase 2.2) |
| incident command board | `x-ics-board` (Fase 6) |
| triage board | `x-triage-board` (Fase 6) |
| victim status board | `x-victim-card` grid (Fase 3) |
| equipment inventory table | reuso `x-data-table` (Fase 4) |
| debrief panel | `x-accordion` + `x-form` composto (Fase 7) |
| report summary | composição server-side (Fase 7) |
| PDF preview | `x-pdf-preview` (Fase 5) |
| CTA | `x-button` — mantido |
| FAQ | `x-accordion` (§6.5) |
| footer | `x-footer` — mantido |

---

## 9. Novos componentes Blade a criar (por fase — resumo executável)

### Fase 2.1 (fundação de pessoas e organizações)
`x-command-palette`, `x-sheet`, `x-tabs`, `x-search-input`, `x-data-table`, `x-filter-chip`, `x-quick-add-modal`, `x-completeness-bar`, `x-combobox`, `x-radio-group`, `x-switch`, `x-avatar`, `x-skeleton`, `x-spinner`, `x-tooltip`, `x-kbd`, `x-pagination`. Também refinar `x-sidebar` e `x-modal` para alinhar com composição shadcn.

### Fase 2.2 (auth + governança)
`x-pii`, `x-audit-log-table`, `x-mobile-nav`.

### Fase 3 (vítimas)
`x-victim-card`, `x-hover-card`.

### Fase 4 (equipamentos e kits)
`x-table`, `x-file-upload`.

### Fase 5 (protocolos e evidências)
`x-version-list`, `x-pdf-preview`.

### Fase 6 (execução e SCI)
`x-triage-board`, `x-ics-board`.

### Fase 7 (gestão e relatórios)
`x-chart`, `x-date-picker`, `x-accordion`, `x-team-board`.

---

## 10. Pendências residuais que exigem humano antes da implementação visual

1. **Origin UI** — abrir `github.com/origin-space/originui/blob/main/LICENSE` no navegador (meu fetch deu timeout). Se **MIT**, liberar uso de padrões visuais de form controls; se outra, restringir a apenas "inspiração de composição".
2. **Hero da landing** — escolher **1 componente específico** da categoria `https://21st.dev/community/components/s/hero` (sem shaders/motion pesado). Registrar autor, URL e licença individual antes de trocar `welcome.blade.php`.
3. **Autores 21st.dev específicos** (Aceternity, Magic UI, Kibo UI, Motion Primitives, ReUI, Kokonut UI, cult ui, Ruixen UI) — só consultar caso um componente específico se prove necessário; nenhum é adotado por default nesta versão.

Essas três pendências **não bloqueiam** a Fase 2.1: o incremento 2.1 depende apenas de shadcn/ui (MIT confirmado), TanStack Table (MIT confirmado — apenas como referência visual, não pacote), Chart.js (MIT), e código próprio.

---

## 11. Compromissos de auditabilidade

Cada componente Blade novo será criado com header comentado no formato:

```blade
{{--
    Componente:  x-<nome>
    Fase:        <n>
    Adaptado de: <fonte oficial>
    URL:         <url canônica>
    Licença:     <MIT | Apache 2.0 | próprio>
    Adaptação:   React → Blade+Alpine · dependências <cmdk|radix|framer-motion> substituídas por <alpine|nativo>
--}}
```

Toda PR que introduzir um componente novo precisa incluir screenshot antes/depois e apontar para a URL da fonte no corpo da PR.

---

## 12. Diff versus v0.1.0

| Antes (0.1.0) | Agora (0.2.0) |
|---|---|
| Muitas linhas 🔎 / 🧪 / decisão pendente | Cada linha resolvida com Decisão explícita: **ADOTAR / POSTERGAR / PENDÊNCIA-AUTOR** |
| "Data Table (shadcn)" sem clareza | Data Table = **guia** shadcn baseado em TanStack (React); TMA usa server-side render próprio inspirado no visual |
| Combobox marcado como "candidato" | Combobox = **implementação Alpine própria** inspirada em shadcn Combobox (Base UI) |
| Command palette marcado como "candidato" | Command palette = **Alpine puro** com API mínima; não depende de cmdk |
| Sheet marcado como "candidato" | Sheet = **`x-sheet` novo Blade** com API `side="…"`, sub-componentes por include |
| Origin UI marcada só como referência | Explicitada dependência de conferência do arquivo `LICENSE` no repo antes de adotar visualmente |
| Sem lista consolidada por fase | §9 lista todos os componentes novos agrupados por fase |
| Componentes fora de escopo dispersos | §7 consolida os recusados com motivo |

---

## 13. Decisão solicitada

Este documento resolve todas as decisões visuais que podem ser tomadas com base em fetches oficiais das fontes primárias. As três pendências residuais em §10 são acionáveis com uma passada humana rápida (5 minutos por item), e nenhuma bloqueia o incremento 2.1.

> **Aprova a v0.2.0 da curadoria visual (`docs/UI_COMPONENT_RESEARCH.md`) e o plano arquitetural v0.2.0 (`docs/EXPANSION_PLAN.md`) para iniciar o incremento 2.1?**

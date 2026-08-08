import './bootstrap';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

// -----------------------------------------------------------------
// Store global — utilitários compartilhados entre componentes.
// -----------------------------------------------------------------
Alpine.store('ui', {
    toast: {
        visible: false,
        kind: 'success',
        message: '',
        show(kind, message, duration = 4200) {
            this.kind = kind;
            this.message = message;
            this.visible = true;
            clearTimeout(this._timer);
            this._timer = setTimeout(() => (this.visible = false), duration);
        },
        hide() {
            this.visible = false;
        },
    },
});

// -----------------------------------------------------------------
// Wizard reutilizável de N passos, com:
//   - salvamento local versionado
//   - garantia de arrays em campos de multi-seleção
//   - validação por etapa (client-side) antes de avançar
//   - mapeamento de erros do servidor → etapa correspondente
//   - foco automático no primeiro campo com erro
//
// Uso:
//   <div x-data="wizard({
//       steps: 8,
//       storageKey: 'tsl:new-scenario',
//       version: 2,
//       arrayFields: ['resources'],
//       validators: { 2: () => data.environment ? true : 'Descreva o ambiente.' },
//       fieldToStep: { environment: 2, threat_level: 3, mechanism: 5 },
//       initialErrorField: 'environment',   // vindo do servidor via Blade
//       initialErrorMessage: 'Campo obrigatório.',
//   })">
// -----------------------------------------------------------------
Alpine.data('wizard', ({
    steps = 1,
    storageKey = null,
    version = 1,
    arrayFields = [],
    validators = {},
    fieldToStep = {},
    initialErrorField = null,
    initialErrorMessage = null,
} = {}) => ({
    step: 1,
    total: steps,
    storageKey,
    version,
    arrayFields,
    validators,
    fieldToStep,
    data: {},
    errors: {},           // client-side: { field: 'mensagem' }
    stepErrors: {},       // client-side: { step: 'mensagem' }

    init() {
        this.hydrate();
        this.ensureArrayFields();

        if (this.storageKey) {
            this.$watch('data', () => this.persist(), { deep: true });
            this.$watch('step', () => this.persist());
        }

        // Se o servidor devolveu erro em algum campo mapeado, salta
        // para a etapa correspondente e foca no campo.
        if (initialErrorField && this.fieldToStep[initialErrorField]) {
            const targetStep = this.fieldToStep[initialErrorField];
            this.step = targetStep;
            this.stepErrors[targetStep] = initialErrorMessage || 'Corrija este campo.';
            this.$nextTick(() => this.focusField(initialErrorField));
        }
    },

    hydrate() {
        if (!this.storageKey) return;
        try {
            const raw = localStorage.getItem(this.storageKey);
            if (!raw) return;
            const saved = JSON.parse(raw);
            if (typeof saved !== 'object' || saved === null || saved.version !== this.version) {
                localStorage.removeItem(this.storageKey);
                return;
            }
            this.step = Number.isInteger(saved.step) && saved.step >= 1 && saved.step <= this.total
                ? saved.step
                : 1;
            this.data = (typeof saved.data === 'object' && saved.data !== null) ? saved.data : {};
        } catch (_) {
            try { localStorage.removeItem(this.storageKey); } catch (_) {}
        }
    },

    ensureArrayFields() {
        for (const key of this.arrayFields) {
            if (!Array.isArray(this.data[key])) this.data[key] = [];
        }
    },

    persist() {
        if (!this.storageKey) return;
        try {
            localStorage.setItem(this.storageKey, JSON.stringify({
                version: this.version,
                step: this.step,
                data: this.data,
            }));
        } catch (_) { /* quota / modo privado */ }
    },

    // Limpa o rascunho de forma segura — chamado após criação bem-sucedida.
    clearDraft() {
        if (!this.storageKey) return;
        try { localStorage.removeItem(this.storageKey); } catch (_) {}
    },

    reset() {
        this.step = 1;
        this.data = {};
        this.errors = {};
        this.stepErrors = {};
        this.ensureArrayFields();
        this.clearDraft();
    },

    // Roda o validator do passo `n`. Retorna true se ok, string se erro.
    validate(n) {
        const fn = this.validators[n];
        if (typeof fn !== 'function') return true;
        // O validator recebe `data` e retorna true | 'mensagem'.
        const result = fn.call(this, this.data);
        if (result === true || result === undefined || result === null) {
            delete this.stepErrors[n];
            return true;
        }
        this.stepErrors[n] = typeof result === 'string' ? result : 'Preencha os campos obrigatórios.';
        return false;
    },

    // Valida todas as etapas anteriores ou igual a `n`.
    validateUpTo(n) {
        let firstBadStep = null;
        for (let i = 1; i <= n; i++) {
            if (!this.validate(i) && firstBadStep === null) firstBadStep = i;
        }
        return firstBadStep;
    },

    canAdvance() {
        return this.validate(this.step);
    },

    goTo(n) {
        // Permitir ir para trás sem validar; ir para frente só se anterior ok.
        if (n < this.step) {
            this.step = Math.max(1, n);
            return;
        }
        const bad = this.validateUpTo(n - 1);
        this.step = bad !== null ? bad : Math.min(n, this.total);
    },

    next() {
        if (!this.canAdvance()) {
            this.$nextTick(() => this.focusFirstErrorInStep());
            return;
        }
        if (this.step < this.total) this.step++;
    },

    prev() { if (this.step > 1) this.step--; },

    isFirst() { return this.step === 1; },
    isLast()  { return this.step === this.total; },
    progress(){ return Math.round((this.step / this.total) * 100); },

    // Foca em um campo específico dentro do form (por name).
    focusField(name) {
        const el = document.querySelector(`[name="${name}"], [name="${name}[]"]`);
        if (el && typeof el.focus === 'function') {
            el.focus({ preventScroll: false });
            if (typeof el.select === 'function') el.select();
        }
    },

    // Foca no primeiro campo obrigatório do step atual sem valor.
    focusFirstErrorInStep() {
        const requiredNames = Object.entries(this.fieldToStep)
            .filter(([, s]) => s === this.step)
            .map(([name]) => name);
        for (const name of requiredNames) {
            const val = this.data[name];
            const empty = val === undefined || val === null || val === '' ||
                (Array.isArray(val) && val.length === 0);
            if (empty) { this.focusField(name); return; }
        }
    },

    // Handler do submit: valida TODOS os passos antes de deixar o form
    // enviar. Se algo estiver inválido, salta para o passo do erro.
    onSubmit(event) {
        const bad = this.validateUpTo(this.total);
        if (bad !== null) {
            event.preventDefault();
            this.step = bad;
            this.$nextTick(() => this.focusFirstErrorInStep());
            Alpine.store('ui').toast.show(
                'error',
                this.stepErrors[bad] || 'Corrija os campos destacados para continuar.',
            );
            return false;
        }
        // Ok — não limpar localStorage AQUI. Só limpamos após a resposta
        // bem-sucedida do servidor (ver `show.blade.php`).
        return true;
    },
}));

window.Alpine = Alpine;
Alpine.start();

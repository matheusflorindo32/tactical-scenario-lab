import './bootstrap';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

// -----------------------------------------------------------------
// Store global — utilitários compartilhados entre componentes.
// -----------------------------------------------------------------
Alpine.store('ui', {
    // controla toasts efêmeros (feedback de flash session)
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
// Wizard reutilizável de N passos.
// Uso: <div x-data="wizard({ steps: 8, storageKey: 'tsl:new-scenario' })">
// -----------------------------------------------------------------
Alpine.data('wizard', ({ steps = 1, storageKey = null } = {}) => ({
    step: 1,
    total: steps,
    storageKey,
    data: {},

    init() {
        if (this.storageKey) {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (raw) {
                    const saved = JSON.parse(raw);
                    this.step = saved.step || 1;
                    this.data = saved.data || {};
                }
            } catch (_) { /* ignora storage corrompido */ }

            this.$watch('data', () => this.persist(), { deep: true });
            this.$watch('step', () => this.persist());
        }
    },

    persist() {
        if (!this.storageKey) return;
        try {
            localStorage.setItem(this.storageKey, JSON.stringify({
                step: this.step,
                data: this.data,
            }));
        } catch (_) { /* quota / privacy mode */ }
    },

    reset() {
        this.step = 1;
        this.data = {};
        if (this.storageKey) localStorage.removeItem(this.storageKey);
    },

    goTo(n)   { if (n >= 1 && n <= this.total) this.step = n; },
    next()    { if (this.step < this.total)   this.step++; },
    prev()    { if (this.step > 1)            this.step--; },

    isFirst() { return this.step === 1; },
    isLast()  { return this.step === this.total; },
    progress(){ return Math.round((this.step / this.total) * 100); },
}));

window.Alpine = Alpine;
Alpine.start();

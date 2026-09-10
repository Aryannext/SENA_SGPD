/**
 * SGPD — Core JavaScript
 * Toast notifications, utility functions, AJAX helpers.
 */

const APP = {
    basePath: '/SENA_SGPD',

    /**
     * Escapa una cadena para insertarla en HTML, tanto en texto como dentro de
     * un atributo entrecomillado. TODO dato proveniente de la base de datos
     * debe pasar por aquí antes de llegar a innerHTML.
     * @param {*} value
     * @returns {string}
     */
    esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    /**
     * Convierte Markdown a HTML saneado. La respuesta del modelo de IA es
     * contenido no confiable: puede incluir datos de la BD inyectados en el
     * prompt, así que se sanea siempre antes de renderizar.
     * @param {string} markdown
     * @returns {string}
     */
    mdSafe(markdown) {
        if (typeof marked === 'undefined') return this.esc(markdown);
        const html = marked.parse(markdown);
        return (typeof DOMPurify !== 'undefined') ? DOMPurify.sanitize(html) : this.esc(markdown);
    },

    /**
     * Show a toast notification.
     * @param {string} message
     * @param {'success'|'error'|'info'} type
     * @param {number} duration ms
     */
    toast(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${this.esc(message)}`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /**
     * AJAX GET returning JSON.
     */
    async get(url) {
        const res = await fetch(this.basePath + url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    },

    /**
     * AJAX POST with JSON body, returning JSON.
     */
    async post(url, data = {}) {
        const res = await fetch(this.basePath + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    },

    /**
     * AJAX POST with FormData (file uploads).
     */
    async postForm(url, formData) {
        const res = await fetch(this.basePath + url, {
            method: 'POST',
            body: formData,
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    },

    /**
     * Animate a counter from 0 to value.
     * @param {HTMLElement} el
     * @param {number} target
     * @param {string} suffix
     */
    animateCounter(el, target, suffix = '') {
        const duration = 1200;
        const start = performance.now();
        const step = (now) => {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(eased * target) + suffix;
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },

    /**
     * Format number with locale.
     */
    formatNumber(n) {
        return new Intl.NumberFormat('es-CO').format(n);
    },

    /**
     * Shorten long text.
     */
    truncate(str, len = 60) {
        return str.length > len ? str.substring(0, len) + '...' : str;
    }
};

// Auto-animate stat counters on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseFloat(el.dataset.counter);
        const suffix = el.dataset.suffix || '';
        APP.animateCounter(el, target, suffix);
    });
});

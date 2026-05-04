/**
 * SGPD — Core JavaScript
 * Toast notifications, utility functions, AJAX helpers.
 */

const APP = {
    basePath: '/SENA_SGPD',

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
        toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${message}`;
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

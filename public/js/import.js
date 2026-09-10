/**
 * Import JS — Drag & drop + AJAX upload.
 */
document.addEventListener('DOMContentLoaded', () => {
    const zone     = document.getElementById('upload-zone');
    const input    = document.getElementById('file-input');
    const progress = document.getElementById('import-progress');
    const bar      = document.getElementById('progress-bar');
    const results  = document.getElementById('import-results');
    const pText    = document.getElementById('progress-text');

    if (!zone) return;

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', () => { if (input.files.length) handleFile(input.files[0]); });

    async function handleFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xls', 'xlsx'].includes(ext)) {
            APP.toast('Solo se permiten archivos .xls o .xlsx', 'error');
            return;
        }

        zone.style.display = 'none';
        progress.style.display = 'block';
        bar.style.width = '30%';
        pText.textContent = `Procesando ${file.name}...`;

        const formData = new FormData();
        formData.append('excel_file', file);

        try {
            bar.style.width = '60%';
            const res = await APP.postForm('/api/import/upload', formData);
            bar.style.width = '100%';

            if (res.success) {
                pText.textContent = '¡Importación exitosa!';
                setTimeout(() => {
                    progress.style.display = 'none';
                    showResults(res.data);
                }, 500);
                APP.toast('Datos importados correctamente', 'success');
            } else {
                APP.toast(res.message || 'Error en la importación', 'error');
                zone.style.display = '';
                progress.style.display = 'none';
            }
        } catch (e) {
            APP.toast('Error de conexión: ' + e.message, 'error');
            zone.style.display = '';
            progress.style.display = 'none';
        }
    }

    function showResults(data) {
        results.style.display = 'block';
        const statsGrid = document.getElementById('import-stats');
        const errorsDiv = document.getElementById('import-errors');

        const s = data.stats;
        const items = [
            { icon: 'fa-file-alt',      label: 'Filas Procesadas', value: s.filas_procesadas, color: 'blue' },
            { icon: 'fa-user-graduate',  label: 'Aprendices',      value: s.aprendices,       color: 'green' },
            { icon: 'fa-graduation-cap', label: 'Competencias',    value: s.competencias,     color: 'purple' },
            { icon: 'fa-book',           label: 'Resultados',      value: s.resultados,       color: 'blue' },
            { icon: 'fa-check-circle',   label: 'Calificaciones',  value: s.calificaciones,   color: 'green' },
            { icon: 'fa-user-tie',       label: 'Funcionarios',    value: s.funcionarios,      color: 'orange' },
            { icon: 'fa-exclamation-triangle', label: 'Errores',   value: s.errores,          color: 'red' },
        ];

        statsGrid.innerHTML = items.map(i => `
            <div class="stat-card animate-in">
                <div class="stat-icon ${i.color}"><i class="fas ${i.icon}"></i></div>
                <div class="stat-value" data-counter="${i.value}">${i.value}</div>
                <div class="stat-label">${i.label}</div>
            </div>
        `).join('');

        if (data.errors && data.errors.length > 0) {
            errorsDiv.innerHTML = `
                <div class="card" style="border-color:var(--danger);">
                    <div class="card-header"><span class="card-title" style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Errores</span></div>
                    <div style="max-height:200px;overflow-y:auto;font-size:12px;color:var(--text-muted);">
                        ${data.errors.map(e => `<div style="padding:4px 0;border-bottom:1px solid var(--border);">${APP.esc(e)}</div>`).join('')}
                    </div>
                </div>
            `;
        }

        // Animate counters
        document.querySelectorAll('[data-counter]').forEach(el => {
            APP.animateCounter(el, parseInt(el.dataset.counter));
        });
    }
});

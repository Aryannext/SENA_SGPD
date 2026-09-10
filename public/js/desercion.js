const Desercion = {
    chartMotivos: null,
    chartInstructores: null,

    init() {
        this.fetchStats();
        this.fetchPrediccion();
    },

    async fetchStats() {
        try {
            const res = await fetch('/SENA_SGPD/api/desercion/stats');
            const data = await res.json();
            
            this.renderMotivosChart(data.motivos);
            this.renderInstructoresChart(data.instructores);
            this.renderAuditoriaTable(data.auditoria);
        } catch (error) {
            console.error('Error fetching desercion stats:', error);
            document.getElementById('table-body').innerHTML = `
                <tr><td colspan="6" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><h3>Error cargando datos</h3></td></tr>
            `;
        }
    },

    async fetchPrediccion() {
        try {
            const res = await fetch('/SENA_SGPD/api/desercion/predecir');
            const data = await res.json();
            
            const aiText = document.getElementById('ai-prediction');
            if (data.error) {
                aiText.innerHTML = `<i class="fas fa-exclamation-circle text-danger"></i> ${APP.esc(data.error)}`;
            } else {
                aiText.innerHTML = `<i class="fas fa-magic" style="color:var(--accent);"></i> ${APP.esc(data.prediccion)}`;
            }
        } catch (error) {
            console.error('Error fetching AI prediction:', error);
            document.getElementById('ai-prediction').innerHTML = `<i class="fas fa-exclamation-triangle text-danger"></i> No se pudo conectar con SENA-IA.`;
        }
    },

    renderMotivosChart(motivos) {
        const ctx = document.getElementById('chart-motivos');
        if (!ctx) return;

        if (this.chartMotivos) this.chartMotivos.destroy();

        // Sin datos no se dibuja nada: un segmento ficticio se lee como un dato
        // real. Se muestra un estado vacío explícito en su lugar.
        if (!motivos || motivos.length === 0) {
            this.mostrarVacio(ctx, 'No hay retiros registrados');
            return;
        }

        const labels = motivos.map(m => m.motivo);
        const data = motivos.map(m => m.cantidad);

        this.chartMotivos = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#00f0ff', '#ff3366', '#a200ff', '#ffcc00', '#00ffaa'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#a0aec0', padding: 20 } }
                }
            }
        });
    },

    renderInstructoresChart(instructores) {
        const ctx = document.getElementById('chart-instructores');
        if (!ctx) return;

        if (this.chartInstructores) this.chartInstructores.destroy();

        if (!instructores || instructores.length === 0) {
            this.mostrarVacio(ctx, 'Sin retiros atribuibles a un instructor');
            return;
        }

        const labels = instructores.map(i => i.nombre);
        const data = instructores.map(i => i.total_retiros);

        this.chartInstructores = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cantidad de Aprendices Retirados',
                    data: data,
                    backgroundColor: 'rgba(255, 51, 102, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0aec0' } },
                    x: { grid: { display: false }, ticks: { color: '#a0aec0' } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    },

    /** Sustituye un lienzo por un mensaje, en vez de dibujar datos inventados. */
    mostrarVacio(canvas, mensaje) {
        const contenedor = canvas.parentElement;
        if (!contenedor) return;
        canvas.style.display = 'none';
        let aviso = contenedor.querySelector('.estado-vacio');
        if (!aviso) {
            aviso = document.createElement('div');
            aviso.className = 'estado-vacio empty-state';
            aviso.style.cssText = 'padding:40px 20px;text-align:center;color:var(--text-muted);';
            contenedor.appendChild(aviso);
        }
        aviso.innerHTML = '<i class="fas fa-check-circle" style="font-size:28px;color:var(--success);margin-bottom:10px;display:block;"></i>' + APP.esc(mensaje);
    },

    renderAuditoriaTable(auditoria) {
        const tbody = document.getElementById('table-body');
        if (!auditoria || auditoria.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="empty-state"><i class="fas fa-check-circle text-success"></i><h3>No hay retiros registrados</h3></td></tr>`;
            return;
        }

        tbody.innerHTML = auditoria.map(row => `
            <tr>
                <td>${new Date(row.fecha).toLocaleDateString()}</td>
                <td><span class="badge badge-secondary">${APP.esc(row.nu_documento)}</span></td>
                <td style="font-weight: 500;">${APP.esc(row.aprendiz)}</td>
                <td>${APP.esc(row.fase_abandono || 'N/A')}</td>
                <td><span class="badge badge-warning">${APP.esc(row.motivo || 'No especificado')}</span></td>
                <td><i class="fas fa-chalkboard-teacher" style="color:var(--text-muted); margin-right:5px;"></i> ${APP.esc(row.instructor_responsable || 'Desconocido')}</td>
            </tr>
        `).join('');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Desercion.init();
});

/**
 * Dashboard JS — Loads stats, renders charts, handles filters.
 */
const Dashboard = {
    charts: {},
    data: null,

    async init() {
        try {
            this.data = await APP.get('/api/dashboard/stats');
            this.renderStats();
            this.renderCharts();
            this.renderTable(this.data.avance_por_aprendiz);
            this.populateFilters();
        } catch (e) {
            console.error('Dashboard load error:', e);
            APP.toast('Error cargando el dashboard. ¿Hay datos importados?', 'error');
        }
    },

    renderStats() {
        const d = this.data;
        APP.animateCounter(document.getElementById('stat-total'), d.total_aprendices);
        APP.animateCounter(document.getElementById('stat-aprobados'), d.total_aprobados);
        APP.animateCounter(document.getElementById('stat-pendientes'), d.total_por_evaluar);
        APP.animateCounter(document.getElementById('stat-porcentaje'), d.porcentaje_global, '%');
    },

    renderCharts() {
        this.renderAvanceChart();
        this.renderJuiciosChart();
        this.renderCompetenciasChart();
    },

    renderAvanceChart() {
        const ctx = document.getElementById('chart-avance-aprendiz');
        if (!ctx) return;

        const data = this.data.avance_por_aprendiz.slice(0, 15);
        const labels = data.map(a => `${a.nombre} ${a.apellido}`.substring(0, 20));
        const values = data.map(a => parseFloat(a.porcentaje_avance) || 0);

        if (this.charts.avance) this.charts.avance.destroy();

        this.charts.avance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: '% Avance',
                    data: values,
                    backgroundColor: values.map(v =>
                        v >= 70 ? 'rgba(57,211,83,0.7)' :
                        v >= 40 ? 'rgba(245,158,11,0.7)' :
                                  'rgba(239,68,68,0.7)'
                    ),
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.parsed.x}% avance`
                        }
                    }
                },
                scales: {
                    x: {
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { color: '#94a3b8', callback: v => v + '%' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    }
                }
            }
        });
    },

    renderJuiciosChart() {
        const ctx = document.getElementById('chart-juicios');
        if (!ctx) return;

        const juicios = this.data.juicios_por_tipo || [];
        const labels = juicios.map(j => j.jui_evaluativo);
        const values = juicios.map(j => parseInt(j.total));
        const colors = labels.map(l =>
            l === 'APROBADO' ? '#39d353' :
            l === 'POR EVALUAR' ? '#f59e0b' : '#ef4444'
        );

        if (this.charts.juicios) this.charts.juicios.destroy();

        this.charts.juicios = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', padding: 16, font: { size: 12 } }
                    }
                }
            }
        });
    },

    renderCompetenciasChart() {
        const ctx = document.getElementById('chart-competencias');
        if (!ctx) return;

        const data = this.data.avance_por_competencia || [];
        const labels = data.map(c => c.cod_competencia);
        const pcts   = data.map(c => parseFloat(c.porcentaje) || 0);

        if (this.charts.comp) this.charts.comp.destroy();

        this.charts.comp = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: '% Aprobación',
                    data: pcts,
                    backgroundColor: 'rgba(59,130,246,0.6)',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => {
                                const idx = items[0].dataIndex;
                                return APP.truncate(data[idx].nombre_competencia, 80);
                            },
                            label: ctx => `${ctx.parsed.y}% aprobación`
                        }
                    }
                },
                scales: {
                    y: {
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { color: '#94a3b8', callback: v => v + '%' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 45 }
                    }
                }
            }
        });
    },

    renderTable(data) {
        const tbody = document.getElementById('table-body');
        const count = document.getElementById('table-count');
        if (!tbody) return;

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><i class="fas fa-database"></i><h3>No hay datos</h3><p>Importa un archivo Excel primero</p></td></tr>';
            if (count) count.textContent = '0 registros';
            return;
        }

        if (count) count.textContent = `${data.length} registros`;

        tbody.innerHTML = data.map(a => {
            const pct = parseFloat(a.porcentaje_avance) || 0;
            const barClass = pct >= 70 ? '' : pct >= 40 ? 'warning' : 'danger';
            const badgeClass = a.estado.includes('FORMACION') ? 'badge-success' :
                               a.estado.includes('RETIRO') ? 'badge-danger' : 'badge-warning';
            return `<tr>
                <td><code>${a.nu_documento}</code></td>
                <td><a href="${APP.basePath}/aprendiz/${a.id_aprendiz}">${a.nombre} ${a.apellido}</a></td>
                <td><span class="badge ${badgeClass}">${a.estado}</span></td>
                <td style="color:var(--accent);font-weight:600;">${a.aprobados}</td>
                <td style="color:var(--warning);font-weight:600;">${a.por_evaluar}</td>
                <td style="font-weight:700;">${pct}%</td>
                <td style="min-width:140px;">
                    <div class="progress-inline">
                        <div class="progress"><div class="progress-bar ${barClass}" style="width:${pct}%"></div></div>
                    </div>
                </td>
                <td><a href="${APP.basePath}/aprendiz/${a.id_aprendiz}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
            </tr>`;
        }).join('');
    },

    populateFilters() {
        const estadoSelect = document.getElementById('filter-estado');
        const compSelect   = document.getElementById('filter-competencia');

        if (estadoSelect && this.data.aprendices_por_estado) {
            this.data.aprendices_por_estado.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.estado;
                opt.textContent = `${e.estado} (${e.total})`;
                estadoSelect.appendChild(opt);
            });
        }

        if (compSelect && this.data.avance_por_competencia) {
            this.data.avance_por_competencia.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_competencia;
                opt.textContent = `${c.cod_competencia} — ${APP.truncate(c.nombre_competencia, 40)}`;
                compSelect.appendChild(opt);
            });
        }
    },

    async applyFilters() {
        const params = new URLSearchParams();
        const estado = document.getElementById('filter-estado').value;
        const comp   = document.getElementById('filter-competencia').value;
        const doc    = document.getElementById('filter-documento').value;
        const q      = document.getElementById('filter-busqueda').value;

        if (estado) params.set('estado', estado);
        if (comp)   params.set('competencia', comp);
        if (doc)    params.set('documento', doc);
        if (q)      params.set('q', q);

        try {
            const result = await APP.get('/api/dashboard/filtrar?' + params.toString());
            
            // 1. Update Table
            this.renderTable(result.tabla);
            
            // 2. Update Charts Data
            // Avance: Re-use the filtered table
            this.data.avance_por_aprendiz = result.tabla;
            
            // Juicios: Sum from the filtered table
            let totalAprobados = 0, totalPorEvaluar = 0;
            result.tabla.forEach(a => {
                totalAprobados += parseInt(a.aprobados) || 0;
                totalPorEvaluar += parseInt(a.por_evaluar) || 0;
            });
            this.data.juicios_por_tipo = [
                { jui_evaluativo: 'APROBADO', total: totalAprobados },
                { jui_evaluativo: 'POR EVALUAR', total: totalPorEvaluar }
            ];
            
            // Competencias: Use the newly returned data
            this.data.avance_por_competencia = result.competencias;
            
            // Re-render charts
            this.renderCharts();
            
            APP.toast(`Filtro aplicado: ${result.tabla.length} resultados`, 'success');
        } catch (e) {
            console.error(e);
            APP.toast('Error aplicando filtros', 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => Dashboard.init());

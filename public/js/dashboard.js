/**
 * Dashboard JS — Loads stats, renders charts, handles filters.
 */
const Dashboard = {
    charts: {},
    data: null,
    fichasData: [],
    idFichaActiva: 0,

    async init() {
        try {
            await this.loadFichas();
            if (this.idFichaActiva > 0) {
                await this.loadDashboardData();
            } else {
                APP.toast('No hay fichas importadas', 'warning');
            }
        } catch (e) {
            console.error('Dashboard load error:', e);
            APP.toast('Error cargando el dashboard.', 'error');
        }
    },

    async loadFichas() {
        const res = await APP.get('/api/dashboard/fichasActivas');
        this.fichasData = res.fichas || [];
        
        const select = document.getElementById('global-ficha-selector');
        if (!select) return;

        select.innerHTML = '<option value="">-- Selecciona una ficha --</option>';
        this.fichasData.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id_ficha;
            opt.textContent = `${f.nu_ficha} - ${f.nombre_programa} (${f.modalidad})`;
            select.appendChild(opt);
        });

        if (this.fichasData.length > 0) {
            // Seleccionar la primera por defecto (la más reciente)
            this.idFichaActiva = this.fichasData[0].id_ficha;
            select.value = this.idFichaActiva;
            this.updateFichaInfoCard();
        }
    },

    updateFichaInfoCard() {
        const f = this.fichasData.find(f => f.id_ficha == this.idFichaActiva);
        const card = document.getElementById('ficha-info-card');
        if (!f) {
            if (card) card.style.display = 'none';
            return;
        }

        if (card) {
            card.style.display = 'block';
            document.getElementById('ficha-info-programa').textContent = f.nombre_programa || 'Programa Desconocido';
            document.getElementById('ficha-info-numero').textContent = f.nu_ficha;
            document.getElementById('ficha-info-modalidad').textContent = f.modalidad || 'PRESENCIAL';
            
            // Format dates simply
            const fmtDate = (d) => {
                if (!d) return 'Sin fecha';
                try {
                    const dt = new Date(d);
                    return dt.toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' });
                } catch(e) { return d; }
            };

            document.getElementById('ficha-info-inicio').textContent = fmtDate(f.fecha_inicio);
            document.getElementById('ficha-info-fin').textContent = fmtDate(f.fecha_fin);
        }
    },

    async changeFicha() {
        const val = document.getElementById('global-ficha-selector').value;
        if (!val) {
            this.idFichaActiva = 0;
            document.getElementById('ficha-info-card').style.display = 'none';
            return;
        }
        
        this.idFichaActiva = val;
        this.updateFichaInfoCard();
        await this.loadDashboardData();
    },

    async loadDashboardData() {
        try {
            const params = new URLSearchParams();
            if (this.idFichaActiva) params.set('id_ficha', this.idFichaActiva);
            
            const estado = document.getElementById('filter-estado')?.value;
            const comp   = document.getElementById('filter-competencia')?.value;
            const doc    = document.getElementById('filter-documento')?.value;
            const q      = document.getElementById('filter-busqueda')?.value;

            if (estado) params.set('estado', estado);
            if (comp)   params.set('competencia', comp);
            if (doc)    params.set('documento', doc);
            if (q)      params.set('q', q);

            this.data = await APP.get(`/api/dashboard/stats?${params.toString()}`);
            this.renderStats();
            this.renderCharts();
            this.renderTable(this.data.avance_por_aprendiz);
            
            // Only populate filters if not already populated
            const estadoSelect = document.getElementById('filter-estado');
            if (estadoSelect && estadoSelect.options.length <= 1) {
                this.populateFilters();
            }
        } catch (e) {
            console.error('Error fetching stats:', e);
            APP.toast('Error obteniendo datos de la ficha.', 'error');
        }
    },

    renderStats() {
        const d = this.data;
        APP.animateCounter(document.getElementById('stat-total'), d.total_aprendices);
        APP.animateCounter(document.getElementById('stat-porcentaje'), d.porcentaje_global, '%');
        
        if (d.insights) {
            APP.animateCounter(document.getElementById('stat-riesgo'), d.insights.en_riesgo);
            APP.animateCounter(document.getElementById('stat-destacados'), d.insights.destacados);
        }

        // Actualizar barra de tiempo
        const tiempoBar = document.getElementById('ficha-info-tiempo-bar');
        const tiempoText = document.getElementById('ficha-info-tiempo');
        if (tiempoBar && tiempoText && d.tiempo_transcurrido !== undefined) {
            tiempoText.textContent = `${d.tiempo_transcurrido}%`;
            tiempoBar.style.width = `${d.tiempo_transcurrido}%`;
            if (d.tiempo_transcurrido > 90) tiempoBar.style.background = 'var(--danger)';
            else if (d.tiempo_transcurrido > 70) tiempoBar.style.background = 'var(--warning)';
            else tiempoBar.style.background = 'var(--accent)';
        }
    },

    renderCharts() {
        this.renderHistograma();
        this.renderFases();
        this.renderTopCompetencias();
        this.renderTopRiesgoList();
    },

    renderHistograma() {
        const ctx = document.getElementById('chart-histograma');
        if (!ctx) return;

        const hist = this.data.insights?.histograma || {};
        const labels = Object.keys(hist);
        const values = Object.values(hist);

        if (this.charts.histograma) this.charts.histograma.destroy();

        this.charts.histograma = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Cantidad de Aprendices',
                    data: values,
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.7)',   // Crítico
                        'rgba(245, 158, 11, 0.7)',  // Rezagado
                        'rgba(59, 130, 246, 0.7)',  // Al Día
                        'rgba(57, 211, 83, 0.7)'    // Adelantado
                    ],
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
                            label: ctx => `${ctx.parsed.y} aprendices`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { color: '#94a3b8', stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 12 } }
                    }
                }
            }
        });
    },

    renderFases() {
        const ctx = document.getElementById('chart-fases');
        if (!ctx) return;

        const fases = this.data.fases || [];
        
        // Manejar Empty State si la ficha no tiene proyecto asignado
        if (fases.length === 0) {
            const container = ctx.parentElement;
            if (this.charts.fases) this.charts.fases.destroy();
            ctx.style.display = 'none';
            
            let emptyMsg = document.getElementById('fases-empty-msg');
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.id = 'fases-empty-msg';
                emptyMsg.style = 'position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; color:var(--text-muted); width:100%;';
                emptyMsg.innerHTML = '<i class="fas fa-project-diagram" style="font-size:32px; color:rgba(255,255,255,0.1); margin-bottom:12px;"></i><br>La Ficha seleccionada no tiene un Proyecto Formativo asignado.<br><a href="'+APP.basePath+'/proyecto" style="color:var(--accent); font-size:12px;">Asignar Proyecto</a>';
                container.style.position = 'relative';
                container.appendChild(emptyMsg);
            }
            emptyMsg.style.display = 'block';
            return;
        } else {
            ctx.style.display = 'block';
            const emptyMsg = document.getElementById('fases-empty-msg');
            if (emptyMsg) emptyMsg.style.display = 'none';
        }

        const labels = fases.map(f => f.nombre_fase);
        const values = fases.map(f => parseFloat(f.porcentaje_cumplimiento) || 0);

        if (this.charts.fases) this.charts.fases.destroy();

        this.charts.fases = new Chart(ctx, {
            type: 'polarArea',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(139, 92, 246, 0.6)',
                        'rgba(59, 130, 246, 0.6)',
                        'rgba(236, 72, 153, 0.6)',
                        'rgba(16, 185, 129, 0.6)',
                        'rgba(245, 158, 11, 0.6)'
                    ],
                    borderColor: 'rgba(15,23,42,1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        ticks: { display: false },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    }
                },
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#94a3b8', font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.r}% completado`
                        }
                    }
                }
            }
        });
    },

    renderTopCompetencias() {
        const ctx = document.getElementById('chart-competencias-criticas');
        if (!ctx) return;

        let comps = this.data.avance_por_competencia || [];
        // Sort ascending to get the lowest ones first
        comps.sort((a, b) => parseFloat(a.porcentaje) - parseFloat(b.porcentaje));
        const top5 = comps.slice(0, 5);

        const labels = top5.map(c => c.cod_competencia);
        const pcts   = top5.map(c => parseFloat(c.porcentaje) || 0);

        if (this.charts.compCriticas) this.charts.compCriticas.destroy();

        this.charts.compCriticas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: '% Aprobación',
                    data: pcts,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderRadius: 4,
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
                            title: (items) => APP.truncate(top5[items[0].dataIndex].nombre_competencia, 60),
                            label: ctx => `${ctx.parsed.x}% aprobación`
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

    renderTopRiesgoList() {
        const list = document.getElementById('table-riesgo-body');
        if (!list) return;

        const riesgos = this.data.insights?.top_riesgo || [];
        if (riesgos.length === 0) {
            list.innerHTML = '<tr><td colspan="2"><div class="empty-state" style="padding:20px; font-size:13px;"><i class="fas fa-check-circle" style="color:var(--success); font-size:24px; margin-bottom:8px;"></i><br>No hay aprendices en riesgo.</div></td></tr>';
            return;
        }

        list.innerHTML = riesgos.map(a => {
            const pct = parseFloat(a.porcentaje_avance) || 0;
            const color = pct < 40 ? 'var(--danger)' : 'var(--warning)';
            return `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                    <td style="padding: 10px 8px;">
                        <div style="font-weight:600;font-size:12px;color:var(--text-bright);">
                            <a href="${APP.basePath}/aprendiz/${a.id_aprendiz}" style="color:inherit;text-decoration:none;">${APP.esc(a.nombre)} ${APP.esc(a.apellido)}</a>
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);">${APP.esc(a.nu_documento)}</div>
                    </td>
                    <td style="text-align:right; font-weight:700; color:${color}; font-size:13px; padding: 10px 8px;">
                        ${pct}%<br>
                        ${a.en_deuda > 0 
                            ? `<a href="#" data-nombre="${APP.esc(a.nombre + ' ' + a.apellido)}" onclick="Dashboard.verDeudas(${a.id_aprendiz}, this.dataset.nombre); return false;" style="font-size:10px; color:var(--danger); text-decoration:underline;">Debe ${a.en_deuda} res.</a>` 
                            : `<span style="font-size:10px; color:var(--text-muted); font-weight:normal;">Al día</span>`
                        }
                    </td>
                </tr>
            `;
        }).join('');
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
                <td><code>${APP.esc(a.nu_documento)}</code></td>
                <td><a href="${APP.basePath}/aprendiz/${a.id_aprendiz}">${APP.esc(a.nombre)} ${APP.esc(a.apellido)}</a></td>
                <td><span class="badge ${badgeClass}">${APP.esc(a.estado)}</span></td>
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

        if (estadoSelect) {
            estadoSelect.innerHTML = '<option value="">Todos</option>';
            
            // Standard states that must always appear
            const standardStates = [
                'EN FORMACION',
                'RETIRO VOLUNTARIO',
                'CANCELADO',
                'TRASLADO',
                'APLAZADO',
                'CONDICIONADO',
                'CERTIFICADO',
                'POR CERTIFICAR'
            ];
            
            const dbStates = this.data.aprendices_por_estado ? this.data.aprendices_por_estado.map(e => e.estado) : [];
            const allStates = [...new Set([...standardStates, ...dbStates])].filter(s => s && s.trim() !== '');

            allStates.forEach(estado => {
                const opt = document.createElement('option');
                opt.value = estado;
                opt.textContent = estado;
                estadoSelect.appendChild(opt);
            });
        }

        if (compSelect && this.data.avance_por_competencia) {
            compSelect.innerHTML = '<option value="">Todas</option>';
            this.data.avance_por_competencia.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_competencia;
                opt.textContent = c.cod_competencia + ' - ' + APP.truncate(c.nombre_competencia, 40);
                compSelect.appendChild(opt);
            });
        }
    },

    quickFilterEstado(estado) {
        document.getElementById('filter-estado').value = estado;
        
        // Highlight active button
        const buttons = document.getElementById('quick-status-filters').querySelectorAll('button');
        buttons.forEach(btn => {
            btn.classList.remove('btn-secondary');
            btn.style.background = '';
            btn.style.color = '';
            
            if ((estado === '' && btn.id === 'btn-filter-all') ||
                (estado === 'EN FORMACION' && btn.id === 'btn-filter-formacion') ||
                (estado === 'RETIRO VOLUNTARIO' && btn.id === 'btn-filter-retiro')) {
                btn.style.background = 'var(--accent)';
                btn.style.color = '#000';
            } else {
                btn.classList.add('btn-secondary');
            }
        });
        
        this.applyFilters();
    },

        async applyFilters() {
        try {
            const params = new URLSearchParams();
            if (this.idFichaActiva) params.set('id_ficha', this.idFichaActiva);
            
            const estado = document.getElementById('filter-estado')?.value;
            const comp   = document.getElementById('filter-competencia')?.value;
            const doc    = document.getElementById('filter-documento')?.value;
            const q      = document.getElementById('filter-busqueda')?.value;

            if (estado) params.set('estado', estado);
            if (comp)   params.set('competencia', comp);
            if (doc)    params.set('documento', doc);
            if (q)      params.set('q', q);

            const res = await APP.get('/api/dashboard/filtrar?' + params.toString());
            this.renderTable(res.tabla);
            APP.toast('Filtros aplicados correctamente', 'success');
        } catch (e) {
            console.error('Error aplicando filtros:', e);
            APP.toast('Error al aplicar filtros.', 'error');
        }
    },

    async verDeudas(idAprendiz, nombre) {
        try {
            const deudas = await APP.get(`/api/dashboard/deudasAprendiz?id_aprendiz=${idAprendiz}`);
            
            // Crear modal
            const overlay = document.createElement('div');
            overlay.style = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px);';
            
            const card = document.createElement('div');
            card.className = 'card animate-in';
            card.style = 'width:90%; max-width:600px; max-height:80vh; display:flex; flex-direction:column; background:var(--bg-card);';
            
            let listHtml = '';
            if (!deudas || deudas.length === 0) {
                listHtml = '<div style="padding:20px;text-align:center;color:var(--text-muted);">No hay resultados pendientes para mostrar.</div>';
            } else {
                listHtml = `<div style="overflow-y:auto; padding:0 20px 20px 20px;">
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                        ${deudas.map(d => `
                            <li style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:12px; border-radius:6px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <div style="font-size:11px; color:var(--accent); font-weight:600; text-transform:uppercase;">${APP.esc(d.competencia)}</div>
                                    ${d.instructor_responsable ? `<div style="font-size:10px; color:var(--text-muted);"><i class="fas fa-user-tie" style="margin-right:4px;"></i>${APP.esc(d.instructor_responsable)}</div>` : ''}
                                </div>
                                <div style="font-size:13px; color:var(--text-bright); line-height:1.4;">${APP.esc(d.resultado)}</div>
                            </li>
                        `).join('')}
                    </ul>
                </div>`;
            }

            card.innerHTML = `
                <div style="padding:20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.05); margin-bottom:16px;">
                    <h3 style="margin:0; font-size:16px; color:var(--text-bright);">Resultados Pendientes</h3>
                    <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px;"><i class="fas fa-times"></i></button>
                </div>
                <div style="padding:0 20px 16px 20px; font-size:13px; color:var(--text-muted);">
                    Mostrando resultados evaluados en la ficha donde <strong>${APP.esc(nombre)}</strong> se encuentra POR EVALUAR.
                </div>
                ${listHtml}
            `;
            
            overlay.appendChild(card);
            document.body.appendChild(overlay);
        } catch (e) {
            console.error('Error al obtener deudas:', e);
            APP.toast('No se pudieron cargar las deudas del aprendiz', 'error');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => Dashboard.init());



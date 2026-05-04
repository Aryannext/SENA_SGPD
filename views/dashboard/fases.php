<div class="page-header animate-in" style="margin-bottom:28px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
        <i class="fas fa-layer-group" style="color:var(--accent);"></i> Dashboard de Fases
    </h1>
    <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Cumplimiento por fase del proyecto formativo, agrupado por ficha</p>
</div>

<div id="fases-container">
    <div class="empty-state">
        <i class="fas fa-spinner fa-spin"></i>
        <h3>Cargando fases...</h3>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('fases-container');
    try {
        const rawData = await APP.get('/api/dashboard/fases-stats');
        if (!rawData || rawData.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-layer-group"></i><h3>No hay fases configuradas</h3><p>Ve a Proyecto Formativo para crear fases y asignar resultados</p></div>';
            return;
        }

        // Group data by ficha
        const fichas = {};
        rawData.forEach(row => {
            const fichaKey = row.nu_ficha || 'SIN_FICHA';
            if (!fichas[fichaKey]) {
                fichas[fichaKey] = {
                    nu_ficha: row.nu_ficha,
                    estado_ficha: row.estado_ficha,
                    nombre_programa: row.nombre_programa,
                    codigo_programa: row.codigo_programa,
                    fases: []
                };
            }
            fichas[fichaKey].fases.push(row);
        });

        const phaseColors = {
            'ANÁLISIS': '#3b82f6',
            'PLANEACIÓN': '#8b5cf6',
            'EJECUCIÓN': '#f59e0b',
            'EVALUACIÓN': '#ef4444'
        };

        let html = '';

        Object.values(fichas).forEach((ficha, fichaIdx) => {
            // Calculate ficha-level totals
            let fichaAprobados = 0, fichaPendientes = 0, fichaTotal = 0;
            ficha.fases.forEach(f => {
                fichaAprobados += parseInt(f.aprobados) || 0;
                fichaPendientes += parseInt(f.pendientes) || 0;
                fichaTotal += parseInt(f.total_calificaciones) || 0;
            });
            const fichaPct = fichaTotal > 0 ? (fichaAprobados * 100.0 / fichaTotal).toFixed(1) : 0;
            const fichaPctColor = fichaPct >= 70 ? '#39d353' : fichaPct >= 40 ? '#f59e0b' : '#ef4444';

            html += `
            <div class="card animate-in" style="margin-bottom:24px;animation-delay:${fichaIdx * 0.15}s;">
                <!-- Ficha Header -->
                <div style="
                    padding:16px 20px;
                    border-bottom:1px solid var(--border);
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    flex-wrap:wrap;
                    gap:12px;
                ">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="
                            width:42px;height:42px;border-radius:10px;
                            background:linear-gradient(135deg, var(--accent), #8b5cf6);
                            display:flex;align-items:center;justify-content:center;
                            font-size:16px;color:white;
                        ">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div>
                            <div style="font-size:16px;font-weight:700;color:var(--text-bright);">
                                Ficha ${ficha.nu_ficha || 'N/A'}
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span><i class="fas fa-graduation-cap" style="margin-right:4px;"></i>${ficha.nombre_programa || 'Sin programa'}</span>
                                ${ficha.codigo_programa ? `<code style="font-size:10px;color:var(--text-muted);background:var(--surface-hover);padding:1px 6px;border-radius:3px;">${ficha.codigo_programa}</code>` : ''}
                                ${ficha.estado_ficha ? `<span class="badge ${ficha.estado_ficha === 'EN EJECUCION' ? 'badge-success' : ficha.estado_ficha === 'TERMINADA' ? 'badge-info' : 'badge-danger'}" style="font-size:9px;padding:2px 6px;">${ficha.estado_ficha}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:22px;font-weight:800;color:${fichaPctColor};">${fichaPct}%</div>
                        <div style="font-size:11px;color:var(--text-muted);">Avance general</div>
                    </div>
                </div>

                <!-- Fases Grid -->
                <div style="padding:16px 20px;">
                    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                        ${ficha.fases.map((f, i) => {
                            const pct = parseFloat(f.porcentaje_cumplimiento) || 0;
                            const color = phaseColors[f.nombre_fase] || '#3b82f6';
                            const barClass = pct >= 70 ? '' : pct >= 40 ? 'warning' : 'danger';
                            return `
                            <div class="fase-card animate-in" onclick="showFaseDetalle(${f.id_fase}, ${f.id_ficha}, '${f.nombre_fase}')" style="animation-delay:${(fichaIdx * 0.15) + (i * 0.05)}s;padding:14px;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                                <div class="fase-header" style="margin-bottom:8px;">
                                    <span class="fase-name" style="font-size:13px;">${f.nombre_fase}</span>
                                    <span class="fase-badge" style="background:${color}20;color:${color};font-size:10px;padding:2px 8px;">${f.nombre_fase}</span>
                                </div>
                                <div class="progress" style="height:6px;margin-bottom:8px;">
                                    <div class="progress-bar ${barClass}" style="width:${pct}%;background:linear-gradient(90deg,${color},${color}aa);"></div>
                                </div>
                                <div style="font-size:18px;font-weight:800;color:var(--text-bright);margin-bottom:8px;">${pct}%</div>
                                <div class="fase-stats" style="gap:8px;">
                                    <div class="fase-stat">
                                        <div class="value" style="color:var(--accent);font-size:14px;">${f.aprobados || 0}</div>
                                        <div class="label" style="font-size:9px;">Aprobados</div>
                                    </div>
                                    <div class="fase-stat">
                                        <div class="value" style="color:var(--warning);font-size:14px;">${f.pendientes || 0}</div>
                                        <div class="label" style="font-size:9px;">Pendientes</div>
                                    </div>
                                    <div class="fase-stat">
                                        <div class="value" style="font-size:14px;">${f.total_resultados_fase || 0}</div>
                                        <div class="label" style="font-size:9px;">Resultados</div>
                                    </div>
                                </div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
            </div>`;
        });

        container.innerHTML = html;
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error cargando fases</h3></div>';
    }
});

// Modal Logic
async function showFaseDetalle(idFase, idFicha, nombreFase) {
    const modal = document.getElementById('fase-modal');
    const title = document.getElementById('fase-modal-title');
    const body = document.getElementById('fase-modal-body');
    
    title.textContent = `Detalles de Fase: ${nombreFase}`;
    body.innerHTML = '<div class="empty-state" style="padding:40px;"><i class="fas fa-spinner fa-spin"></i><p>Cargando aprendices...</p></div>';
    modal.classList.add('show');
    
    try {
        const data = await APP.get(`/api/dashboard/fase-detalle?id_fase=${idFase}&id_ficha=${idFicha}`);
        
        if (!data || !data.aprendices || data.aprendices.length === 0) {
            body.innerHTML = '<div class="empty-state"><i class="fas fa-users-slash"></i><p>No hay aprendices asociados a esta ficha/fase</p></div>';
            return;
        }

        let html = `
        <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Aprendiz</th>
                        <th>Estado</th>
                        <th>Aprobados</th>
                        <th>Avance Fase</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.aprendices.forEach(a => {
            const pct = parseFloat(a.porcentaje_avance) || 0;
            const barClass = pct >= 70 ? '' : pct >= 40 ? 'warning' : 'danger';
            const badgeClass = a.estado.includes('FORMACION') ? 'badge-success' : a.estado.includes('RETIRO') ? 'badge-danger' : 'badge-warning';
            
            html += `
            <tr>
                <td><code>${a.nu_documento}</code></td>
                <td><a href="${APP.basePath}/aprendiz/${a.id_aprendiz}">${a.nombre} ${a.apellido}</a></td>
                <td><span class="badge ${badgeClass}">${a.estado}</span></td>
                <td><strong style="color:var(--accent);">${a.aprobados}</strong> / ${a.total_calificaciones}</td>
                <td style="min-width:120px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;margin-bottom:2px;">
                        <span>${pct}%</span>
                    </div>
                    <div class="progress" style="height:4px;"><div class="progress-bar ${barClass}" style="width:${pct}%"></div></div>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        body.innerHTML = html;
        
    } catch(e) {
        body.innerHTML = '<div class="empty-state" style="color:var(--danger);"><i class="fas fa-times-circle"></i><p>Error de conexión</p></div>';
    }
}

function closeFaseModal() {
    document.getElementById('fase-modal').classList.remove('show');
}
</script>

<!-- Modal Template -->
<div id="fase-modal" class="modal">
    <div class="modal-content animate-in" style="max-width: 800px; border-radius: var(--radius-lg); overflow: hidden; background: var(--bg-card); border: 1px solid var(--border);">
        <div class="modal-header" style="background: var(--bg-secondary); padding: 16px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <h3 id="fase-modal-title" style="margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-layer-group" style="color:var(--accent);"></i> Detalles
            </h3>
            <button onclick="closeFaseModal()" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:18px;"><i class="fas fa-times"></i></button>
        </div>
        <div id="fase-modal-body" style="padding: 0;">
            <!-- Dynamic content -->
        </div>
    </div>
</div>
<style>
.modal {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}
.modal.show {
    opacity: 1;
    visibility: visible;
}
.modal.show .modal-content {
    transform: scale(1);
    opacity: 1;
}
.modal-content {
    transform: scale(0.9);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
}
</style>

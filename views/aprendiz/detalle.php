<?php
$initials = strtoupper(substr($aprendiz['nombre'], 0, 1) . substr($aprendiz['apellido'], 0, 1));
$badgeClass = match(true) {
    str_contains($aprendiz['estado'], 'FORMACION') => 'badge-success',
    str_contains($aprendiz['estado'], 'RETIRO')    => 'badge-danger',
    default => 'badge-warning',
};

// Group calificaciones by competencia
$porCompetencia = [];
foreach ($calificaciones as $cal) {
    $codComp = $cal['cod_competencia'];
    if (!isset($porCompetencia[$codComp])) {
        $porCompetencia[$codComp] = [
            'codigo'     => $codComp,
            'nombre'     => $cal['nombre_competencia'],
            'resultados' => [],
            'total'      => 0,
            'aprobados'  => 0,
        ];
    }
    $porCompetencia[$codComp]['resultados'][] = $cal;
    $porCompetencia[$codComp]['total']++;
    if ($cal['jui_evaluativo'] === 'APROBADO') {
        $porCompetencia[$codComp]['aprobados']++;
    }
}
?>

<div class="aprendiz-profile animate-in">
    <div class="aprendiz-avatar"><?= $initials ?></div>
    <div class="aprendiz-info">
        <h2><?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido']) ?></h2>
        <div class="aprendiz-meta">
            <span><i class="fas fa-id-card"></i> <?= htmlspecialchars($aprendiz['nu_documento']) ?></span>
            <span><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($aprendiz['nombre_programa'] ?? 'N/A') ?></span>
            <span><i class="fas fa-hashtag"></i> Ficha <?= htmlspecialchars($aprendiz['nu_ficha'] ?? 'N/A') ?></span>
            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($aprendiz['estado']) ?></span>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid animate-in" id="aprendiz-stats"></div>

<!-- Charts -->
<div class="grid-2 animate-in" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-bar" style="color:var(--accent);"></i> Avance por Competencia</span>
        </div>
        <div style="position:relative;height:350px;">
            <canvas id="chart-aprendiz-comp"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-doughnut" style="color:var(--info);"></i> Distribución General</span>
        </div>
        <div style="position:relative;height:350px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chart-aprendiz-dist"></canvas>
        </div>
    </div>
</div>

<!-- Seguimiento por Resultado de Aprendizaje (enunciado b4) -->
<div class="card animate-in" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <span class="card-title"><i class="fas fa-list-check" style="color:var(--warning);"></i> Seguimiento por Resultado de Aprendizaje</span>
        <select class="form-control" id="ra-competencia" style="max-width:420px;font-size:12px;">
            <option value="">Todas las competencias</option>
        </select>
    </div>
    <div style="position:relative;height:420px;overflow-y:auto;">
        <canvas id="chart-aprendiz-ra"></canvas>
    </div>
    <div style="padding:10px 4px 0;font-size:11px;color:var(--text-muted);" id="ra-leyenda"></div>
</div>

<!-- Resultados agrupados por Competencia -->
<div class="card animate-in">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list-check" style="color:var(--warning);"></i> Resultados de Aprendizaje por Competencia</span>
        <span style="font-size:12px;color:var(--text-muted);"><?= count($porCompetencia) ?> competencias · <?= count($calificaciones) ?> resultados</span>
    </div>

    <?php foreach ($porCompetencia as $comp): ?>
        <?php
            $pctComp = $comp['total'] > 0 ? round($comp['aprobados'] * 100.0 / $comp['total'], 1) : 0;
            $pctColor = $pctComp >= 70 ? 'var(--accent)' : ($pctComp >= 40 ? 'var(--warning)' : 'var(--danger)');
        ?>
        <div class="competencia-group" style="border-bottom:1px solid var(--border);">
            <!-- Competencia Header (clickable) -->
            <div class="competencia-header" onclick="this.parentElement.classList.toggle('open')" style="
                padding:16px 20px;
                display:flex;
                align-items:center;
                gap:12px;
                cursor:pointer;
                transition:background 0.2s;
            ">
                <i class="fas fa-chevron-right competencia-arrow" style="
                    color:var(--text-muted);
                    font-size:11px;
                    transition:transform 0.2s;
                    min-width:12px;
                "></i>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                        <code style="font-size:11px;color:#cbd5e1;background:#0f172a;border:1px solid #334155;padding:3px 8px;border-radius:6px;font-weight:600;letter-spacing:0.5px;">
                            <?= htmlspecialchars($comp['codigo']) ?>
                        </code>
                        <span style="font-size:13px;font-weight:500;color:var(--text-bright);line-height:1.4;flex:1;">
                            <?= htmlspecialchars(ucfirst(mb_strtolower($comp['nombre']))) ?>
                        </span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:14px;flex-shrink:0;">
                    <div style="width:100px;height:8px;background:var(--surface-hover);border-radius:4px;overflow:hidden;box-shadow:inset 0 1px 3px rgba(0,0,0,0.2);">
                        <div style="width:<?= $pctComp ?>%;height:100%;background:<?= $pctColor ?>;border-radius:4px;transition:width 0.5s;"></div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;">
                        <span style="font-size:13px;font-weight:700;color:<?= $pctColor ?>;line-height:1;">
                            <?= $pctComp ?>%
                        </span>
                        <span style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                            <?= $comp['aprobados'] ?>/<?= $comp['total'] ?> aprobados
                        </span>
                    </div>
                </div>
            </div>

            <!-- Resultados (hidden by default) -->
            <div class="competencia-resultados" style="
                max-height:0;
                overflow:hidden;
                transition:max-height 0.3s ease;
            ">
                <div style="padding:12px 20px 20px 44px;display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($comp['resultados'] as $cal): ?>
                        <?php
                            $juicioClass = match($cal['jui_evaluativo']) {
                                'APROBADO'    => 'badge-success',
                                'NO APROBADO' => 'badge-danger',
                                default       => 'badge-warning',
                            };
                            $nombreRes = ucfirst(mb_strtolower($cal['nombre_resultado'] ?? ''));
                        ?>
                        <div class="resultado-row" style="
                            display:flex;
                            align-items:center;
                            gap:14px;
                            padding:12px 16px;
                            background:rgba(0,0,0,0.15);
                            border-radius:8px;
                            border:1px solid rgba(255,255,255,0.02);
                            font-size:12px;
                            transition:all 0.2s;
                        " onmouseover="this.style.transform='translateX(4px)'; this.style.background='rgba(255,255,255,0.03)'"
                          onmouseout="this.style.transform='translateX(0)'; this.style.background='rgba(0,0,0,0.15)'">
                            <code style="color:#94a3b8;background:#1e293b;padding:3px 6px;border-radius:4px;min-width:60px;font-size:11px;text-align:center;border:1px solid #334155;font-weight:600;">
                                <?= htmlspecialchars($cal['cod_resultado']) ?>
                            </code>
                            <span style="flex:1;color:var(--text-secondary);min-width:0;line-height:1.4;">
                                <?= htmlspecialchars($nombreRes) ?>
                                <?php if (!empty($cal['nombre_funcionario'])): ?>
                                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                        <i class="fas fa-user-tie" style="margin-right:4px;"></i><?= htmlspecialchars($cal['nombre_funcionario']) ?>
                                    </div>
                                <?php endif; ?>
                            </span>
                            <span class="badge <?= $juicioClass ?>" style="font-size:10px;padding:4px 8px;white-space:nowrap;">
                                <?= htmlspecialchars($cal['jui_evaluativo']) ?>
                            </span>
                            <span style="color:var(--text-muted);min-width:90px;font-size:11px;text-align:right;white-space:nowrap;display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                                <?php if ($cal['fecha_registro']): ?>
                                    <i class="far fa-calendar-alt" style="color:var(--accent);"></i>
                                    <?= date('d/m/Y', strtotime($cal['fecha_registro'])) ?>
                                <?php else: ?>
                                    <i class="far fa-clock" style="color:var(--warning);"></i> Pendiente
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.competencia-group:hover .competencia-header {
    background: var(--surface-hover, rgba(255,255,255,0.02));
}
.competencia-group.open .competencia-arrow {
    transform: rotate(90deg);
}
.competencia-group.open .competencia-resultados {
    max-height: 2000px !important;
}
.competencia-group:last-child {
    border-bottom: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await APP.get('/api/aprendiz/<?= $aprendiz['id_aprendiz'] ?>/stats');

        // Render stats cards
        const statsGrid = document.getElementById('aprendiz-stats');
        statsGrid.innerHTML = `
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-book"></i></div><div class="stat-value" data-counter="${data.total}">${data.total}</div><div class="stat-label">Total Resultados</div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div class="stat-value" data-counter="${data.aprobados}">${data.aprobados}</div><div class="stat-label">Aprobados</div></div>
            <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-clock"></i></div><div class="stat-value" data-counter="${data.por_evaluar}">${data.por_evaluar}</div><div class="stat-label">Pendientes</div></div>
            <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-percentage"></i></div><div class="stat-value" data-counter="${data.porcentaje}" data-suffix="%">${data.porcentaje}%</div><div class="stat-label">Avance</div></div>
        `;
        document.querySelectorAll('[data-counter]').forEach(el => {
            APP.animateCounter(el, parseFloat(el.dataset.counter), el.dataset.suffix || '');
        });

        // Competencia chart
        const compCtx = document.getElementById('chart-aprendiz-comp');
        new Chart(compCtx, {
            type: 'bar',
            data: {
                labels: data.por_competencia.map(c => c.codigo),
                datasets: [{
                    label: '% Avance',
                    data: data.por_competencia.map(c => c.porcentaje),
                    backgroundColor: data.por_competencia.map(c =>
                        c.porcentaje >= 70 ? 'rgba(57,211,83,0.7)' :
                        c.porcentaje >= 40 ? 'rgba(245,158,11,0.7)' : 'rgba(239,68,68,0.7)'
                    ),
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { title: items => APP.truncate(data.por_competencia[items[0].dataIndex].nombre, 80) } } },
                scales: { y: { max: 100, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', callback: v => v+'%' } }, x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } } }
            }
        });

        // Distribution donut
        const distCtx = document.getElementById('chart-aprendiz-dist');
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Pendientes'],
                datasets: [{ data: [data.aprobados, data.por_evaluar], backgroundColor: ['#39d353', '#f59e0b'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } } }
        });
        // ── Seguimiento por resultado de aprendizaje (enunciado b4) ─────────
        const COLOR_ESTADO = {
            'APROBADO':    '#39d353',
            'POR EVALUAR': '#f59e0b',
            'NO APROBADO': '#ef4444',
        };

        const selectRA  = document.getElementById('ra-competencia');
        const leyendaRA = document.getElementById('ra-leyenda');
        const ctxRA     = document.getElementById('chart-aprendiz-ra');
        let chartRA = null;

        data.por_competencia.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.codigo;
            opt.textContent = c.codigo + ' - ' + APP.truncate(c.nombre, 60);
            selectRA.appendChild(opt);
        });

        /** Altura proporcional al número de barras: 75 resultados no caben en 420 px. */
        function ajustarAlto(barras) {
            ctxRA.parentElement.style.height = Math.max(300, barras * 26 + 60) + 'px';
        }

        function dibujarRA() {
            const codComp = selectRA.value;
            if (chartRA) chartRA.destroy();

            if (!codComp) {
                // Cumplimiento de cada competencia, contando sus resultados por estado.
                const comps = data.por_competencia.map(c => c.codigo);
                const cuenta = estado => comps.map(cod =>
                    data.calificaciones.filter(x => x.cod_competencia === cod && x.jui_evaluativo === estado).length
                );

                ajustarAlto(comps.length);
                chartRA = new Chart(ctxRA, {
                    type: 'bar',
                    data: {
                        labels: comps,
                        datasets: Object.keys(COLOR_ESTADO).map(estado => ({
                            label: estado,
                            data: cuenta(estado),
                            backgroundColor: COLOR_ESTADO[estado],
                            borderRadius: 3,
                        })),
                    },
                    options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#94a3b8', boxWidth: 12 } },
                            tooltip: { callbacks: { title: items => {
                                const c = data.por_competencia[items[0].dataIndex];
                                return APP.truncate(c.codigo + ' - ' + c.nombre, 80);
                            } } },
                        },
                        scales: {
                            x: { stacked: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', precision: 0 } },
                            y: { stacked: true, grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                        },
                    },
                });
                leyendaRA.textContent = `${data.total} resultados de aprendizaje en ${comps.length} competencias. Elige una competencia para ver sus resultados uno a uno.`;
                return;
            }

            // Una barra por resultado de la competencia elegida.
            const resultados = data.calificaciones.filter(x => x.cod_competencia === codComp);
            ajustarAlto(resultados.length);
            chartRA = new Chart(ctxRA, {
                type: 'bar',
                data: {
                    labels: resultados.map(r => r.cod_resultado),
                    datasets: [{
                        label: 'Estado',
                        data: resultados.map(() => 100),
                        backgroundColor: resultados.map(r => COLOR_ESTADO[r.jui_evaluativo] || '#64748b'),
                        borderRadius: 3,
                    }],
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: {
                            title: items => APP.truncate(resultados[items[0].dataIndex].nombre_resultado, 90),
                            label: item => {
                                const r = resultados[item.dataIndex];
                                return r.jui_evaluativo + (r.nombre_funcionario ? ' · ' + r.nombre_funcionario : '');
                            },
                        } },
                    },
                    scales: {
                        x: { max: 100, grid: { display: false }, ticks: { display: false } },
                        y: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                    },
                },
            });

            const aprobados = resultados.filter(r => r.jui_evaluativo === 'APROBADO').length;
            leyendaRA.textContent = `${aprobados} de ${resultados.length} resultados aprobados en esta competencia. Verde: aprobado · ámbar: por evaluar · rojo: no aprobado.`;
        }

        selectRA.addEventListener('change', dibujarRA);
        dibujarRA();
    } catch (e) {
        console.error(e);
    }
});
</script>

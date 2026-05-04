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
                padding:14px 20px;
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
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <code style="font-size:12px;color:var(--accent);background:var(--accent)15;padding:2px 8px;border-radius:4px;">
                            <?= htmlspecialchars($comp['codigo']) ?>
                        </code>
                        <span style="font-size:13px;font-weight:600;color:var(--text-primary);">
                            <?= htmlspecialchars(mb_substr($comp['nombre'], 0, 120)) ?>
                        </span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                    <div style="width:80px;height:6px;background:var(--surface-hover);border-radius:3px;overflow:hidden;">
                        <div style="width:<?= $pctComp ?>%;height:100%;background:<?= $pctColor ?>;border-radius:3px;transition:width 0.5s;"></div>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:<?= $pctColor ?>;min-width:45px;text-align:right;">
                        <?= $pctComp ?>%
                    </span>
                    <span style="font-size:11px;color:var(--text-muted);min-width:30px;">
                        <?= $comp['aprobados'] ?>/<?= $comp['total'] ?>
                    </span>
                </div>
            </div>

            <!-- Resultados (hidden by default) -->
            <div class="competencia-resultados" style="
                max-height:0;
                overflow:hidden;
                transition:max-height 0.3s ease;
            ">
                <div style="padding:0 20px 12px 44px;">
                    <?php foreach ($comp['resultados'] as $cal): ?>
                        <?php
                            $juicioClass = match($cal['jui_evaluativo']) {
                                'APROBADO'    => 'badge-success',
                                'NO APROBADO' => 'badge-danger',
                                default       => 'badge-warning',
                            };
                        ?>
                        <div style="
                            display:flex;
                            align-items:center;
                            gap:12px;
                            padding:8px 0;
                            border-bottom:1px solid var(--border-subtle, rgba(255,255,255,0.03));
                            font-size:12px;
                        ">
                            <code style="color:var(--text-muted);min-width:60px;font-size:11px;">
                                <?= htmlspecialchars($cal['cod_resultado']) ?>
                            </code>
                            <span style="flex:1;color:var(--text-secondary);min-width:0;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars(mb_substr($cal['nombre_resultado'], 0, 120)) ?>
                            </span>
                            <span class="badge <?= $juicioClass ?>" style="font-size:10px;padding:2px 8px;">
                                <?= htmlspecialchars($cal['jui_evaluativo']) ?>
                            </span>
                            <span style="color:var(--text-muted);min-width:75px;font-size:11px;text-align:right;">
                                <?= $cal['fecha_registro'] ? date('d/m/Y', strtotime($cal['fecha_registro'])) : '-' ?>
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
    } catch (e) {
        console.error(e);
    }
});
</script>

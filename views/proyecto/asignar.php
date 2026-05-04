<div class="page-header animate-in" style="margin-bottom:28px;">
    <div style="display:flex;align-items:center;gap:16px;">
        <a href="/SENA_SGPD/proyecto/detalle?id=<?= $proyecto['id_proyecto'] ?? '' ?>"
           style="color:var(--text-muted);text-decoration:none;font-size:18px;"
           title="Volver al proyecto">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);margin:0;">
                <i class="fas fa-link" style="color:var(--accent);"></i> Asignar Resultados a Fases
            </h1>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                <span style="color:var(--info);"><?= htmlspecialchars($proyecto['nombre_proyecto'] ?? '') ?></span>
                <?php if (!empty($proyecto['nombre_programa'])): ?>
                    — Programa: <?= htmlspecialchars($proyecto['nombre_programa']) ?>
                    (<?= htmlspecialchars($proyecto['codigo_programa'] ?? '') ?>)
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid-2 animate-in">
    <!-- Left: Unassigned -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-clipboard-list" style="color:var(--warning);"></i> Resultados sin Asignar</span>
            <?php 
                $totalNoAsignados = 0;
                foreach ($noAsignados as $list) $totalNoAsignados += count($list);
            ?>
            <span class="badge badge-warning"><?= $totalNoAsignados ?></span>
        </div>
        <div style="max-height:600px;overflow-y:auto;">
            <?php if (empty($noAsignados)): ?>
                <div class="empty-state"><h3>Todos asignados ✓</h3></div>
            <?php else: ?>
                <?php foreach ($noAsignados as $compName => $resultados): ?>
                    <div style="padding:8px 14px; background:rgba(255,255,255,0.05); font-size:11px; font-weight:700; color:var(--accent); border-bottom:1px solid var(--border);">
                        <i class="fas fa-book"></i> <?= htmlspecialchars($compName) ?>
                    </div>
                    <?php foreach ($resultados as $res): ?>
                    <div class="resultado-item" data-id="<?= $res['id_resultado'] ?>"
                         style="padding:10px 14px 10px 24px;border-bottom:1px solid var(--border);cursor:pointer;transition:all 0.2s;"
                         onmouseover="this.style.background='var(--accent-soft)'"
                         onmouseout="this.style.background='transparent'"
                         onclick="selectResultado(<?= $res['id_resultado'] ?>, this)">
                        <div style="font-size:12px;font-weight:600;color:var(--text-primary);">
                            <code><?= htmlspecialchars($res['cod_resultado'] ?? '') ?></code>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                            <?= htmlspecialchars($res['nombre_resultado'] ?? '') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Phases with activities -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-layer-group" style="color:var(--accent);"></i> Fases y Actividades</span>
        </div>
        <div style="max-height:600px;overflow-y:auto;">
            <?php if (empty($fases)): ?>
                <div class="empty-state">
                    <h3>Sin fases</h3>
                    <p style="color:var(--text-muted);font-size:12px;">Suba el PDF del proyecto formativo para generar las fases y actividades.</p>
                </div>
            <?php else: ?>
                <?php foreach ($fases as $fase): ?>
                <div style="margin-bottom:16px;">
                    <div style="font-size:14px;font-weight:700;color:var(--text-bright);padding:8px 14px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                        <?= htmlspecialchars($fase['nombre_fase']) ?>
                    </div>
                    <?php if (!empty($fase['actividades'])): ?>
                        <?php foreach ($fase['actividades'] as $act): ?>
                        <div class="actividad-target" data-id="<?= $act['id_actividad'] ?>"
                             style="margin-left:16px;padding:10px 14px;border-left:2px solid var(--border);cursor:pointer;transition:all 0.2s;"
                             onmouseover="this.style.borderColor='var(--accent)'"
                             onmouseout="this.style.borderColor='var(--border)'"
                             onclick="assignToActividad(<?= $act['id_actividad'] ?>)">
                            <div style="font-size:12px;font-weight:600;color:var(--text-primary);">
                                <i class="fas fa-cogs" style="color:var(--info);font-size:10px;"></i>
                                <?= htmlspecialchars(substr($act['nombre_actividad'], 0, 60)) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="margin-left:16px;padding:8px;font-size:11px;color:var(--text-muted);">Sin actividades</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let selectedResultado = null;
let selectedElement = null;

function selectResultado(id, el) {
    if (selectedElement) selectedElement.style.background = 'transparent';
    selectedResultado = id;
    selectedElement = el;
    el.style.background = 'var(--accent-glow)';
    APP.toast('Resultado seleccionado. Ahora haz clic en una actividad.', 'info');
}

async function assignToActividad(idActividad) {
    if (!selectedResultado) {
        APP.toast('Primero selecciona un resultado de la lista izquierda', 'error');
        return;
    }
    try {
        const res = await APP.post('/api/proyecto/asignar', { id_actividad: idActividad, id_resultado: selectedResultado });
        if (res.success) {
            APP.toast('Resultado asignado correctamente', 'success');
            if (selectedElement) selectedElement.remove();
            selectedResultado = null;
            selectedElement = null;
            // Update counter
            const badge = document.querySelector('.badge-warning');
            if (badge) {
                const count = document.querySelectorAll('.resultado-item').length;
                badge.textContent = count;
            }
        }
    } catch (e) {
        APP.toast('Error al asignar: ' + e.message, 'error');
    }
}
</script>

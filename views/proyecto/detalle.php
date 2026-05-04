<div class="page-header animate-in" style="margin-bottom:28px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
        <a href="/SENA_SGPD/proyecto" class="btn btn-secondary btn-sm" style="border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;padding:0;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
                <i class="fas fa-book-open" style="color:var(--accent);"></i>
                <?= htmlspecialchars($proyecto['nombre_proyecto']) ?>
            </h1>
            <div style="display:flex;align-items:center;gap:16px;margin-top:4px;flex-wrap:wrap;">
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-hashtag" style="color:var(--accent);"></i>
                    Código: <strong style="color:var(--text-bright);"><?= htmlspecialchars($proyecto['codigo_proyecto'] ?? 'N/A') ?></strong>
                </span>
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-graduation-cap" style="color:var(--info);"></i>
                    <?= htmlspecialchars($proyecto['nombre_programa'] ?? 'Sin programa') ?>
                </span>
                <?php if (!empty($proyecto['modalidad'])): ?>
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-desktop"></i> <?= htmlspecialchars($proyecto['modalidad']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Action Bar -->
<div class="card animate-in" style="margin-bottom:20px;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:16px;">
        <?php if (!empty($proyecto['ruta_pdf'])): ?>
        <a href="/SENA_SGPD<?= htmlspecialchars($proyecto['ruta_pdf']) ?>" target="_blank" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-pdf" style="color:var(--danger);"></i> Ver PDF Actual
        </a>
        <?php endif; ?>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('pdf-upload').click()">
            <i class="fas fa-upload"></i> <?= !empty($proyecto['ruta_pdf']) ? 'Resubir PDF' : 'Subir PDF' ?>
        </button>
        <input type="file" id="pdf-upload" accept=".pdf" style="display:none;" onchange="uploadPdf(this)">
    </div>
    <a href="/SENA_SGPD/proyecto/asignar?id=<?= $proyecto['id_proyecto'] ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-link"></i> Asignar Resultados a Fases
    </a>
</div>

<!-- Phases -->
<?php if (empty($fases)): ?>
<div class="card animate-in" style="text-align:center;padding:40px 20px;">
    <div style="font-size:40px;color:var(--text-muted);margin-bottom:12px;"><i class="fas fa-layer-group"></i></div>
    <h3 style="color:var(--text-bright);margin-bottom:8px;">No hay fases configuradas</h3>
    <p style="color:var(--text-muted);font-size:13px;">Sube el PDF del proyecto para extraer automáticamente las fases y actividades.</p>
</div>
<?php else: ?>
    <?php foreach ($fases as $fase): ?>
    <div class="card animate-in phase-card" style="margin-bottom:16px;">
        <div class="card-header" style="cursor:pointer;" onclick="togglePhase(this)">
            <span class="card-title" style="display:flex;align-items:center;gap:10px;">
                <span class="phase-icon"><i class="fas fa-layer-group"></i></span>
                <?= htmlspecialchars($fase['nombre_fase']) ?>
            </span>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="badge badge-info" style="font-size:11px;">
                    <?= count($fase['actividades'] ?? []) ?> actividades
                </span>
                <i class="fas fa-chevron-down phase-chevron" style="color:var(--text-muted);font-size:12px;transition:transform 0.3s;"></i>
            </div>
        </div>

        <div class="phase-body">
        <?php if (!empty($fase['actividades'])): ?>
            <?php foreach ($fase['actividades'] as $act): ?>
            <div class="activity-block">
                <div class="activity-header">
                    <i class="fas fa-cogs" style="color:var(--info);"></i>
                    <span><?= htmlspecialchars($act['nombre_actividad']) ?></span>
                </div>
                <?php if (!empty($act['resultados'])): ?>
                    <?php foreach ($act['resultados'] as $res): ?>
                    <div class="ra-item">
                        <i class="fas fa-check-circle" style="color:var(--accent);font-size:10px;"></i>
                        <code><?= htmlspecialchars($res['cod_resultado'] ?? '') ?></code>
                        — <?= htmlspecialchars(mb_substr($res['nombre_resultado'] ?? '', 0, 90)) ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ra-item" style="color:var(--text-muted);font-style:italic;">Sin resultados asignados</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding:12px 24px;font-size:12px;color:var(--text-muted);">Sin actividades definidas</div>
        <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
.phase-icon {
    width: 32px;
    height: 32px;
    background: var(--accent-soft);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--accent);
}

.phase-body {
    max-height: 2000px;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.3s;
}
.phase-card.collapsed .phase-body {
    max-height: 0;
    padding: 0;
}
.phase-card.collapsed .phase-chevron {
    transform: rotate(-90deg);
}

.activity-block {
    margin: 0 24px;
    padding: 14px 16px;
    border-left: 3px solid var(--border);
    margin-bottom: 8px;
    transition: border-color 0.2s;
}
.activity-block:hover {
    border-left-color: var(--accent);
}
.activity-block:last-child { margin-bottom: 16px; }

.activity-header {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.ra-item {
    margin-left: 16px;
    padding: 3px 0;
    font-size: 12px;
    color: var(--text-secondary);
    display: flex;
    align-items: flex-start;
    gap: 6px;
}
.ra-item code {
    background: rgba(0,0,0,0.2);
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 11px;
    color: var(--accent);
    white-space: nowrap;
}
</style>

<script>
function togglePhase(header) {
    header.closest('.phase-card').classList.toggle('collapsed');
}

async function uploadPdf(input) {
    if (!input.files || input.files.length === 0) return;
    
    const formData = new FormData();
    formData.append('pdf', input.files[0]);
    
    APP.toast('Subiendo y procesando PDF...', 'info');
    
    try {
        const res = await fetch(APP.basePath + '/api/proyecto/upload-pdf', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        if (data.success) {
            APP.toast(data.message || 'PDF procesado correctamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            APP.toast(data.message || 'Error al subir', 'error');
        }
    } catch (e) {
        APP.toast('Error de red al subir PDF', 'error');
    }
}
</script>

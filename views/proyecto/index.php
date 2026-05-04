<div class="page-header animate-in" style="margin-bottom:28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
            <i class="fas fa-project-diagram" style="color:var(--accent);"></i> Proyectos Formativos
        </h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Gestiona tus proyectos formativos por programa</p>
    </div>
    <div>
        <input type="file" id="pdf-upload" accept=".pdf" style="display:none;" onchange="uploadAutomatic(this)">
        <button class="btn btn-primary" onclick="document.getElementById('pdf-upload').click()" id="btn-upload-auto">
            <i class="fas fa-upload"></i> Subir Proyecto (PDF)
        </button>
    </div>
</div>

<?php if (empty($proyectos)): ?>
<div class="card animate-in" style="text-align:center;padding:60px 20px;">
    <div style="font-size:48px;color:var(--text-muted);margin-bottom:16px;">
        <i class="fas fa-project-diagram"></i>
    </div>
    <h3 style="color:var(--text-bright);margin-bottom:8px;">No hay proyectos formativos</h3>
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:24px;">Crea tu primer proyecto para comenzar a organizar las fases y resultados de aprendizaje.</p>
    <button class="btn btn-primary" onclick="document.getElementById('pdf-upload').click()">
        <i class="fas fa-upload"></i> Subir Proyecto Formativo (PDF)
    </button>
</div>
<?php else: ?>
<div class="projects-grid">
    <?php foreach ($proyectos as $p): ?>
    <div class="project-card animate-in" onclick="window.location.href='/SENA_SGPD/proyecto/detalle?id=<?= $p['id_proyecto'] ?>'">
        <div class="project-card-header">
            <div class="project-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="project-badge">
                <?= htmlspecialchars($p['codigo_proyecto'] ?? 'Sin código') ?>
            </div>
        </div>
        <div class="project-card-body">
            <h3 class="project-title"><?= htmlspecialchars($p['nombre_proyecto']) ?></h3>
            <div class="project-program">
                <i class="fas fa-graduation-cap"></i>
                <?= htmlspecialchars($p['nombre_programa'] ?? 'Sin programa asociado') ?>
            </div>
            <?php if (!empty($p['modalidad'])): ?>
            <div class="project-modalidad">
                <i class="fas fa-desktop"></i> <?= htmlspecialchars($p['modalidad']) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="project-card-footer">
            <div class="project-stat">
                <i class="fas fa-layer-group"></i>
                <span><?= $p['num_fases'] ?> Fases</span>
            </div>
            <div class="project-stat">
                <i class="fas fa-link"></i>
                <span><?= $p['num_resultados'] ?> RA vinculados</span>
            </div>
            <div class="project-stat pdf-status <?= !empty($p['ruta_pdf']) ? 'has-pdf' : '' ?>">
                <i class="fas fa-file-pdf"></i>
                <span><?= !empty($p['ruta_pdf']) ? 'PDF cargado' : 'Sin PDF' ?></span>
            </div>
        </div>
        <div class="project-card-action">
            <span>Ver Detalles</span>
            <i class="fas fa-arrow-right"></i>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal removed for automatic upload -->

<style>
/* Projects Grid */
.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
}

/* Project Card */
.project-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
}
.project-card:hover {
    transform: translateY(-6px);
    border-color: var(--accent);
    box-shadow: 0 12px 40px rgba(57, 211, 83, 0.15);
}

.project-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 12px;
}

.project-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--accent), #10b981);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    box-shadow: 0 4px 15px rgba(57, 211, 83, 0.3);
}

.project-badge {
    background: var(--accent-soft);
    color: var(--accent);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

.project-card-body {
    padding: 8px 24px 16px;
}

.project-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-bright);
    margin-bottom: 10px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.project-program {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}
.project-program i { color: var(--info); font-size: 11px; }

.project-modalidad {
    font-size: 11px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}
.project-modalidad i { color: var(--text-muted); font-size: 10px; }

.project-card-footer {
    display: flex;
    gap: 16px;
    padding: 14px 24px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
}

.project-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 500;
}
.project-stat i { font-size: 10px; color: var(--text-muted); }
.project-stat.has-pdf i { color: var(--accent); }
.project-stat.has-pdf { color: var(--accent); }

.project-card-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border);
    font-size: 12px;
    font-weight: 600;
    color: var(--accent);
    transition: background 0.2s;
}
.project-card:hover .project-card-action {
    background: var(--accent-soft);
}



@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 600px) {
    .projects-grid { grid-template-columns: 1fr; }
    .modal-content { margin: 20px; }
}
</style>

<script>
async function uploadAutomatic(input) {
    if (!input.files || input.files.length === 0) return;
    
    const file = input.files[0];
    if (file.type !== 'application/pdf') {
        APP.toast('Solo se permiten archivos PDF', 'error');
        input.value = '';
        return;
    }

    const btn = document.getElementById('btn-upload-auto');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando PDF...';

    const formData = new FormData();
    formData.append('pdf', file);

    try {
        const response = await fetch('/SENA_SGPD/api/proyecto/upload-pdf', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            APP.toast(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            APP.toast(result.message || 'Error al procesar el PDF', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
            input.value = '';
        }
    } catch (e) {
        APP.toast('Error de conexión al procesar el PDF', 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
        input.value = '';
    }
}
</script>

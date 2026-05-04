<div class="page-header animate-in" style="margin-bottom:28px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
        <i class="fas fa-graduation-cap" style="color:var(--accent);"></i> Programas de Formación
    </h1>
    <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Gestiona los programas, fichas y aprendices</p>
</div>

<?php if (empty($programas)): ?>
<div class="card animate-in" style="text-align:center;padding:60px 20px;">
    <div style="font-size:48px;color:var(--text-muted);margin-bottom:16px;"><i class="fas fa-graduation-cap"></i></div>
    <h3 style="color:var(--text-bright);margin-bottom:8px;">No hay programas registrados</h3>
    <p style="color:var(--text-muted);font-size:13px;">Importa un archivo Excel de SOFIA Plus para registrar programas automáticamente.</p>
</div>
<?php else: ?>
<div class="programs-grid">
    <?php foreach ($programas as $p): ?>
    <div class="program-card animate-in" onclick="window.location.href='/SENA_SGPD/programa/detalle?id=<?= $p['id_programa'] ?>'">
        <!-- Gradient top bar -->
        <div class="program-card-gradient <?= strtolower(str_replace(' ', '-', $p['modalidad'] ?? 'presencial')) ?>"></div>

        <div class="program-card-body">
            <div class="program-card-top">
                <div class="program-icon-wrap">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="program-modalidad-badge"><?= htmlspecialchars($p['modalidad'] ?? 'N/A') ?></span>
            </div>

            <h3 class="program-name"><?= htmlspecialchars($p['nombre_programa']) ?></h3>
            <div class="program-code"><i class="fas fa-hashtag"></i> <?= htmlspecialchars($p['codigo_programa']) ?></div>

            <div class="program-stats-grid">
                <div class="pstat">
                    <div class="pstat-value"><?= (int) $p['total_fichas'] ?></div>
                    <div class="pstat-label">Fichas</div>
                </div>
                <div class="pstat">
                    <div class="pstat-value"><?= (int) $p['total_aprendices'] ?></div>
                    <div class="pstat-label">Aprendices</div>
                </div>
                <div class="pstat">
                    <div class="pstat-value accent"><?= (int) $p['activos'] ?></div>
                    <div class="pstat-label">Activos</div>
                </div>
                <div class="pstat">
                    <div class="pstat-value danger"><?= (int) $p['retirados'] ?></div>
                    <div class="pstat-label">Retirados</div>
                </div>
            </div>
        </div>

        <div class="program-card-action">
            <span>Ver Fichas y Aprendices</span>
            <i class="fas fa-arrow-right"></i>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.programs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 20px;
}

.program-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
}
.program-card:hover {
    transform: translateY(-6px);
    border-color: var(--accent);
    box-shadow: 0 12px 40px rgba(57, 211, 83, 0.15);
}

.program-card-gradient {
    height: 4px;
    background: linear-gradient(90deg, var(--accent), #10b981, #3b82f6);
}
.program-card-gradient.virtual {
    background: linear-gradient(90deg, #8b5cf6, #6366f1);
}
.program-card-gradient.a-distancia {
    background: linear-gradient(90deg, #f59e0b, #f97316);
}

.program-card-body {
    padding: 24px;
}

.program-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.program-icon-wrap {
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

.program-modalidad-badge {
    background: var(--accent-soft);
    color: var(--accent);
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.program-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-bright);
    margin-bottom: 4px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.program-code {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.program-code i { font-size: 10px; color: var(--accent); }

.program-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.pstat {
    text-align: center;
    padding: 10px 4px;
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--border);
}
.pstat-value {
    font-size: 20px;
    font-weight: 800;
    color: var(--text-bright);
    line-height: 1;
}
.pstat-value.accent { color: var(--accent); }
.pstat-value.danger { color: var(--danger); }
.pstat-label {
    font-size: 10px;
    color: var(--text-muted);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.program-card-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border);
    font-size: 12px;
    font-weight: 600;
    color: var(--accent);
    transition: background 0.2s;
}
.program-card:hover .program-card-action {
    background: var(--accent-soft);
}

@media (max-width: 600px) {
    .programs-grid { grid-template-columns: 1fr; }
    .program-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

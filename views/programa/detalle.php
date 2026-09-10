<div class="page-header animate-in" style="margin-bottom:28px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
        <a href="<?= \Core\App::url('/programa') ?>" class="btn btn-secondary btn-sm" style="border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;padding:0;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
                <i class="fas fa-graduation-cap" style="color:var(--accent);"></i>
                <?= htmlspecialchars($programa['nombre_programa']) ?>
            </h1>
            <div style="display:flex;align-items:center;gap:16px;margin-top:4px;flex-wrap:wrap;">
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-hashtag" style="color:var(--accent);"></i>
                    Código: <strong style="color:var(--text-bright);"><?= htmlspecialchars($programa['codigo_programa']) ?></strong>
                </span>
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-desktop"></i> <?= htmlspecialchars($programa['modalidad'] ?? 'N/A') ?>
                </span>
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-users"></i> <?= (int) $programa['total_aprendices'] ?> aprendices total
                </span>
                <?php if ($idProyecto): ?>
                    <a href="<?= \Core\App::url('/proyecto/detalle?id=') . $idProyecto ?>" class="badge badge-success" style="text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-left:10px; cursor:pointer;">
                        <i class="fas fa-check-circle"></i> Proyecto Formativo Activo (Ver)
                    </a>
                <?php else: ?>
                    <span class="badge badge-warning" style="display:inline-flex; align-items:center; gap:5px; margin-left:10px;">
                        <i class="fas fa-exclamation-triangle"></i> Falta Proyecto Formativo
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-row animate-in">
    <div class="summary-card">
        <div class="summary-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1);"><i class="fas fa-id-card"></i></div>
        <div class="summary-data">
            <div class="summary-value"><?= (int) $programa['total_fichas'] ?></div>
            <div class="summary-label">Fichas</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon" style="background:linear-gradient(135deg,var(--accent),#10b981);"><i class="fas fa-user-check"></i></div>
        <div class="summary-data">
            <div class="summary-value"><?= (int) $programa['activos'] ?></div>
            <div class="summary-label">Activos</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon" style="background:linear-gradient(135deg,#ef4444,#f97316);"><i class="fas fa-user-minus"></i></div>
        <div class="summary-data">
            <div class="summary-value"><?= (int) $programa['retirados'] ?></div>
            <div class="summary-label">Retirados</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon" style="background:linear-gradient(135deg,#8b5cf6,#a855f7);"><i class="fas fa-users"></i></div>
        <div class="summary-data">
            <div class="summary-value"><?= (int) $programa['total_aprendices'] ?></div>
            <div class="summary-label">Total Aprendices</div>
        </div>
    </div>
</div>

<!-- Fichas -->
<h2 style="font-size:16px;font-weight:700;color:var(--text-bright);margin:28px 0 16px;display:flex;align-items:center;gap:8px;">
    <i class="fas fa-id-card" style="color:var(--info);"></i> Fichas del Programa
</h2>

<?php if (empty($fichas)): ?>
<div class="card animate-in" style="text-align:center;padding:40px 20px;">
    <h3 style="color:var(--text-bright);margin-bottom:8px;">No hay fichas registradas</h3>
    <p style="color:var(--text-muted);font-size:13px;">Importa un archivo Excel de SOFIA Plus para registrar fichas.</p>
</div>
<?php else: ?>
<div class="fichas-grid">
    <?php foreach ($fichas as $f): ?>
    <div class="ficha-card animate-in" onclick="window.location.href='<?= \Core\App::url('/programa/ficha?id=') . $f['id_ficha'] ?>'">
        <div class="ficha-card-header">
            <div class="ficha-number">
                <i class="fas fa-id-card"></i>
                <span><?= htmlspecialchars($f['nu_ficha']) ?></span>
            </div>
            <?php
                $statusClass = match($f['estado']) {
                    'EN EJECUCION' => 'badge-success',
                    'TERMINADA'    => 'badge-info',
                    'CANCELADA'    => 'badge-danger',
                    default        => 'badge-warning',
                };
            ?>
            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($f['estado']) ?></span>
        </div>

        <div class="ficha-card-dates">
            <span><i class="fas fa-calendar-alt"></i> <?= $f['fecha_inicio'] ?? 'N/A' ?></span>
            <span><i class="fas fa-arrow-right" style="font-size:10px;"></i></span>
            <span><i class="fas fa-calendar-check"></i> <?= $f['fecha_fin'] ?? 'N/A' ?></span>
        </div>

        <div class="ficha-avance-bar">
            <div class="ficha-avance-track">
                <div class="ficha-avance-fill" style="width:<?= $f['avance']['porcentaje'] ?>%"></div>
            </div>
            <span class="ficha-avance-pct"><?= $f['avance']['porcentaje'] ?>%</span>
        </div>

        <div class="ficha-stats-row">
            <div class="fstat"><span class="fstat-val"><?= (int) $f['total_aprendices'] ?></span><span class="fstat-lbl">Total</span></div>
            <div class="fstat"><span class="fstat-val accent"><?= (int) $f['activos'] ?></span><span class="fstat-lbl">Activos</span></div>
            <div class="fstat"><span class="fstat-val danger"><?= (int) $f['retirados'] ?></span><span class="fstat-lbl">Retirados</span></div>
            <div class="fstat"><span class="fstat-val warning"><?= (int) $f['trasladados'] ?></span><span class="fstat-lbl">Traslados</span></div>
        </div>

        <div class="ficha-card-action">
            <span>Ver Aprendices</span>
            <i class="fas fa-arrow-right"></i>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.summary-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 8px;
}
.summary-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.summary-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff;
    flex-shrink: 0;
}
.summary-value { font-size: 24px; font-weight: 800; color: var(--text-bright); }
.summary-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

.fichas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
}

.ficha-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.ficha-card:hover {
    transform: translateY(-4px);
    border-color: var(--accent);
    box-shadow: 0 8px 30px rgba(57, 211, 83, 0.12);
}

.ficha-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 8px;
}

.ficha-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    font-weight: 800;
    color: var(--text-bright);
}
.ficha-number i { color: var(--info); font-size: 16px; }

.ficha-card-dates {
    padding: 4px 24px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: var(--text-muted);
}
.ficha-card-dates i { font-size: 10px; }

.ficha-avance-bar {
    padding: 0 24px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.ficha-avance-track {
    flex: 1;
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 4px;
    overflow: hidden;
}
.ficha-avance-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent), #10b981);
    border-radius: 4px;
    transition: width 0.6s ease;
}
.ficha-avance-pct {
    font-size: 13px;
    font-weight: 700;
    color: var(--accent);
    min-width: 45px;
    text-align: right;
}

.ficha-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
    padding: 12px 24px;
    border-top: 1px solid var(--border);
}
.fstat { text-align: center; }
.fstat-val { font-size: 16px; font-weight: 800; color: var(--text-bright); display: block; }
.fstat-val.accent { color: var(--accent); }
.fstat-val.danger { color: var(--danger); }
.fstat-val.warning { color: var(--warning); }
.fstat-lbl { font-size: 9px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; }

.ficha-card-action {
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
.ficha-card:hover .ficha-card-action {
    background: var(--accent-soft);
}

@media (max-width: 600px) {
    .fichas-grid { grid-template-columns: 1fr; }
    .summary-row { grid-template-columns: repeat(2, 1fr); }
}
</style>

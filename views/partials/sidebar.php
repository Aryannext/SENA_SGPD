<?php
/**
 * Sidebar navigation partial.
 * Highlights the active route based on REQUEST_URI.
 */
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$basePath   = '/SENA_SGPD';

function isActive(string $path, string $currentUri, string $basePath): string {
    $full = $basePath . $path;
    if ($path === '/' || $path === '/dashboard') {
        return ($currentUri === $basePath . '/' || $currentUri === $basePath . '/dashboard') ? 'active' : '';
    }
    return str_starts_with($currentUri, $full) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">S</div>
        <div>
            <div class="brand-text">SGPD</div>
            <div class="brand-sub">SENA Caquetá</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <a href="<?= $basePath ?>/dashboard" class="nav-item <?= isActive('/dashboard', $currentUri, $basePath) ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= $basePath ?>/dashboard/fases" class="nav-item <?= isActive('/dashboard/fases', $currentUri, $basePath) ?>">
                <i class="fas fa-layer-group"></i>
                <span>Fases</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Gestión</div>
            <a href="<?= $basePath ?>/import" class="nav-item <?= isActive('/import', $currentUri, $basePath) ?>">
                <i class="fas fa-file-import"></i>
                <span>Importar Datos</span>
            </a>
            <a href="<?= $basePath ?>/programa" class="nav-item <?= isActive('/programa', $currentUri, $basePath) ?>">
                <i class="fas fa-graduation-cap"></i>
                <span>Programas</span>
            </a>
            <a href="<?= $basePath ?>/aprendiz" class="nav-item <?= isActive('/aprendiz', $currentUri, $basePath) ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Aprendices</span>
            </a>
            <a href="<?= $basePath ?>/proyecto" class="nav-item <?= isActive('/proyecto', $currentUri, $basePath) ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Proyecto Formativo</span>
            </a>
            <a href="<?= $basePath ?>/desercion" class="nav-item <?= isActive('/desercion', $currentUri, $basePath) ?>">
                <i class="fas fa-chart-line"></i>
                <span>Análisis de Deserción</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Inteligencia Artificial</div>
            <a href="<?= $basePath ?>/chat" class="nav-item <?= isActive('/chat', $currentUri, $basePath) ?>">
                <i class="fas fa-robot"></i>
                <span>SENA-IA</span>
            </a>
        </div>
    </nav>

    <?php $sesion = \Core\Auth::usuario(); ?>
    <?php if ($sesion !== null): ?>
        <div style="padding: 14px 22px; border-top: 1px solid var(--border); display:flex; align-items:center; gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:#07130b;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex:0 0 auto;">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($sesion['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:12px;font-weight:600;color:var(--text-bright);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= htmlspecialchars($sesion['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div style="font-size:10px;color:var(--text-muted);letter-spacing:.05em;">
                    <?= htmlspecialchars($sesion['rol'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <a href="<?= $basePath ?>/logout" title="Cerrar sesión" style="color:var(--text-muted);padding:6px;">
                <i class="fas fa-right-from-bracket"></i>
            </a>
        </div>
    <?php endif; ?>

    <div style="padding: 12px 22px 16px; border-top: 1px solid var(--border); font-size: 10px; color: var(--text-muted);">
        SGPD v2.0 — ADSO 2480542
    </div>
</aside>

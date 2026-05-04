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
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Inteligencia Artificial</div>
            <a href="<?= $basePath ?>/chat" class="nav-item <?= isActive('/chat', $currentUri, $basePath) ?>">
                <i class="fas fa-robot"></i>
                <span>SENA-IA</span>
            </a>
        </div>
    </nav>

    <div style="padding: 16px 22px; border-top: 1px solid var(--border); font-size: 10px; color: var(--text-muted);">
        SGPD v2.0 — ADSO 2480542
    </div>
</aside>

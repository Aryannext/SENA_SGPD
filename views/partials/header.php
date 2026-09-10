<header class="top-header">
    <div class="header-left">
        <button class="btn btn-sm btn-secondary" id="sidebar-toggle" style="display:none;" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <i class="fas fa-home"></i> / <span><?= htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:12px;color:var(--text-muted)">
            <i class="fas fa-circle" style="color:var(--accent);font-size:8px;"></i>
            Sistema Activo
        </span>
    </div>
</header>

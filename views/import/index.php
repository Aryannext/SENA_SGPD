<div class="page-header animate-in" style="margin-bottom:28px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
        <i class="fas fa-file-import" style="color:var(--accent);"></i> Importar Datos
    </h1>
    <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Cargue masivo del Reporte de Juicios Evaluativos de SOFIA Plus</p>
</div>

<div class="card animate-in">
    <div class="upload-zone" id="upload-zone">
        <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
        <div class="upload-title">Arrastra tu archivo Excel aquí</div>
        <div class="upload-subtitle">o haz clic para seleccionar — .xls / .xlsx</div>
        <input type="file" id="file-input" accept=".xls,.xlsx" style="display:none;">
    </div>

    <!-- Progress -->
    <div id="import-progress" style="display:none; margin-top:24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
            <i class="fas fa-spinner fa-spin" style="color:var(--accent);"></i>
            <span style="font-size:14px;font-weight:600;color:var(--text-bright);" id="progress-text">Procesando archivo...</span>
        </div>
        <div class="progress" style="height:10px;">
            <div class="progress-bar" id="progress-bar" style="width:0%;"></div>
        </div>
    </div>

    <!-- Results -->
    <div id="import-results" style="display:none; margin-top:28px;">
        <h3 style="font-size:16px;font-weight:700;color:var(--accent);margin-bottom:16px;">
            <i class="fas fa-check-circle"></i> Importación Completada
        </h3>
        <div class="stats-grid" id="import-stats"></div>
        <div id="import-errors" style="margin-top:16px;"></div>
    </div>
</div>

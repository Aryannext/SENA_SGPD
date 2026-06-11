<div class="page-header animate-in" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
            <i class="fas fa-chart-pie" style="color:var(--accent);"></i> Dashboard General
        </h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Selecciona una ficha para ver sus métricas detalladas</p>
    </div>
    
    <div style="background:rgba(0,0,0,0.2); padding:12px 16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:12px;">
        <label for="global-ficha-selector" style="font-size:13px; font-weight:600; color:var(--text-muted); margin:0;">Contexto Ficha Activa:</label>
        <select id="global-ficha-selector" class="form-control" style="min-width:300px; font-size:14px; font-weight:600; background-color:#0f172a;" onchange="Dashboard.changeFicha()">
            <option value="">Cargando fichas...</option>
        </select>
    </div>
</div>

<!-- Tarjeta de Identidad de la Ficha -->
<div id="ficha-info-card" class="card animate-in" style="margin-bottom:24px; display:none; background: linear-gradient(135deg, rgba(30,41,59,0.8), rgba(15,23,42,0.8)); border-left: 4px solid var(--accent);">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px;">
        <div>
            <h2 id="ficha-info-programa" style="font-size:18px; font-weight:700; color:var(--text-bright); margin:0 0 4px 0;">Programa</h2>
            <div style="font-size:13px; color:var(--text-muted); display:flex; gap:16px; align-items:center;">
                <span><i class="fas fa-hashtag" style="color:var(--accent); margin-right:4px;"></i> Ficha: <strong id="ficha-info-numero" style="color:var(--text-bright);">0000000</strong></span>
                <span><i class="fas fa-desktop" style="color:var(--info); margin-right:4px;"></i> Modalidad: <strong id="ficha-info-modalidad" style="color:var(--text-bright);">PRESENCIAL</strong></span>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:13px; color:var(--text-muted);"><i class="far fa-clock" style="margin-right:4px;"></i> Tiempo Transcurrido: <strong id="ficha-info-tiempo" style="color:var(--accent);">0%</strong></div>
            <div style="font-size:13px; font-weight:600; color:var(--text-bright); margin-top:4px;">
                <span id="ficha-info-inicio">Inicio</span> <i class="fas fa-arrow-right" style="color:var(--text-muted); font-size:10px; margin:0 6px;"></i> <span id="ficha-info-fin">Fin</span>
            </div>
            <div style="width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-top: 6px; overflow: hidden;">
                <div id="ficha-info-tiempo-bar" style="height: 100%; width: 0%; background: var(--accent);"></div>
            </div>
        </div>
    </div>
</div>


<!-- Stat Cards -->
<div class="stats-grid" id="stats-grid">
    <div class="stat-card animate-in">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-value" id="stat-total" data-counter="0">0</div>
        <div class="stat-label">Total Aprendices</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon purple"><i class="fas fa-percentage"></i></div>
        <div class="stat-value" id="stat-porcentaje" data-counter="0" data-suffix="%">0%</div>
        <div class="stat-label">Avance Global</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-value" id="stat-riesgo" data-counter="0">0</div>
        <div class="stat-label">En Riesgo (Crítico)</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon green"><i class="fas fa-star"></i></div>
        <div class="stat-value" id="stat-destacados" data-counter="0">0</div>
        <div class="stat-label">Destacados (Adelantados)</div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2 animate-in" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-bar" style="color:var(--info);"></i> Distribución del Rendimiento</span>
        </div>
        <div style="position:relative;height:350px;">
            <canvas id="chart-histograma"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-pie" style="color:var(--accent);"></i> Cumplimiento por Fases</span>
        </div>
        <div style="position:relative;height:350px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chart-fases"></canvas>
        </div>
    </div>
</div>

<!-- Critical Competencies & At Risk -->
<div class="grid-2 animate-in" style="margin-bottom:24px; grid-template-columns: 2fr 1fr;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-exclamation-circle" style="color:var(--danger);"></i> Top 5: Competencias Críticas (Menor Aprobación)</span>
        </div>
        <div style="position:relative;height:350px;">
            <canvas id="chart-competencias-criticas"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-user-times" style="color:var(--warning);"></i> Foco de Atención</span>
        </div>
        <div class="table-wrapper" style="max-height: 350px; overflow-y: auto;">
            <table class="table-sm">
                <thead>
                    <tr>
                        <th>Aprendiz</th>
                        <th style="text-align:right;">Avance</th>
                    </tr>
                </thead>
                <tbody id="table-riesgo-body">
                    <tr><td colspan="2" class="empty-state">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Aprendices Table -->
<div class="card animate-in">
    <!-- Filter Bar -->
<div class="filter-bar animate-in" id="filter-bar">
    <div class="form-group">
        <label class="form-label">Estado</label>
        <select class="form-control" id="filter-estado">
            <option value="">Todos</option>
        </select>
    </div>
    
    <div class="form-group">
        <label class="form-label">Documento</label>
        <input type="text" class="form-control" id="filter-documento" placeholder="Buscar por documento...">
    </div>
    <div class="form-group">
        <label class="form-label">Buscar</label>
        <input type="text" class="form-control" id="filter-busqueda" placeholder="Nombre o apellido...">
    </div>
    <div class="form-group" style="flex:0;">
        <label class="form-label">&nbsp;</label>
        <button class="btn btn-primary" onclick="Dashboard.applyFilters()">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </div>
</div>
<div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <span class="card-title"><i class="fas fa-table" style="color:var(--purple);"></i> Directorio General de Aprendices</span>
            <span class="badge badge-info" id="table-count">0 registros</span>
        </div>
        
        <!-- Botones Rápidos Removidos -->
    </div>
    <div class="table-wrapper">
        <table id="table-aprendices">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Aprobados</th>
                    <th>Pendientes</th>
                    <th>Avance</th>
                    <th>Progreso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="8" class="empty-state"><i class="fas fa-database"></i><h3>Cargando datos...</h3></td></tr>
            </tbody>
        </table>
    </div>
</div>





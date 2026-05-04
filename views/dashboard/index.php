<div class="page-header animate-in" style="margin-bottom:28px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
        <i class="fas fa-chart-pie" style="color:var(--accent);"></i> Dashboard General
    </h1>
    <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Resumen de progreso académico de la ficha</p>
</div>

<!-- Stat Cards -->
<div class="stats-grid" id="stats-grid">
    <div class="stat-card animate-in">
        <div class="stat-icon green"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-value" id="stat-total" data-counter="0">0</div>
        <div class="stat-label">Total Aprendices</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon blue"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value" id="stat-aprobados" data-counter="0">0</div>
        <div class="stat-label">Juicios Aprobados</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="stat-value" id="stat-pendientes" data-counter="0">0</div>
        <div class="stat-label">Juicios Pendientes</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon purple"><i class="fas fa-percentage"></i></div>
        <div class="stat-value" id="stat-porcentaje" data-counter="0" data-suffix="%">0%</div>
        <div class="stat-label">Avance Global</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar animate-in" id="filter-bar">
    <div class="form-group">
        <label class="form-label">Estado</label>
        <select class="form-control" id="filter-estado">
            <option value="">Todos</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Competencia</label>
        <select class="form-control" id="filter-competencia">
            <option value="">Todas</option>
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

<!-- Charts Row -->
<div class="grid-2 animate-in" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-bar" style="color:var(--accent);"></i> Avance por Aprendiz</span>
        </div>
        <div style="position:relative;height:350px;">
            <canvas id="chart-avance-aprendiz"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-doughnut" style="color:var(--info);"></i> Distribución de Juicios</span>
        </div>
        <div style="position:relative;height:350px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chart-juicios"></canvas>
        </div>
    </div>
</div>

<!-- Aprendices Table -->
<div class="card animate-in">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-table" style="color:var(--warning);"></i> Seguimiento por Aprendiz</span>
        <span class="badge badge-info" id="table-count">0 registros</span>
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

<!-- Competencia Chart -->
<div class="card animate-in" style="margin-top:24px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-graduation-cap" style="color:var(--purple);"></i> Aprobación por Competencia</span>
    </div>
    <div style="position:relative;height:400px;">
        <canvas id="chart-competencias"></canvas>
    </div>
</div>

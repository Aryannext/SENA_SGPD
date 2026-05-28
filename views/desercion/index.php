<div class="page-header animate-in" style="margin-bottom:28px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
        <i class="fas fa-chart-line" style="color:var(--accent);"></i> Análisis de Deserción y Retiros
    </h1>
    <p style="color:var(--text-muted);font-size:13px;margin-top:4px;">Monitoreo de aprendices retirados, motivos y auditoría de instructores.</p>
</div>

<!-- AI Prediction Card -->
<div class="card animate-in" style="margin-bottom: 24px; border-left: 4px solid var(--accent); background: linear-gradient(145deg, var(--bg-card), rgba(0, 240, 255, 0.05));">
    <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
        <span class="card-title" style="color:var(--accent); font-weight: 700;">
            <i class="fas fa-robot"></i> Predicción SENA-IA (Riesgo de Deserción)
        </span>
    </div>
    <div style="padding: 16px 20px;">
        <p id="ai-prediction" style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
            <i class="fas fa-spinner fa-spin"></i> Analizando patrones históricos para detectar aprendices en riesgo...
        </p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2 animate-in" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-pie" style="color:var(--warning);"></i> Motivos de Retiro</span>
        </div>
        <div style="position:relative;height:350px;display:flex;align-items:center;justify-content:center;">
            <canvas id="chart-motivos"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chalkboard-teacher" style="color:var(--danger);"></i> Retiros por Instructor (Última Fase)</span>
        </div>
        <div style="position:relative;height:350px;">
            <canvas id="chart-instructores"></canvas>
        </div>
    </div>
</div>

<!-- Audit Table -->
<div class="card animate-in">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list-alt" style="color:var(--info);"></i> Trazabilidad de Retiros (Auditoría)</span>
    </div>
    <div class="table-wrapper">
        <table id="table-auditoria">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Documento</th>
                    <th>Aprendiz</th>
                    <th>Fase de Abandono</th>
                    <th>Motivo Principal</th>
                    <th>Instructor a Cargo</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="6" class="empty-state"><i class="fas fa-spinner fa-spin"></i><h3>Cargando datos...</h3></td></tr>
            </tbody>
        </table>
    </div>
</div>

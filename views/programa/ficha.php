<div class="page-header animate-in" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="/SENA_SGPD/programa/detalle?id=<?= $programa['id_programa'] ?? 0 ?>" class="btn btn-secondary btn-sm" style="border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;padding:0;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
                <i class="fas fa-id-card" style="color:var(--info);"></i>
                Ficha <?= htmlspecialchars($ficha['nu_ficha']) ?>
            </h1>
            <div style="display:flex;align-items:center;gap:16px;margin-top:4px;flex-wrap:wrap;">
                <span style="font-size:12px;color:var(--text-muted);">
                    <i class="fas fa-graduation-cap" style="color:var(--accent);"></i>
                    <?= htmlspecialchars($programa['nombre_programa'] ?? 'Sin programa') ?>
                </span>
                <?php
                    $statusClass = match($ficha['estado'] ?? '') {
                        'EN EJECUCION' => 'badge-success',
                        'TERMINADA'    => 'badge-info',
                        default        => 'badge-warning',
                    };
                ?>
                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($ficha['estado'] ?? '') ?></span>
                <span style="font-size:11px;color:var(--text-muted);">
                    <i class="fas fa-calendar"></i> <?= $ficha['fecha_inicio'] ?? '' ?> → <?= $ficha['fecha_fin'] ?? '' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards (loaded via AJAX) -->
<div class="ficha-summary-row animate-in" id="stats-cards">
    <div class="fsum-card">
        <div class="fsum-icon"><i class="fas fa-users"></i></div>
        <div class="fsum-val" id="stat-total">—</div>
        <div class="fsum-lbl">Total</div>
    </div>
    <div class="fsum-card">
        <div class="fsum-icon accent"><i class="fas fa-user-check"></i></div>
        <div class="fsum-val accent" id="stat-activos">—</div>
        <div class="fsum-lbl">Activos</div>
    </div>
    <div class="fsum-card">
        <div class="fsum-icon danger"><i class="fas fa-user-minus"></i></div>
        <div class="fsum-val danger" id="stat-retirados">—</div>
        <div class="fsum-lbl">Retirados</div>
    </div>
    <div class="fsum-card">
        <div class="fsum-icon warning"><i class="fas fa-exchange-alt"></i></div>
        <div class="fsum-val warning" id="stat-trasladados">—</div>
        <div class="fsum-lbl">Trasladados</div>
    </div>
    <div class="fsum-card wide">
        <div class="fsum-icon info"><i class="fas fa-chart-line"></i></div>
        <div class="fsum-val info" id="stat-avance">—%</div>
        <div class="fsum-lbl">Avance Global</div>
        <div class="mini-bar" style="margin-top:6px;">
            <div class="mini-bar-fill" id="avance-bar" style="width:0%"></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card animate-in filter-bar" style="margin-bottom:16px;">
    <div class="filters-flex">
        <div class="filter-group">
            <label><i class="fas fa-search"></i></label>
            <input type="text" id="filter-buscar" class="form-control form-control-sm" placeholder="Buscar nombre, apellido o documento..." style="min-width:260px;">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-filter"></i></label>
            <select id="filter-estado" class="form-control form-control-sm">
                <option value="">Todos los estados</option>
                <option value="EN FORMACION">En Formación</option>
                <option value="RETIRO VOLUNTARIO">Retiro Voluntario</option>
                <option value="TRASLADADO">Trasladado</option>
                <option value="CANCELADO">Cancelado</option>
                <option value="CERTIFICADO">Certificado</option>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-sort"></i></label>
            <select id="filter-ordenar" class="form-control form-control-sm">
                <option value="apellido">Apellido</option>
                <option value="nombre">Nombre</option>
                <option value="porcentaje_avance">Avance</option>
                <option value="estado">Estado</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="filter-dir" class="form-control form-control-sm">
                <option value="ASC">↑ Ascendente</option>
                <option value="DESC">↓ Descendente</option>
            </select>
        </div>
        <button class="btn btn-primary btn-sm" onclick="loadAprendices()">
            <i class="fas fa-sync-alt"></i> Filtrar
        </button>
    </div>
</div>

<!-- Table -->
<div class="card animate-in">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-user-graduate" style="color:var(--accent);"></i> Aprendices</span>
        <span class="badge badge-info" id="count-badge">—</span>
    </div>
    <div class="table-wrapper">
        <table id="aprendices-table">
            <thead>
                <tr>
                    <th>Tipo Doc</th>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Estado</th>
                    <th>Avance</th>
                    <th>Aprobados</th>
                    <th>Pendientes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="aprendices-body">
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.ficha-summary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr) 1.5fr;
    gap: 12px;
    margin-bottom: 16px;
}
.fsum-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    text-align: center;
}
.fsum-icon {
    font-size: 18px;
    color: var(--text-muted);
    margin-bottom: 6px;
}
.fsum-icon.accent { color: var(--accent); }
.fsum-icon.danger { color: var(--danger); }
.fsum-icon.warning { color: var(--warning); }
.fsum-icon.info { color: var(--info); }
.fsum-val {
    font-size: 26px;
    font-weight: 800;
    color: var(--text-bright);
}
.fsum-val.accent { color: var(--accent); }
.fsum-val.danger { color: var(--danger); }
.fsum-val.warning { color: var(--warning); }
.fsum-val.info { color: var(--info); }
.fsum-lbl {
    font-size: 10px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mini-bar {
    height: 6px;
    background: var(--bg-secondary);
    border-radius: 3px;
    overflow: hidden;
}
.mini-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--info), #6366f1);
    border-radius: 3px;
    transition: width 0.8s ease;
}

.filter-bar { padding: 14px 20px; }
.filters-flex {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    align-items: center;
    gap: 6px;
}
.filter-group label {
    color: var(--text-muted);
    font-size: 13px;
}

/* Progress bar in table */
.avance-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}
.avance-bar-mini {
    width: 60px;
    height: 6px;
    background: var(--bg-secondary);
    border-radius: 3px;
    overflow: hidden;
}
.avance-bar-mini-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.4s;
}

@media (max-width: 800px) {
    .ficha-summary-row { grid-template-columns: repeat(2, 1fr); }
    .fsum-card.wide { grid-column: span 2; }
    .filters-flex { flex-direction: column; align-items: stretch; }
}
</style>

<script>
const FICHA_ID = <?= $ficha['id_ficha'] ?>;
let debounceTimer;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filter-buscar').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadAprendices, 300);
    });
    document.getElementById('filter-estado').addEventListener('change', loadAprendices);
    document.getElementById('filter-ordenar').addEventListener('change', loadAprendices);
    document.getElementById('filter-dir').addEventListener('change', loadAprendices);

    // Load on page ready
    loadAprendices();
});

async function loadAprendices() {
    const buscar  = document.getElementById('filter-buscar').value;
    const estado  = document.getElementById('filter-estado').value;
    const ordenar = document.getElementById('filter-ordenar').value;
    const dir     = document.getElementById('filter-dir').value;

    const params = new URLSearchParams({
        id_ficha: FICHA_ID,
        buscar, estado, ordenar, dir,
    });

    try {
        const basePath = (typeof APP !== 'undefined') ? APP.basePath : '/SENA_SGPD';
        const res = await fetch(basePath + '/api/programa/aprendices?' + params.toString());
        const data = await res.json();

        if (!data.success) {
            if (typeof APP !== 'undefined') APP.toast(data.message || 'Error', 'error');
            return;
        }

        // Update stats
        document.getElementById('stat-total').textContent = data.stats.total ?? 0;
        document.getElementById('stat-activos').textContent = data.stats.activos ?? 0;
        document.getElementById('stat-retirados').textContent = data.stats.retirados ?? 0;
        document.getElementById('stat-trasladados').textContent = data.stats.trasladados ?? 0;
        document.getElementById('stat-avance').textContent = data.avance.porcentaje + '%';
        document.getElementById('avance-bar').style.width = data.avance.porcentaje + '%';

        // Update count badge
        document.getElementById('count-badge').textContent = data.aprendices.length + ' resultados';

        // Render table
        const tbody = document.getElementById('aprendices-body');
        if (data.aprendices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-user-slash"></i> No se encontraron aprendices</td></tr>';
            return;
        }

        tbody.innerHTML = data.aprendices.map(a => {
            const pct = parseFloat(a.porcentaje_avance) || 0;
            const badgeClass = a.estado.includes('FORMACION') ? 'badge-success' :
                               a.estado.includes('RETIRO') ? 'badge-danger' :
                               a.estado.includes('TRASLADADO') ? 'badge-warning' : 'badge-info';
            const barColor = pct >= 70 ? 'var(--accent)' : pct >= 40 ? 'var(--warning)' : 'var(--danger)';

            return `<tr>
                <td>${APP.esc(a.ti_documento)}</td>
                <td><code>${APP.esc(a.nu_documento)}</code></td>
                <td><strong>${APP.esc(a.nombre)} ${APP.esc(a.apellido)}</strong></td>
                <td><span class="badge ${badgeClass}">${APP.esc(a.estado)}</span></td>
                <td>
                    <div class="avance-cell">
                        <div class="avance-bar-mini">
                            <div class="avance-bar-mini-fill" style="width:${pct}%;background:${barColor}"></div>
                        </div>
                        <span style="font-weight:700;font-size:12px;color:${barColor}">${pct}%</span>
                    </div>
                </td>
                <td style="font-weight:600;color:var(--accent);">${a.aprobados ?? 0}</td>
                <td style="font-weight:600;color:var(--warning);">${a.por_evaluar ?? 0}</td>
                <td><a href="/SENA_SGPD/aprendiz/${a.id_aprendiz}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
            </tr>`;
        }).join('');

    } catch (e) {
        console.error('loadAprendices error:', e);
        const tbody = document.getElementById('aprendices-body');
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Error: ' + e.message + '</td></tr>';
    }
}
</script>

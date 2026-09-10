<div class="page-header animate-in" style="margin-bottom:28px;">
    <h1 style="font-size:22px;font-weight:700;color:var(--text-bright);">
        <i class="fas fa-user-graduate" style="color:var(--accent);"></i> Aprendices
    </h1>
</div>

<div class="card animate-in">
    <div class="card-header">
        <span class="card-title">Lista de Aprendices</span>
        <span class="badge badge-info"><?= count($aprendices) ?> registros</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tipo Doc</th>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Estado</th>
                    <th>Ficha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aprendices)): ?>
                    <tr><td colspan="7" class="empty-state"><i class="fas fa-user-slash"></i><h3>Sin aprendices</h3><p>Importa un archivo Excel primero</p></td></tr>
                <?php else: ?>
                    <?php foreach ($aprendices as $a): ?>
                        <?php
                            $badgeClass = match(true) {
                                str_contains($a['estado'], 'FORMACION') => 'badge-success',
                                str_contains($a['estado'], 'RETIRO')    => 'badge-danger',
                                default => 'badge-warning',
                            };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($a['ti_documento']) ?></td>
                            <td><code><?= htmlspecialchars($a['nu_documento']) ?></code></td>
                            <td><?= htmlspecialchars($a['nombre']) ?></td>
                            <td><?= htmlspecialchars($a['apellido']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($a['estado']) ?></span></td>
                            <td><?= htmlspecialchars($a['nu_ficha'] ?? '-') ?></td>
                            <td><a href="<?= \Core\App::url('/aprendiz/') . $a['id_aprendiz'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i> Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

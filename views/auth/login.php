<style>
    .login-wrap {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .login-card {
        width: 100%;
        max-width: 400px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 36px 32px;
        box-shadow: 0 24px 60px -30px rgba(0, 0, 0, .8);
    }
    .login-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 28px;
    }
    .login-brand .brand-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--accent);
        color: #07130b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 20px;
    }
    .login-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--text-bright);
        margin: 0;
    }
    .login-sub {
        font-size: 11px;
        color: var(--text-muted);
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .login-card .form-group { margin-bottom: 16px; }
    .login-card .form-label { display: block; margin-bottom: 6px; }
    .login-error {
        background: rgba(239, 68, 68, .12);
        border: 1px solid rgba(239, 68, 68, .35);
        color: #fca5a5;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 12.5px;
        margin-bottom: 18px;
    }
    .login-aviso {
        background: rgba(245, 158, 11, .1);
        border: 1px solid rgba(245, 158, 11, .3);
        color: #fcd34d;
        border-radius: 6px;
        padding: 12px;
        font-size: 12px;
        line-height: 1.55;
        margin-bottom: 18px;
    }
    .login-aviso code {
        display: block;
        margin-top: 6px;
        color: var(--text-bright);
        font-size: 11.5px;
        word-break: break-all;
    }
    .login-pie {
        margin-top: 22px;
        text-align: center;
        font-size: 10.5px;
        color: var(--text-muted);
        line-height: 1.6;
    }
</style>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">S</div>
            <div>
                <p class="login-title">SGPD SENA</p>
                <span class="login-sub">Regional Caquetá</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="login-error">
                <i class="fas fa-circle-exclamation"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sinUsuarios)): ?>
            <div class="login-aviso">
                <i class="fas fa-triangle-exclamation"></i>
                Todavía no hay ningún usuario. Crea el primero desde la terminal:
                <code>php tools/crear-usuario.php admin ADMIN</code>
            </div>
        <?php endif; ?>

        <form method="POST" action="/SENA_SGPD/login" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Core\Auth::tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label class="form-label" for="usuario">Usuario</label>
                <input
                    class="form-control" type="text" id="usuario" name="usuario"
                    value="<?= htmlspecialchars((string) ($usuario ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="username" autofocus required>
            </div>

            <div class="form-group">
                <label class="form-label" for="contrasena">Contraseña</label>
                <input
                    class="form-control" type="password" id="contrasena" name="contrasena"
                    autocomplete="current-password" required>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:6px;">
                <i class="fas fa-right-to-bracket"></i> Entrar
            </button>
        </form>

        <p class="login-pie">
            Este sistema contiene datos personales de aprendices.<br>
            Su uso está restringido al personal autorizado.
        </p>
    </div>
</div>

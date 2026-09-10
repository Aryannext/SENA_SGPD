#!/bin/sh
# ─────────────────────────────────────────────────────────────────────────────
# Arranque del contenedor.
#
#   1. Espera a que la base de datos acepte conexiones. Al desplegar en Dokploy
#      el servicio de MySQL puede tardar unos segundos más que la aplicación, y
#      sin esta espera el primer arranque falla.
#   2. Aplica el esquema y las migraciones pendientes. Es idempotente.
#   3. Cede el control a Apache.
# ─────────────────────────────────────────────────────────────────────────────
set -e

espera_maxima="${APP_DB_WAIT_SECONDS:-60}"
intento=0

echo "[sgpd] Esperando a la base de datos en ${DB_HOST:-127.0.0.1}:${DB_PORT:-3306}…"

while [ "$intento" -lt "$espera_maxima" ]; do
    if php -r '
        $h = getenv("DB_HOST") ?: "127.0.0.1";
        $p = getenv("DB_PORT") ?: "3306";
        $c = @fsockopen($h, (int) $p, $e, $s, 2);
        exit($c === false ? 1 : 0);
    ' 2>/dev/null; then
        echo "[sgpd] Base de datos disponible."
        break
    fi

    intento=$((intento + 1))
    sleep 1
done

if [ "$intento" -ge "$espera_maxima" ]; then
    echo "[sgpd] La base de datos no respondió en ${espera_maxima}s. Se arranca igualmente:"
    echo "[sgpd] la aplicación mostrará el error de conexión en lugar de quedarse colgada."
fi

# Las migraciones se pueden desactivar si prefieres aplicarlas a mano.
if [ "${APP_AUTO_MIGRATE:-true}" = "true" ]; then
    echo "[sgpd] Preparando la base de datos…"
    php /var/www/html/tools/migrar.php || {
        echo "[sgpd] Las migraciones fallaron. Revisa las credenciales de DB_*."
        echo "[sgpd] Se arranca igualmente para que puedas entrar a diagnosticar."
    }
fi

# El almacenamiento suele ser un volumen montado: los permisos hay que
# asegurarlos en cada arranque, no solo al construir la imagen.
mkdir -p "${APP_STORAGE_PATH:-/var/www/html/storage}/uploads" \
         "${APP_STORAGE_PATH:-/var/www/html/storage}/audio"
chown -R www-data:www-data "${APP_STORAGE_PATH:-/var/www/html/storage}" 2>/dev/null || true

echo "[sgpd] Listo."
exec "$@"

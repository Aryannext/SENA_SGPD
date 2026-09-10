# ─────────────────────────────────────────────────────────────────────────────
# SGPD SENA — imagen de producción
#
# Se usa php:8.2-apache porque el proyecto depende de .htaccess y mod_rewrite.
# La misma imagen sirve en cualquier entorno: toda la configuración llega por
# variables de entorno (ver .env.example).
#
# Construcción en dos etapas para que las dependencias de Composer no arrastren
# el propio Composer a la imagen final.
# ─────────────────────────────────────────────────────────────────────────────

# ── Etapa 1: dependencias PHP ────────────────────────────────────────────────
FROM composer:2 AS dependencias

WORKDIR /app
COPY composer.json composer.lock ./

# --no-dev: PHPUnit y compañía no tienen nada que hacer en producción.
# --ignore-platform-reqs: las extensiones se instalan en la etapa siguiente.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --ignore-platform-reqs

# ── Etapa 2: aplicación ──────────────────────────────────────────────────────
FROM php:8.2-apache

# Extensiones que exigen PhpSpreadsheet (lectura de Excel) y PDO MySQL.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        zip \
        gd \
        intl \
        bcmath \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# El proyecto enruta todo por index.php mediante .htaccess.
RUN a2enmod rewrite headers

# Ajustes de PHP para producción: sin mostrar errores —los mensajes de
# excepción filtran rutas y estructura de la base— y con margen para importar
# reportes de varios miles de filas.
RUN { \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'expose_php = Off'; \
        echo 'upload_max_filesize = 24M'; \
        echo 'post_max_size = 25M'; \
        echo 'memory_limit = 512M'; \
        echo 'max_execution_time = 300'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'session.cookie_samesite = Strict'; \
        echo 'session.use_strict_mode = 1'; \
    } > /usr/local/etc/php/conf.d/sgpd.ini

# Permitir que el .htaccess del proyecto tenga efecto.
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY --from=dependencias /app/vendor ./vendor
COPY . .

# El almacenamiento vive fuera de la raíz pública y es el único directorio que
# la aplicación escribe. En Dokploy debe montarse como volumen: el contenedor
# es efímero y sin volumen se perderían los archivos importados en cada
# despliegue.
RUN mkdir -p storage/uploads storage/audio \
    && chown -R www-data:www-data storage \
    && chmod -R 755 storage

# Nada de esto tiene sentido dentro de la imagen de producción.
RUN rm -rf tests tts_server docs/migraciones/.gitkeep .git

# El repositorio se edita desde Windows: hay que quitar los retornos de carro o
# el interprete de shell del contenedor no reconoce la primera linea.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1/salud") !== false ? 0 : 1);'

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]

# 10 — Guía de Instalación y Despliegue

## 10.1 Requisitos del Sistema

### Software Necesario
| Componente | Versión Mínima | Recomendado |
|-----------|---------------|-------------|
| PHP | 8.1 | 8.2+ |
| MySQL / MariaDB | 5.7 | 8.x / 10.x |
| Apache | 2.4 | 2.4+ con mod_rewrite |
| Composer | 2.x | 2.x (solo para instalar dependencias) |
| Ollama (opcional) | Latest | Para funcionalidad SENA-IA |

### Extensiones PHP Requeridas
- `pdo_mysql` — Conexión a MySQL
- `mbstring` — Manejo de cadenas multibyte
- `zip` — Requerido por PhpSpreadsheet
- `gd` o `imagick` — Procesamiento de imágenes (PhpSpreadsheet)
- `xml` — Parseo de archivos Excel
- `curl` — Comunicación con Ollama API

---

## 10.2 Instalación Local (WampServer)

### Paso 1: Clonar el Repositorio
```bash
cd C:\wamp64\www
git clone https://github.com/Aryannext/SENA_SGPD.git
```

### Paso 2: Instalar Dependencias PHP
```bash
cd SENA_SGPD
composer install
```

### Paso 3: Crear la Base de Datos
1. Abre **phpMyAdmin** en `http://localhost/phpmyadmin`
2. Importa el archivo `docs/SGPD_SENA.sql`
3. Esto creará la base de datos `sistema_sena` con todas las tablas

### Paso 4: Configurar Conexión
Edita el archivo `config/database.php`:
```php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'sistema_sena',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];
```

### Paso 5: Verificar Apache mod_rewrite
Asegúrate de que el módulo `rewrite_module` esté activado en WampServer:
- Clic izquierdo en el ícono de WampServer → Apache → Apache modules → `rewrite_module` ✓

### Paso 6: Acceder al Sistema
Abre tu navegador en:
```
http://localhost/SENA_SGPD
```

---

## 10.3 Instalación de Ollama (SENA-IA) — Opcional

### Paso 1: Descargar Ollama
Descarga desde [https://ollama.com/download](https://ollama.com/download) e instala.

### Paso 2: Descargar el Modelo
```bash
ollama pull deepseek-r1:8b
```

### Paso 3: Verificar
El servicio se ejecuta automáticamente en `http://127.0.0.1:11434`.
Verifica con:
```bash
curl http://127.0.0.1:11434/api/tags
```

---

## 10.4 Despliegue en Hosting (InfinityFree / Hosting Compartido)

### Diferencias con la instalación local

| Aspecto | Local (WampServer) | Producción (Hosting) |
|---------|-------------------|---------------------|
| Base de datos | `root` sin contraseña | Usuario y contraseña del hosting |
| Base path | `/SENA_SGPD` | `` (raíz del dominio) |
| Carpeta web | `C:\wamp64\www\SENA_SGPD` | `/htdocs/` |
| SENA-IA | Disponible (Ollama local) | No disponible (hosting no soporta Ollama) |

### Paso 1: Crear la Base de Datos en el Hosting
1. Accede al Panel de Control del hosting
2. Busca la sección "MySQL Databases"
3. Crea una nueva base de datos
4. Abre phpMyAdmin del hosting e importa `docs/SGPD_SENA.sql`

### Paso 2: Modificar Archivos para Producción
Los siguientes archivos contienen la ruta base `/SENA_SGPD` que debe eliminarse:

| Archivo | Cambio requerido |
|---------|-----------------|
| `config/database.php` | Cambiar credenciales del hosting |
| `core/Router.php` (línea 55) | `$basePath = '';` |
| `core/Controller.php` (línea 65) | `header('Location: ' . $uri);` |
| `public/js/app.js` (línea 7) | `basePath: '',` |
| `public/js/desercion.js` | Reemplazar `/SENA_SGPD/api/` por `/api/` |
| `views/layouts/main.php` | Reemplazar `/SENA_SGPD/public/` por `/public/` |
| `views/partials/sidebar.php` | Cambiar `$basePath = '';` |

### Paso 3: Subir Archivos
Sube todo el contenido del proyecto a la carpeta `htdocs/` del hosting:
```
htdocs/
├── .htaccess
├── index.php
├── app/
├── config/
├── core/
├── public/
├── vendor/
└── views/
```

### Paso 4: Verificar
Accede a tu dominio y verifica que el Dashboard se cargue correctamente.

---

## 10.5 Estructura de Archivos de Configuración

### `config/database.php`
```php
return [
    'host'     => '127.0.0.1',        // Host de la BD
    'port'     => 3306,                // Puerto MySQL
    'dbname'   => 'sistema_sena',      // Nombre de la BD
    'username' => 'root',              // Usuario
    'password' => '',                  // Contraseña
    'charset'  => 'utf8mb4',           // Charset
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
```

### `config/ollama.php`
```php
return [
    'base_url'    => 'http://127.0.0.1:11434',  // URL del servidor Ollama
    'model'       => 'deepseek-r1:8b',           // Modelo LLM
    'temperature' => 0.3,                         // Creatividad (0-1)
    'num_ctx'     => 4096,                        // Ventana de contexto
    'system_prompt' => '...',                      // Personalidad SENA-IA
];
```

---

## 10.6 Troubleshooting (Solución de Problemas Comunes)

| Problema | Causa | Solución |
|----------|-------|---------|
| Página en blanco | mod_rewrite desactivado | Activar `rewrite_module` en Apache |
| Error 500 | PHP < 8.1 o falta extensión | Verificar `php -v` y extensiones |
| "Class not found" | Falta `vendor/` | Ejecutar `composer install` |
| Gráficos no cargan | Error JSON en API | Revisar consola del navegador (F12) |
| Estados vacíos al importar | ENUM restrictivo en BD | Ejecutar `ALTER TABLE aprendiz MODIFY estado VARCHAR(50)` |
| SENA-IA no responde | Ollama no está corriendo | Iniciar Ollama y verificar que el modelo esté descargado |
| Archivos Excel no se importan | Formato no compatible | Usar el reporte oficial de "Juicios Evaluativos" de Sofía Plus |

# 17 — Despliegue en VPS con Dokploy y Docker

## 17.1 El camino

```
PC local  →  repositorio en GitHub  →  Dokploy  →  contenedor Docker en el VPS
```

Tú empujas a `main`, Dokploy detecta el cambio, construye la imagen a partir del
`Dockerfile` y reemplaza el contenedor. No hay que subir archivos por FTP ni tocar la
configuración de Apache: la imagen ya la trae dentro.

> **Antes de empezar.** Este documento asume que el VPS ya tiene Dokploy funcionando y
> otros proyectos desplegados. Solo cubre lo específico del SGPD.

### Verificado, no supuesto

Todo lo que sigue se probó levantando la pila completa con Docker Compose:

| | |
|---|---|
| Construcción de la imagen | **2 min 56 s** en un equipo de 16 núcleos |
| Tamaño de la imagen | **734 MB** |
| Arranque | espera la base, crea el esquema, aplica migraciones y queda `healthy` |
| Redespliegue | los archivos y los datos **sobreviven**; las migraciones no se repiten |
| `storage/` por URL | **404** — no es alcanzable |
| `config/`, `vendor/`, `.env`, `docs/`, `tools/` por URL | **404** en los cinco |

En un VPS compartido con menos núcleos la construcción tardará bastante más: cuenta
con 8 a 15 minutos y no la lances mientras tus otros proyectos tengan carga.

---

## 17.2 Qué cambió para que esto fuera posible

El proyecto nació atado a una instalación de XAMPP. Tres cosas lo impedían:

| Antes | Ahora |
|---|---|
| Credenciales escritas en `config/database.php` | Variables de entorno (`DB_*`) |
| La ruta `/SENA_SGPD` repetida en **45 sitios de 22 archivos**, con el documento 10 pidiendo editarlos a mano | Una sola variable: `APP_BASE_PATH` |
| Archivos subidos en `public/uploads/`, bajo la raíz web y dentro del contenedor | `storage/`, fuera de la raíz web y en un volumen |

La tercera resolvió además la vulnerabilidad **V-04**: los reportes de Sofía Plus ya no
son descargables por quien adivine un nombre de archivo. Se entregan por
`ArchivoController`, que exige sesión iniciada.

**El mismo código sirve en los dos entornos.** Con `APP_BASE_PATH=/SENA_SGPD` funciona en
tu XAMPP; con `APP_BASE_PATH=` vacío, en la raíz de un dominio. Verificado en ambos.

---

## 17.3 Antes de desplegar

### La base de datos

En Dokploy, crea un servicio **MySQL 8** (o MariaDB 10.4+) desde el panel, o usa el
`docker-compose.yml` incluido si prefieres que viaje junto a la aplicación. Anota
nombre de base, usuario y contraseña.

No hace falta importar ningún `.sql`: el contenedor prepara la base al arrancar.

### Las variables de entorno

En la sección **Environment** del servicio, pega esto y ajusta las credenciales:

```bash
APP_BASE_PATH=
APP_STORAGE_PATH=/var/www/html/storage
APP_MAX_UPLOAD_MB=20
APP_DEBUG=false
APP_AUTO_MIGRATE=true

DB_HOST=nombre-del-servicio-mysql
DB_PORT=3306
DB_NAME=sistema_sena
DB_USER=sgpd
DB_PASSWORD=la-contraseña-que-creaste
```

`APP_BASE_PATH` va **vacío**: la aplicación ocupa la raíz de su dominio.

`APP_DEBUG` va **en false**. Con `true`, los mensajes de excepción muestran rutas del
sistema de archivos y estructura de la base de datos.

### El volumen

Monta un volumen en `/var/www/html/storage`. **Sin esto, cada despliegue borra los
reportes importados y los PDF de proyectos**, porque el contenedor se reemplaza entero.

### El dominio

Asígnale un subdominio en el panel; Traefik se encarga del certificado. Una vez con
HTTPS, la cookie de sesión se emite con el atributo `Secure` de forma automática.

---

## 17.4 Desplegar

1. **Crea la aplicación** en Dokploy apuntando al repositorio de GitHub, rama `main`.
2. **Tipo de construcción:** Dockerfile.
3. **Pega las variables** de la sección anterior.
4. **Monta el volumen** en `/var/www/html/storage`.
5. **Asigna el dominio** y activa el certificado.
6. **Despliega.**

El arranque del contenedor hace esto solo:

- espera a que la base de datos acepte conexiones (hasta 60 s, configurable con
  `APP_DB_WAIT_SECONDS`);
- crea el esquema si la base está vacía y aplica las migraciones pendientes;
- ajusta los permisos del volumen;
- arranca Apache.

### Crear tu usuario

El sistema no es accesible sin iniciar sesión, y no hay instalador web a propósito: un
formulario que cree al primer administrador es una puerta abierta si alguien olvida
borrarlo. Abre la **terminal del contenedor** desde Dokploy y ejecuta:

```bash
php tools/crear-usuario.php admin ADMIN
```

Te pedirá tu nombre y una contraseña. Los roles son `ADMIN`, `COORDINADOR` e
`INSTRUCTOR`; solo los dos primeros pueden eliminar fichas.

### Comprobar

```
https://tu-subdominio/salud
```

Debe responder `{"estado":"ok","servicio":"sgpd","basedatos":"ok"}`. Es la ruta que usa
el *healthcheck* del contenedor, y es la única pública además del acceso. No revela
versiones, rutas ni el motivo de un fallo.

---

## 17.5 Qué no va a funcionar en el VPS

**SENA-IA y la voz clonada.** El modelo `deepseek-r1:8b` necesita unos 6 GB de RAM y el
servicio de voz descarga 1,8 GB de modelo. En un VPS compartido con otros proyectos no
hay sitio para eso.

No es un problema: el sistema **degrada solo**. El chat indica que el asistente no está
disponible y todo lo demás —tablero, importación, fases, deserción, proyecto formativo—
funciona con normalidad. Está verificado (RNF-28).

Si algún día quieres activarlo, apunta `OLLAMA_URL` a un servicio accesible desde el
contenedor. **Con una condición**: debe seguir siendo un servicio bajo tu control. El
contexto que se envía al modelo incluye datos personales de aprendices; apuntarlo a un
proveedor en la nube convierte el sistema en un exportador de datos personales sin base
legal (RNF-11, Ley 1581 de 2012).

---

## 17.6 Qué datos publicar

Si esto va en tu portafolio, **despliégalo con el archivo de muestra anonimizado**:

```
docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx
```

Produce cifras idénticas al reporte real —31 aprendices, 20 competencias, 75 resultados,
2.325 calificaciones— así que la demostración se ve igual de completa, con nombres
ficticios. Súbelo desde la pantalla de importación una vez desplegado.

Publicar el reporte real en un servidor accesible desde internet significa tratar datos
personales de personas identificadas por cédula. Puede hacerse, pero deja de ser una
decisión técnica: exige base legal para el tratamiento, y responsabilidad sobre las
copias de seguridad y sobre quién accede.

---

## 17.7 Probarlo en tu equipo antes de subir

```bash
cp .env.example .env          # y ajusta DB_PASSWORD y DB_ROOT_PASSWORD
docker compose up -d --build
```

Queda en `http://localhost:8080`. Si ese puerto ya lo usa otro proyecto —pasa a
menudo—, añade `APP_PORT=8099` a tu `.env` y usa ese. Para crear el usuario:

```bash
docker compose exec app php tools/crear-usuario.php admin ADMIN
```

Para dejarlo todo limpio, incluidos los datos:

```bash
docker compose down -v
```

---

## 17.8 Cuando algo falla

| Síntoma | Causa probable |
|---|---|
| `/salud` responde `503` con `basedatos: sin conexion` | Las variables `DB_*` no coinciden con el servicio de MySQL, o aún está arrancando |
| El contenedor reinicia en bucle | Mira el registro: el arranque escribe cada paso con el prefijo `[sgpd]` |
| Los estilos no cargan y todo se ve sin formato | `APP_BASE_PATH` tiene un valor cuando debería estar vacío |
| Al entrar redirige otra vez al acceso | La cookie no llega: comprueba que el dominio use HTTPS y que Traefik reenvíe `X-Forwarded-Proto` |
| Los archivos importados desaparecen tras cada despliegue | Falta el volumen en `/var/www/html/storage` |
| `not found` al arrancar el contenedor | El `entrypoint.sh` viajó con retornos de carro de Windows. El `.gitattributes` lo evita; comprueba que esté en el repositorio |
| «El contenido del archivo no corresponde a un .xlsx» | Es correcto: se valida el tipo real del contenido, no la extensión del nombre (V-04) |

Para ver qué está pasando dentro:

```bash
php tools/migrar.php          # vuelve a preparar la base; es idempotente
```

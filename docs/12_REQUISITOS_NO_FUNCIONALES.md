# 12 — Requisitos No Funcionales (RNF)

## 12.1 Propósito

Este documento enuncia **cómo debe comportarse** el sistema: con qué rendimiento, bajo
qué condiciones de seguridad y con qué restricciones legales y operativas. A diferencia
de los RF, un RNF atraviesa todos los módulos.

Cada requisito tiene una **métrica** y un **valor objetivo**. Cuando la columna
*medición* trae un número, procede de una ejecución real del sistema y no de una
estimación: instancia MySQL 8.4 + PHP 8.2, importando el reporte de muestra de 2.325
filas (9 de septiembre de 2026).

| Marca | Significado |
|-------|-------------|
| ✅ | Cumple el objetivo, verificado |
| 🟡 | Cumple parcialmente |
| ⛔ | No cumple |

---

## 12.2 Rendimiento

### RNF-01 · Tiempo de importación
**Objetivo:** procesar un reporte de hasta 3.000 filas en menos de 30 s. · **Estado:** ✅

| Métrica | Objetivo | Medición |
|---|---|---|
| 2.325 filas, base vacía | < 30 s | **11,7 s** |

**Deuda asociada:** la importación ejecuta hasta cuatro `INSERT` por fila más una
consulta de relectura por cada uno, porque `insertIgnore()` vuelve a consultar para
recuperar el `id` en lugar de usar `lastInsertId()`. Con volúmenes mayores el margen se
agota. Ver defecto **F-09**.

### RNF-02 · Tiempo de respuesta de las consultas analíticas
**Objetivo:** cualquier endpoint de lectura responde en menos de 2 s con una ficha de
hasta 40 aprendices. · **Estado:** ✅

Verificado sobre los 11 endpoints de API: todos responden por debajo del umbral.

**Riesgo:** `DashboardController::stats()` lanza doce consultas, dos de ellas con
subconsultas correlacionadas por aprendiz (`en_deuda`). El costo crece de forma no
lineal con el tamaño de la ficha; conviene revisarlo antes de superar los ~200
aprendices por ficha.

### RNF-03 · Conexiones a la base de datos
**Objetivo:** una sola conexión PDO por petición HTTP. · **Estado:** 🟡

El núcleo (`Core\Model`) implementa correctamente el patrón *singleton*, pero cuatro
puntos del código abren su propia conexión con `new \PDO`:
`ProgramaController` (3) y `ProyectoController::queryDirectly()` (1). Una petición a
`POST /api/proyecto/upload-pdf` puede abrir hasta tres conexiones adicionales.

### RNF-04 · Volumen de datos soportado
**Objetivo:** operar con al menos 50 fichas, 2.000 aprendices y 150.000 calificaciones
sin degradación perceptible. · **Estado:** ⛔ *no verificado*

No se ha ejecutado ninguna prueba de carga. Queda como pendiente explícito, no como
supuesto.

---

## 12.3 Seguridad

> El sistema gestiona datos personales de aprendices identificados por documento. Los
> requisitos de esta sección no son opcionales para un despliegue real.

### RNF-05 · Protección frente a inyección SQL
**Objetivo:** ninguna entrada del usuario se concatena a una sentencia SQL. · **Estado:** ✅

Toda consulta usa sentencias preparadas. Las pocas interpolaciones directas van
precedidas de conversión a entero, y la ordenación dinámica de
`ProgramaController::aprendicesFicha()` se valida contra lista blanca. Verificado con
seis vectores de prueba: ninguno explotable.

### RNF-06 · Autenticación y autorización
**Objetivo:** toda ruta que lea o modifique datos personales exige sesión iniciada, y
las operaciones destructivas exigen además el rol adecuado. · **Estado:** ⛔

Ninguna de las 32 rutas valida sesión, rol ni token. Verificado: `POST
/api/programa/delete-ficha` responde `{"success":true}` sin credencial alguna, y los
endpoints de creación de fases y actividades aceptan escrituras anónimas.

**Depende de:** RF-40, RF-41. **Ver:** vulnerabilidad **V-02**.

### RNF-07 · Protección contra CSRF
**Objetivo:** toda petición de escritura lleva un token de un solo uso ligado a la
sesión. · **Estado:** ⛔

Ningún endpoint exige token ni verifica `Content-Type`, de modo que un formulario
alojado en otro sitio puede disparar operaciones destructivas desde el navegador de un
instructor autenticado.

### RNF-08 · Escapado de la salida
**Objetivo:** ningún dato almacenado llega al navegador sin escapar según su contexto.
· **Estado:** ✅

Verificado con una prueba de concepto ejecutada en navegador: un nombre de aprendiz con
`<img src=x onerror=…>` importado desde el Excel queda como texto inerte y la página no
contiene ningún elemento `<img>`.

Mecanismos: `APP.esc()` antes de cualquier `innerHTML`, `htmlspecialchars()` sobre
`$pageTitle` en el layout y el breadcrumb, y `APP.mdSafe()` (DOMPurify) sobre la salida
del modelo de IA. Los controladores de evento en línea que mezclaban contexto HTML y
JavaScript se sustituyeron por atributos `data-*`.

**Historial:** vulnerabilidad **V-01**, corregida en `5d27772`.

### RNF-09 · Validación de los archivos cargados
**Objetivo:** todo archivo subido se valida por tipo MIME real y tamaño, y se almacena
con un nombre no adivinable, fuera de la raíz web. · **Estado:** ⛔

Hoy solo se comprueba la extensión del nombre. `ImportController::upload()` ni siquiera
consulta `$_FILES['error']`, y `uploadPdf()` crea el directorio con permisos `0777`. Los
archivos se guardan como `import_<timestamp>.xls`, un patrón enumerable.

**Ver:** vulnerabilidad **V-04**.

### RNF-10 · Cabeceras de seguridad y cookie de sesión
**Objetivo:** las respuestas incluyen `Content-Security-Policy`,
`X-Content-Type-Options`, `X-Frame-Options` y `Referrer-Policy`; la cookie de sesión se
emite con `HttpOnly`, `SameSite=Strict` y, en producción, `Secure`; los recursos de CDN
declaran `integrity`. · **Estado:** ⛔

Ninguna de las cuatro cabeceras está presente. `session_start()` se invoca sin
configurar los atributos de la cookie. Los cinco recursos externos se cargan sin
verificación de integridad. Una CSP habría contenido el impacto de V-01.

---

## 12.4 Protección de datos personales

### RNF-11 · Tratamiento conforme a la Ley 1581 de 2012
**Objetivo:** los datos personales de los aprendices no salen del entorno controlado por
la institución. · **Estado:** 🟡

- ✅ El modelo de IA es **local** (Ollama en `127.0.0.1:11434`): las consultas no viajan
  a ningún tercero.
- ✅ El repositorio ya no contiene reportes reales; se distribuye una muestra anonimizada
  (ver [README de documentación](README.md)).
- 🟡 `DataContextService` inyecta en el *prompt* el número de documento de hasta 30
  aprendices en cualquier consulta que mencione «aprendiz». Mientras el proveedor sea
  local el dato no sale del equipo, pero basta cambiar `config/ollama.php` a un
  proveedor en la nube para convertirlo en una transferencia internacional sin base
  legal. Ver **V-05**.

**Restricción de diseño:** el proveedor de IA **debe** permanecer local. Cualquier
cambio de `base_url` en `config/ollama.php` hacia un servicio externo exige antes una
evaluación de impacto y el consentimiento de los titulares.

### RNF-12 · Minimización de datos
**Objetivo:** cada componente accede solo a los datos que necesita. · **Estado:** 🟡

El contexto de IA envía el listado completo de aprendices con documento aunque la
pregunta sea agregada. Debería enviarse sin identificadores salvo petición explícita.

### RNF-13 · No exposición pública de archivos
**Objetivo:** ningún archivo con datos personales es accesible por HTTP sin
autenticación. · **Estado:** 🟡

- ✅ Los archivos con datos reales salieron del repositorio y `public/uploads/` dejó de
  versionarse (commit `196b6fb`, historial purgado).
- ⛔ En tiempo de ejecución, `public/uploads/` sigue bajo la raíz web y el `.htaccess`
  entrega cualquier archivo existente. Un reporte recién importado sigue siendo
  descargable por quien adivine su nombre. La corrección definitiva es moverlo fuera de
  la raíz y servirlo desde un controlador autenticado. Ver **V-03**.

---

## 12.5 Usabilidad

### RNF-14 · Idioma
**Objetivo:** toda la interfaz en español de Colombia. · **Estado:** ✅

### RNF-15 · Interfaz adaptable
**Objetivo:** utilizable en pantallas desde 1.366 px; la barra lateral se colapsa en
pantallas estrechas. · **Estado:** 🟡 — implementado, sin verificación sistemática en
tabletas y móviles.

### RNF-16 · Retroalimentación de las operaciones
**Objetivo:** toda operación asíncrona muestra estado de carga, y todo error muestra un
mensaje comprensible en español. · **Estado:** ✅ — sistema de *toasts* y estados de
carga en todas las vistas.

### RNF-17 · Accesibilidad
**Objetivo:** contraste mínimo AA y navegación completa por teclado. · **Estado:** ⛔
*no verificado* — no se ha auditado. El tema oscuro con texto atenuado
(`--text-muted`) es el principal candidato a incumplimiento.

---

## 12.6 Compatibilidad y portabilidad

### RNF-18 · Versión de PHP
**Objetivo:** PHP **8.1 o superior**. · **Estado:** 🟡

El código usa `match`, `str_contains`, tipos de unión y `never`. Hoy el proyecto declara
tres mínimos distintos y contradictorios:

| Fuente | Dice |
|---|---|
| `composer.json` | `>=8.0` |
| `docs/02_STACK_TECNOLOGICO.md` | 8.2+ |
| `docs/10_INSTALACION_DESPLIEGUE.md` | mínimo 8.1 |

**Acción:** unificar en 8.1 y corregir `composer.json`.

### RNF-19 · Motor de base de datos
**Objetivo:** MySQL 8.x o MariaDB 10.4+, tablas InnoDB. · **Estado:** ✅ — verificado
sobre MySQL 8.4.7.

### RNF-20 · Codificación de caracteres
**Objetivo:** `utf8mb4` de extremo a extremo. · **Estado:** 🟡

La conexión PDO fija `charset=utf8mb4`, pero `docs/SGPD_SENA.sql` **no declara juego de
caracteres ni cotejamiento**: hereda el del servidor (`utf8mb4_0900_ai_ci` en MySQL 8.4,
que puede diferir en MariaDB). La documentación menciona dos cotejamientos distintos
entre sí y ninguno coincide con el real.

**Acción:** declarar explícitamente `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
en el script y alinear la documentación. Es lo que evita defectos de codificación como
el que causó **F-01**.

### RNF-21 · Navegadores soportados
**Objetivo:** últimas dos versiones de Chrome, Edge y Firefox. · **Estado:** ✅ — el
dictado por voz (RF-39) degrada correctamente donde no hay Web Speech API.

### RNF-22 · Independencia de la ruta de despliegue
**Objetivo:** el sistema funciona en la raíz del dominio o en un subdirectorio, sin
editar código. · **Estado:** ⛔

La ruta base `/SENA_SGPD` está incrustada en siete archivos y el documento 10 instruye
editarlos a mano para producción. Debería resolverse con una constante en `config/`.

---

## 12.7 Mantenibilidad

### RNF-23 · Separación de capas
**Objetivo:** los controladores no ejecutan SQL; la persistencia vive en los modelos.
· **Estado:** 🟡

Cuatro controladores ejecutan SQL crudo; `DesercionController` es casi exclusivamente
SQL. `DashboardController` concentra 538 líneas y ocho responsabilidades. El
`README.md` afirma lo contrario de lo que hace el código.

### RNF-24 · Cobertura de pruebas automatizadas
**Objetivo:** al menos las funciones puras del importador y una prueba de contrato por
cada ruta registrada. · **Estado:** ⛔

El proyecto no tiene ninguna prueba automatizada ni dependencia de pruebas. Una simple
prueba de contrato ruta→método habría detectado **F-03**. Ver
[16_PLAN_PRUEBAS.md](16_PLAN_PRUEBAS.md).

### RNF-25 · Documentación sincronizada con el código
**Objetivo:** ninguna afirmación de la documentación contradice al código. · **Estado:** 🟡

La auditoría del 9 de septiembre de 2026 encontró **14 discrepancias** verificadas
(nombres de parámetros, métodos inexistentes, celdas del Excel, lenguaje del
microservicio de voz). Se corrigen en un bloque de trabajo aparte.

### RNF-26 · Convenciones de código
**Objetivo:** PSR-4 para autocarga, `declare(strict_types=1)` en todo archivo PHP,
nombres de dominio en español y estructura en inglés. · **Estado:** ✅

---

## 12.8 Fiabilidad y operación

### RNF-27 · Atomicidad de las operaciones compuestas
**Objetivo:** importar un reporte o eliminar una ficha ocurre por completo o no ocurre.
· **Estado:** ⛔

Ni la importación ni el borrado de fichas usan transacciones. Un fallo a mitad de una
importación de 2.325 filas deja la base en estado parcial, sin forma de revertirlo. Ver
**F-09**.

### RNF-28 · Degradación ante servicios externos caídos
**Objetivo:** si Ollama o el microservicio de voz no responden, el resto del sistema
sigue operativo y la interfaz lo informa. · **Estado:** ✅

Verificado: con el servidor de voz apagado, `POST /api/chat/synthesize` responde HTTP
200 con `tts_available: false` y el chat continúa en modo texto. `OllamaService::isAvailable()`
y `TTSService::isAvailable()` usan tiempos de espera cortos (2–3 s).

### RNF-29 · Registro de errores
**Objetivo:** los errores del servidor se registran con contexto suficiente para
diagnosticarlos, sin exponer detalles internos al usuario. · **Estado:** ⛔

No hay registro propio. `ImportController::upload()` devuelve
`$e->getMessage()` directamente al cliente, lo que puede filtrar rutas del sistema de
archivos y estructura de la base de datos.

### RNF-30 · Respaldo
**Objetivo:** procedimiento documentado de respaldo y restauración de la base de datos.
· **Estado:** ⛔ — no existe.

---

## 12.9 Resumen

| Categoría | ✅ | 🟡 | ⛔ | Total |
|---|--:|--:|--:|--:|
| Rendimiento | 2 | 1 | 1 | 4 |
| Seguridad | 2 | 0 | 4 | 6 |
| Protección de datos | 0 | 3 | 0 | 3 |
| Usabilidad | 2 | 1 | 1 | 4 |
| Compatibilidad y portabilidad | 2 | 2 | 1 | 5 |
| Mantenibilidad | 1 | 2 | 1 | 4 |
| Fiabilidad y operación | 1 | 0 | 3 | 4 |
| **Total** | **10** | **9** | **11** | **30** |

**Lectura del resumen.** La seguridad y la operación son las dos dimensiones más
débiles, y no por descuido de implementación sino por una decisión de alcance: la
versión 2.0 se declaró sin autenticación. Mientras RNF-06 siga en ⛔, el sistema es
apto para demostración académica y **no** para manejar datos reales de aprendices en un
servidor accesible.

# 16 — Plan de Pruebas

## 16.1 Alcance y estrategia

Este documento define qué hay que probar y cómo, derivándolo de los criterios de
aceptación de [14_HISTORIAS_USUARIO.md](14_HISTORIAS_USUARIO.md).

Estados de cada caso:

| Marca | Significado |
|---|---|
| ✅ **automatizado** | Vive en `tests/` y se ejecuta con un comando |
| ✅ ejecutado | Comprobado a mano en la auditoría, aún sin automatizar |
| ❌ falla | Comprobado y con defecto asociado |
| ⏳ pendiente | Especificado, sin ejecutar |

### Niveles

| Nivel | Qué cubre | Estado |
|---|---|---|
| **Unitario** | Funciones puras: parseo, normalización, categorización | ✅ implementado |
| **Contrato** | Que cada ruta registrada apunte a un método existente | ✅ implementado |
| **Integración** | Servicios contra una base de datos desechable | ✅ implementado |
| **Regresión de seguridad** | Escapado de la salida | ✅ implementado |
| **Sistema (API)** | Endpoints HTTP de extremo a extremo | ⏳ pendiente |
| **Interfaz** | Render y comportamiento en navegador | ⏳ manual |

### Cómo ejecutar la suite

```bash
php tests/run.php                # toda la suite
php tests/run.php Unit           # solo un directorio
php tests/run.php RutasTest      # solo una clase
```

Devuelve código de salida `0` si todo pasa y `1` si algo falla, así que sirve tal cual
en un hook de pre-commit o en integración continua.

Las pruebas de integración necesitan un servidor MySQL. Si no lo encuentran, **se omiten
en lugar de fallar**, de modo que la suite corre en cualquier máquina:

```bash
TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3306 TEST_DB_USER=root TEST_DB_PASS= php tests/run.php
```

Crean y destruyen su propia base `sgpd_pruebas`; **nunca tocan `sistema_sena`**.

> **Por qué un ejecutor propio y no PHPUnit.** El proyecto se construyó sin frameworks y
> la máquina de desarrollo no tiene Composer instalado. `tests/TestCase.php` usa los
> mismos nombres de aserción que PHPUnit a propósito: cuando se pueda instalar, migrar
> consiste en extender `PHPUnit\Framework\TestCase` y borrar ese archivo.

### Entorno de pruebas

- PHP 8.1+ con `pdo_mysql`, `mbstring`, `zip`, `gd`, `xml`, `curl`.
- Base de datos **desechable**, nunca la de trabajo: crear desde
  `docs/SGPD_SENA.sql` y destruir al terminar.
- **Datos de prueba:** `docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx` y
  `docs/1.Proyecto Formativo ADSO - 2480542.pdf`.
- Ollama y el microservicio de voz **apagados** por defecto: así se comprueba la
  degradación (RNF-28) sin depender de servicios externos.

> **Regla:** ninguna prueba usa reportes con datos personales reales. El archivo de
> muestra produce exactamente los mismos conteos, por lo que sirve como referencia fija.

### Valores de referencia

Todos los casos de integración se contrastan contra estas cifras, obtenidas del archivo
de muestra:

| Entidad | Registros esperados |
|---|---:|
| `programa` | 1 |
| `ficha` | 1 |
| `aprendiz` | 31 |
| `competencia` | 20 |
| `resultado_aprendizaje` | 75 |
| `funcionario` | 14 |
| `calificacion` | 2.325 |
| — de ellas `APROBADO` | 1.077 |
| — de ellas `POR EVALUAR` | 1.248 |
| Aprendices `EN FORMACION` | 24 |
| Aprendices `RETIRO VOLUNTARIO` | 6 |
| Aprendices `TRASLADADO` | 1 |

---

## 16.2 Importación (CP-01 … CP-07)

| Caso | Objetivo | Resultado esperado | Estado |
|---|---|---|---|
| **CP-01** | Subir un `.xlsx` válido | HTTP 200, `success: true` | ✅ ejecutado — falta a nivel HTTP |
| **CP-02** | Subir un archivo con extensión no permitida | HTTP 400, base de datos sin cambios | ⏳ pendiente |
| **CP-03** | Metadatos de ficha y programa | `nu_ficha=3142784`, `codigo_programa=228118`, fechas `2025-02-10` / `2027-05-10`, modalidad `PRESENCIAL` | ✅ **automatizado** |
| **CP-04** | Detección de columnas por encabezado | Intercambiar dos columnas produce los mismos registros | ⏳ pendiente |
| **CP-05** | Conteos tras la importación | Coinciden con los valores de referencia, 0 errores | ✅ **automatizado** |
| **CP-06** | Idempotencia | Reimportar no altera ningún conteo | ✅ **automatizado** |
| **CP-07** | Resumen distingue nuevos de actualizados | Reimportar informa 0 nuevos | ❌ **falla** — defecto F-11 |

### CP-05 · Detalle

**Precondición:** base recién creada desde `SGPD_SENA.sql`.
**Pasos:** `POST /api/import/upload` con el archivo de muestra.
**Resultado esperado:** los 7 conteos de la tabla de referencia, `errores = 0`,
`filas_procesadas = 2325`.
**Resultado obtenido (09-09-2026):** ✅ los 7 conteos coinciden; 11,7 s con el archivo
real, 14,0 s con el de muestra.

---

## 16.3 Tablero analítico (CP-08 … CP-15)

| Caso | Objetivo | Resultado esperado | Estado |
|---|---|---|---|
| **CP-08** | Selector de ficha | Cambiar de ficha recalcula todo el tablero | ✅ ejecutado |
| **CP-09** | Cálculo del avance global | `aprobados ÷ (aprobados + por evaluar) × 100`, un decimal | ✅ ejecutado |
| **CP-10** | Histograma completo | Las 4 categorías suman el total de activos | ✅ **automatizado** |
| **CP-11** | Cumplimiento por fases | Solo fases con resultados vinculados | ✅ ejecutado |
| **CP-12** | Competencias críticas | 5 competencias, orden ascendente | ⏳ pendiente |
| **CP-13** | **Regresión F-01:** foco de atención | Todo aprendiz Crítico aparece en la lista | ✅ **automatizado** |
| **CP-14** | Directorio de aprendices | Una fila por aprendiz de la ficha, con avance | ✅ ejecutado |
| **CP-15** | Filtros combinados | Los 5 filtros del enunciado, acumulativos | ✅ ejecutado — 41/34 sin filtro, 0/3 por competencia, 0/1 por resultado |

### CP-13 · Detalle — prueba de regresión obligatoria

Este caso cubre un defecto que estuvo activo en producción y que ninguna prueba
detectaba.

**Precondición:** ficha importada; añadir un aprendiz `EN FORMACION` con avance muy por
debajo del promedio (por ejemplo 10 de 75 resultados aprobados = 13,3 %).

**Pasos:** `GET /api/dashboard/stats?id_ficha=1`.

**Resultado esperado:**
- `insights.histograma["Crítico"] ≥ 1`
- `insights.en_riesgo ≥ 1`
- `insights.top_riesgo` contiene al menos un elemento con `categoria = "Crítico"`
- el primer elemento de `top_riesgo` es el de menor `score_riesgo`

**Resultado antes de la corrección:** ❌ `top_riesgo` traía 24 aprendices sanos y ninguno
con categoría `Crítico`.
**Resultado tras `5d27772`:** ✅ 25 elementos, el crítico en primer lugar con
`score_riesgo = −214,7`.

---

## 16.4 Aprendices, programas y fichas (CP-16 … CP-21)

| Caso | Objetivo | Resultado esperado | Estado |
|---|---|---|---|
| **CP-16** | Listado de aprendices | Todos los aprendices con su ficha | ✅ ejecutado |
| **CP-17** | Perfil individual | Historial completo agrupado por competencia y resultado | ✅ ejecutado |
| **CP-18** | Resultados en deuda | Solo los que el grupo ya aprobó y el aprendiz no | ✅ ejecutado |
| **CP-19** | Programas con estadísticas | Fichas, aprendices, activos y retirados por programa | ✅ ejecutado |
| **CP-20** | Detalle de programa | Sus fichas con avance | ✅ ejecutado |
| **CP-21** | Eliminar ficha | Borrado transaccional, con permiso y confirmación | ❌ **falla** — anónimo y sin transacción (V-02) |

---

## 16.5 Proyecto formativo (CP-22 … CP-28)

| Caso | Objetivo | Resultado esperado | Estado |
|---|---|---|---|
| **CP-22** | Crear proyecto | Se crean 4 fases; se rechaza si el programa ya tiene proyecto o el código se repite | ⏳ pendiente |
| **CP-23** | Extraer estructura del PDF | 4 fases, 6 actividades, 75 vínculos, 0 resultados sin vincular | ✅ ejecutado |
| **CP-24** | Validación al crear fase y actividad | Nombre vacío o proyecto inexistente se rechazan con HTTP 400 | ❌ **falla** — sin validación |
| **CP-25** | Asignar resultado a actividad | Se vincula; repetir no duplica | ⏳ pendiente |
| **CP-26** | Vincular proyecto a fichas | Solo afecta a fichas sin proyecto asignado | ⏳ pendiente |
| **CP-27** | Cumplimiento por fase | Porcentaje, aprobados, pendientes y total por fase | ✅ ejecutado |
| **CP-28** | Detalle de fase | Aprendices con su avance dentro de la fase | ✅ ejecutado |

### CP-23 · Detalle

**Resultado obtenido (09-09-2026):** ✅ `{fases: 4, actividades: 6, vinculos: 75}`, y
`SELECT COUNT(*) FROM resultado_aprendizaje WHERE id_resultado NOT IN (SELECT id_resultado FROM actividad_resultado)`
devuelve **0**.

**Observación:** `ra_not_found` trae 75 entradas que no son resultados de aprendizaje
sino códigos de seis dígitos capturados de más por la expresión regular. El mensaje al
usuario dice «75 RA del PDF no estaban en el Excel» justo después de anunciar éxito.
Defecto **F-06**: el caso debe extenderse para exigir `ra_not_found` deduplicado y
filtrado.

---

## 16.6 Deserción (CP-29 … CP-31)

| Caso | Objetivo | Resultado esperado | Estado |
|---|---|---|---|
| **CP-29** | Registrar novedad de retiro | Con 6 retiros y 1 traslado importados, `novedad_retiro` tiene 7 filas | ✅ **automatizado** |
| **CP-30** | Motivos, instructores y trazabilidad | Datos coherentes con `novedad_retiro` | ✅ ejecutado — 7 registros, 4 instructores, fase resuelta |
| **CP-31** | Estado vacío honesto | Sin datos, mensaje explícito y **ninguna gráfica con valores inventados** | ✅ ejecutado |
| **CP-55** | Idempotencia de las novedades | Reimportar no duplica; si el aprendiz reingresa, su novedad se elimina | ✅ **automatizado** |
| **CP-56** | Clasificación de estados de salida | Retiro, traslado, cancelación y deserción son salidas; aplazado y condicionado no | ✅ **automatizado** |

---

## 16.7 SENA-IA (CP-32 … CP-37)

| Caso | Objetivo | Resultado esperado | Estado |
|---|---|---|---|
| **CP-32** | Chat con contexto | La respuesta usa datos reales de la base | ⏳ pendiente |
| **CP-33** | Transmisión SSE | Los tokens llegan progresivamente | ⏳ pendiente |
| **CP-34** | Gráfica generada | Pedir «gráfica» produce una acción `[CHART:…]` válida | ⏳ pendiente |
| **CP-35** | Reporte CSV | El CSV descargado tiene encabezados y filas correctas | ⏳ pendiente |
| **CP-36** | **Regresión F-03:** ruta de voz | `POST /api/chat/synthesize` responde HTTP 200 | ✅ **automatizado** |
| **CP-37** | Degradación sin servicios | Con Ollama y TTS apagados, el sistema sigue operativo | ✅ ejecutado |

### CP-36 · Detalle — prueba de contrato

**Resultado antes de la corrección:** ❌ `/api/chat/speak` → HTTP 500 «Action not found»;
`/api/chat/synthesize` → HTTP 404.
**Resultado tras `5d27772`:** ✅ HTTP 200 con `{"audio_url":null,"tts_available":false}`
estando el microservicio apagado.

---

## 16.8 Reglas de negocio (CP-38 … CP-50)

| Caso | Regla | Resultado esperado | Estado |
|---|---|---|---|
| **CP-38** | RF-40 · autenticación | Ruta protegida sin sesión redirige al inicio de sesión | ❌ **falla** — sin implementar |
| **CP-39** | RN-01 | Aprendiz sin tipo de documento es rechazado | ⏳ pendiente |
| **CP-40** | RN-02 | Documento duplicado es rechazado | ⏳ pendiente |
| **CP-41** | RN-03 | Documento repetido en otra ficha traslada al aprendiz | ✅ ejecutado — comportamiento confirmado |
| **CP-42** | RN-04 | Aprendiz sin estado es rechazado | ❌ **falla** — la columna admite nulos |
| **CP-43** | RN-05, RN-06 | Borrar competencia elimina sus resultados en cascada | ⏳ pendiente |
| **CP-44** | RN-07 | Código de competencia o resultado duplicado es rechazado | ⏳ pendiente |
| **CP-45** | RN-08 | Una competencia se asocia a varios programas sin duplicar | ⏳ pendiente |
| **CP-46** | RN-10 | Borrar aprendiz elimina sus juicios en cascada | ⏳ pendiente |
| **CP-47** | RN-12 | Un juicio desconocido se normaliza a `POR EVALUAR` | ✅ **automatizado** |
| **CP-48** | RN-13 | Un juicio `POR EVALUAR` admite fecha y funcionario nulos | ✅ **automatizado** |
| **CP-49** | RN-15 | Los KPIs excluyen a retirados y trasladados | ✅ ejecutado — KPI 24 frente a 31 |
| **CP-50** | RN-19 | Recargar el PDF reemplaza la estructura previa | ⏳ pendiente |
| **CP-54** | RN-12 · **regresión F-12** | `NO APROBADO` no se almacena como `APROBADO` | ✅ **automatizado** |

---

## 16.9 Contrato, seguridad y transversales (CP-51 … CP-53)

### CP-51 · Contrato de rutas
**La prueba más rentable del proyecto.** Recorre las rutas registradas en `index.php` y
verifica que la clase exista y el método esté definido.

```php
// Esbozo. Habria detectado F-03 en el momento de introducirlo.
foreach ($router->getRoutes() as $ruta) {
    $this->assertTrue(class_exists($ruta['controller']));
    $this->assertTrue(method_exists($ruta['controller'], $ruta['action']));
}
```

> Requiere exponer un `getRoutes()` en `Core\Router`, hoy inexistente.

**Estado:** ✅ **automatizado** en `tests/Contract/RutasTest.php`. Cubre las 36 rutas y
añade tres comprobaciones más: que cada acción sea pública, que las rutas con
parámetros tengan acciones que los acepten, y que toda ruta invocada desde
`public/js` exista en el router.

### CP-52 · Regresión V-01 · escapado de la salida
**Precondición:** importar un reporte cuyo campo *Nombre* contenga
`<img src=x onerror=alert(document.domain)>`.

**Resultado esperado:**
- `GET /aprendiz/{id}` devuelve el payload escapado en `<title>` y en el breadcrumb.
- Tras cargar el tablero, `document.querySelectorAll('img').length === 0`.
- El texto se ve literal en la celda.

**Resultado antes de la corrección:** ❌ el payload creaba un `<img>` vivo en el DOM.
**Resultado tras `5d27772`:** ✅ 0 elementos `<img>`; el nombre aparece como texto inerte.
**Estado:** ✅ **automatizado** en `tests/Regression/EscapadoTest.php`. Verifica el
escapado en servidor renderizando las plantillas, y vigila que no reaparezca ninguno
de los 14 patrones de interpolación sin escapar que se corrigieron.

### CP-53 · Cabeceras de seguridad
**Resultado esperado:** las respuestas incluyen `Content-Security-Policy`,
`X-Content-Type-Options`, `X-Frame-Options` y `Referrer-Policy`; la cookie de sesión
lleva `HttpOnly` y `SameSite`.
**Estado:** ❌ **falla** — ninguna cabecera presente (RNF-10).

---

## 16.10 Estado de la suite

| Resultado | Casos |
|---|--:|
| ✅ **Automatizado** | 14 |
| ✅ Pasa, ejecutado a mano | 19 |
| ❌ Falla | 6 |
| ⏳ Pendiente | 17 |
| **Total** | **56** |

### Lo que hay automatizado

`php tests/run.php` — **52 pruebas, 31 s.**

| Archivo | Cubre | Pruebas |
|---|---|--:|
| `tests/Contract/RutasTest.php` | CP-36, CP-51 | 6 |
| `tests/Unit/ImportadorTest.php` | CP-47, CP-48, CP-54 | 14 |
| `tests/Unit/InsightsTest.php` | CP-10, CP-13 | 8 |
| `tests/Unit/NovedadRetiroTest.php` | CP-56 | 4 |
| `tests/Integration/ImportacionTest.php` | CP-03, CP-05, CP-06, CP-29, CP-55 | 12 |
| `tests/Regression/EscapadoTest.php` | CP-52 | 8 |

Los tres defectos ya corregidos tienen su prueba de regresión: **F-01** en
`InsightsTest`, **F-03** en `RutasTest` y **V-01** en `EscapadoTest`. **F-12** se
descubrió al escribir `ImportadorTest` y quedó cubierto por CP-54.

### Lo que falta por automatizar

1. **Nivel HTTP (CP-01, CP-02, CP-21, CP-38).** La suite llama a los servicios
   directamente; falta un cliente que ejerza los endpoints reales, con sus códigos de
   estado y su validación de entrada.
2. **Consultas analíticas (CP-09, CP-11, CP-12, CP-49).** Requieren datos en base y
   comparar contra cifras conocidas; la infraestructura ya existe en
   `ImportacionTest`.
3. **Proyecto formativo (CP-22 … CP-26, CP-50).** El parser de PDF es determinista y el
   archivo de prueba está en `docs/`: son automatizables tal cual.
4. **Reglas de negocio del esquema (CP-39 … CP-46).** Cada una es un `INSERT` que debe
   fallar; rápidas de escribir sobre la base desechable.
5. **Interfaz.** Solo con un navegador automatizado. Es lo último por costo/beneficio,
   salvo la parte de escapado, que ya está cubierta de forma estática.

### Los diez casos que fallan

| Caso | Defecto | Requisito |
|---|---|---|
| CP-07 | F-11 · el resumen no distingue nuevos de actualizados | RF-06 |
| CP-21 | V-02 · borrado anónimo y sin transacción | RF-20, RNF-06 |
| CP-24 | Sin validación al crear fase y actividad | RF-23, RF-24 |
| CP-38 | Sin autenticación | RF-40, RNF-06 |
| CP-42 | RN-04 sin aplicar | RN-04 |
| CP-53 | Sin cabeceras de seguridad | RNF-10 |

Cuatro de los seis dependen de la misma historia sin implementar: **HU-19, el control
de acceso**.

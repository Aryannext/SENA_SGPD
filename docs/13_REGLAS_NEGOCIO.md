# 13 — Reglas de Negocio (RN)

## 13.1 Propósito

Una regla de negocio es una restricción del dominio que el sistema debe respetar
**siempre**, independientemente de la funcionalidad que se esté ejecutando.

Todas las reglas de este documento **ya están vivas en el sistema**: se extrajeron del
esquema de base de datos y del código. Documentarlas cumple dos funciones: es un
entregable exigido por el enunciado del ejercicio, y evita que alguien las rompa por
desconocimiento al modificar el código.

### Dónde se aplica cada regla

| Marca | Significado | Fortaleza |
|---|---|---|
| 🛢️ **Esquema** | Restricción declarada en la base de datos | Inviolable |
| ⚙️ **Código** | Validada en PHP | Depende de que se pase por ese camino |
| ⚠️ **No aplicada** | Enunciada pero sin mecanismo que la haga cumplir | Solo por convención |

---

## 13.2 Identidad y matrícula del aprendiz

### RN-01 · Todo aprendiz tiene un tipo de documento
🛢️ Esquema · `aprendiz.ti_documento ENUM('CC','TI','CE','PA','PEP') NOT NULL`

Los cinco valores corresponden a los tipos que maneja Sofía Plus.
**Verificación:** insertar un aprendiz sin tipo de documento falla.

### RN-02 · El número de documento identifica unívocamente a un aprendiz
🛢️ Esquema · `aprendiz.nu_documento UNIQUE`

**Verificación:** dos aprendices distintos no pueden compartir documento.

### RN-03 · Un aprendiz pertenece a una sola ficha
🛢️ Esquema (consecuencia de RN-02) · `aprendiz.id_ficha` es un único valor

Al ser el documento único en toda la tabla, el aprendiz existe una sola vez y por tanto
tiene una sola ficha.

> **Consecuencia importante.** Si el mismo documento aparece en el reporte de otra
> ficha, la importación **traslada** al aprendiz a la ficha nueva y su matrícula
> anterior se pierde, junto con la adscripción de sus calificaciones.
> Verificado: `id_ficha` pasa de 1 a 2 en silencio.
>
> Es una **limitación aceptada** del modelo actual, no un defecto de programación. Si el
> sistema debe soportar aprendices en varias fichas, hay que separar la entidad
> `persona` de la entidad `matricula`, con clave única `(id_persona, id_ficha)`.
> Ver defecto **F-04**.

### RN-04 · Todo aprendiz tiene un estado
⚠️ **No aplicada** · `aprendiz.estado VARCHAR(50) DEFAULT NULL`

Es la regla 3 del enunciado, pero la columna admite nulos y el importador escribe cadena
vacía cuando el origen viene en blanco. El estado se migró de `ENUM` a `VARCHAR(50)`
para admitir todos los valores de Sofía Plus sin restricción, y en esa migración se
perdió la obligatoriedad.

**Estados observados:** `EN FORMACION`, `RETIRO VOLUNTARIO`, `TRASLADADO`, `CANCELADO`,
`APLAZADO`, `CONDICIONADO`, `CERTIFICADO`, `POR CERTIFICAR`.

**Acción recomendada:** `NOT NULL DEFAULT 'EN FORMACION'`, y validar contra una tabla
de estados en lugar de una lista quemada en JavaScript.

---

## 13.3 Estructura académica

### RN-05 · Un resultado de aprendizaje pertenece a exactamente una competencia
🛢️ Esquema · `resultado_aprendizaje.id_competencia` con clave foránea

Es la regla 5 del enunciado. **Verificación:** borrar una competencia elimina en cascada
sus resultados de aprendizaje.

### RN-06 · Una competencia agrupa uno o varios resultados de aprendizaje
🛢️ Esquema · relación 1:N — regla 4 del enunciado.

### RN-07 · El código de la competencia y el del resultado son únicos
🛢️ Esquema · `competencia.cod_competencia UNIQUE`, `resultado_aprendizaje.cod_resultado UNIQUE`

Permite reconciliar la información entre importaciones sucesivas sin duplicar catálogos.

> **Alcance de la unicidad.** Los códigos son únicos **globalmente**, no por programa.
> Un mismo código de resultado no puede pertenecer a dos competencias distintas aunque
> sean de programas diferentes. Con el catálogo del SENA esto se cumple, pero es una
> restricción a tener presente si se incorporan programas de otras regionales.

### RN-08 · Una competencia se asocia a uno o más programas
🛢️ Esquema · tabla pivote `programa_competencia` con clave primaria compuesta

Refleja que las competencias transversales (inglés, ética, emprendimiento) son
compartidas por muchos programas.

### RN-09 · Un programa tiene, como máximo, un proyecto formativo
⚙️ Código · `ProyectoController::crearProyecto()`

**No está en el esquema:** nada impide insertar un segundo proyecto para el mismo
programa por otra vía. **Acción recomendada:** `UNIQUE KEY (id_programa)` en
`proyecto_formativo`.

---

## 13.4 Juicios evaluativos

### RN-10 · Todo juicio se asocia a un aprendiz y a un resultado de aprendizaje
🛢️ Esquema · claves foráneas `calificacion.id_aprendiz` e `id_resultado`

Reglas 6 y 7 del enunciado. Ambas en cascada: al borrar el aprendiz o el resultado,
desaparecen sus juicios.

### RN-11 · Un aprendiz tiene, como máximo, un juicio por resultado de aprendizaje
🛢️ Esquema · `UNIQUE KEY unique_juicio (id_aprendiz, id_resultado)`

Es la regla que hace posible RF-05: reimportar actualiza el juicio en vez de duplicarlo.

**Verificación:** tras dos importaciones del mismo archivo, `calificacion` conserva
2.325 registros.

### RN-12 · El juicio solo admite valores controlados
🛢️ Esquema · `ENUM('APROBADO','POR EVALUAR','NO APROBADO')`
⚙️ Código · `ExcelImportService::normalizeJuicio()`

Es la regla 10 del enunciado. La normalización acepta las abreviaturas del origen
(`A` → `APROBADO`, `D` → `NO APROBADO`) y **cualquier valor desconocido cae en
`POR EVALUAR`**.

> Esa decisión de diseño merece atención: un valor inesperado del origen no genera error
> ni advertencia, se convierte silenciosamente en «pendiente». Es prudente para no
> perder filas, pero conviene registrar cuántas conversiones por defecto ocurrieron.

> **Defecto F-12, corregido.** El orden de las ramas estaba invertido: como la cadena
> `NO APROBADO` contiene `APROB`, la rama de aprobación se evaluaba primero y **todo
> juicio reprobado se almacenaba como `APROBADO`**. Pasó inadvertido porque el reporte
> de la ficha ADSO 3142784 no contiene ni un solo `NO APROBADO`: el defecto habría
> corrompido los datos en la primera ficha que sí tuviera juicios reprobados. Lo
> encontró la prueba unitaria CP-54 al escribirla, no una revisión de código.

### RN-13 · Fecha, hora y funcionario del juicio son opcionales
🛢️ Esquema · ambas columnas admiten `NULL`

**Desviación explícita frente a las reglas 8 y 9 del enunciado**, que exigen que todo
juicio registre fecha y funcionario.

**Justificación:** en el reporte de Sofía Plus los juicios en estado `POR EVALUAR`
llegan sin evaluar, con el campo de funcionario como `"  -  "` y la fecha vacía.
Exigirlos obligaría a inventar datos o a descartar más de la mitad de las filas.

**Medición sobre el reporte real (2.401 registros):**

| | Registros | |
|---|---:|---|
| Sin funcionario | 1.323 | 55 % — casi todos `POR EVALUAR` |
| Sin fecha | 1.249 | 52 % — casi todos `POR EVALUAR` |
| `APROBADO` sin funcionario | 10 | anomalía del origen |
| `APROBADO` sin fecha | 1 | anomalía del origen |

**Regla derivada:** *todo juicio en estado `APROBADO` o `NO APROBADO` debería registrar
fecha y funcionario.* No está aplicada; las 11 excepciones anteriores provienen del
propio Sofía Plus. Sería un buen control de calidad a reportar durante la importación.

---

## 13.5 Cálculo de indicadores

### RN-14 · El avance de un aprendiz es la proporción de resultados aprobados
⚙️ Código

```
avance (%) = aprobados ÷ total de resultados evaluables × 100
```
Redondeado a un decimal. Cuando el denominador es cero, el avance es cero, nunca error.

### RN-15 · Los indicadores del tablero se calculan solo sobre aprendices activos
⚙️ Código · `WHERE a.estado LIKE '%FORMACION%'`

Retirados, trasladados y certificados quedan fuera de KPIs, histograma y foco de
atención, porque su avance congelado distorsionaría la lectura del grupo.

> Aparecen, en cambio, en el **directorio de aprendices** (RF-13), que sí muestra todos
> los estados. La diferencia entre ambos conjuntos es una fuente habitual de confusión:
> el KPI dice 24 y la tabla lista 31.

### RN-16 · Un resultado «en deuda» es el que el grupo ya aprobó y el aprendiz no
⚙️ Código

Un resultado está en deuda para un aprendiz cuando **él lo tiene `POR EVALUAR`** y
**al menos un compañero de su misma ficha lo tiene `APROBADO`**.

Es el indicador más útil del sistema, porque aísla el rezago individual del avance
natural del grupo: si nadie lo ha aprobado todavía, no es rezago.

### RN-17 · La categoría de rendimiento es relativa al promedio del grupo
⚙️ Código · `DashboardController::stats()`

Siendo `dif = avance del aprendiz − promedio de la ficha`:

| Categoría | Condición |
|---|---|
| Crítico | `dif < −15` **o** `en deuda ≥ 5` |
| Rezagado | `dif < −5` **o** `en deuda ≥ 2` |
| Al Día | `dif ≤ 5` |
| Adelantado | resto |

Y el orden de atención: `score = dif − (en deuda × 5)`, ascendente.

> **Limitación conocida y relevante.** En los juicios evaluativos del SENA el instructor
> aprueba un resultado para toda la ficha a la vez, de modo que el avance individual
> tiende a ser idéntico. Medición real: **los 24 aprendices activos de la ficha 3142784
> tienen exactamente 41/75 = 54,7 %**. Con un criterio puramente relativo, el resultado
> es que nadie destaca ni nadie está en riesgo.
>
> Además la métrica es inestable: basta añadir un aprendiz con avance bajo para que el
> promedio se mueva y los 24 sanos pasen en bloque a «Rezagado», por lo sensible que es
> el disparador `en deuda ≥ 2`.
>
> **Acción recomendada:** combinar el criterio relativo con un umbral absoluto —por
> ejemplo, avance inferior al 40 %, o muy por debajo del tiempo cronológico transcurrido
> de la ficha, que el sistema ya calcula— y ponderar `en_deuda` por el total de
> resultados en lugar de usar una cifra fija. Ver defecto **F-05**.

---

## 13.6 Proyecto formativo

### RN-18 · Un proyecto nuevo nace con las cuatro fases del SENA
⚙️ Código · Análisis, Planeación, Ejecución, Evaluación.

### RN-19 · Recargar el PDF reemplaza la estructura previa del proyecto
⚙️ Código · `ProyectoController::uploadPdf()`

Borra fases, actividades y vínculos anteriores antes de volver a extraer. Es
deliberado —el PDF es la fuente de verdad—, pero **destructivo**: si se hicieron
asignaciones manuales de resultados, se pierden sin aviso.

**Acción recomendada:** advertirlo en la interfaz antes de confirmar la carga.

### RN-20 · Un resultado solo se vincula si pertenece al programa del proyecto
⚙️ Código · `ResultadoAprendizaje::findByCodigoAndPrograma()`

Impide que códigos de otro programa que aparezcan en el PDF contaminen la estructura.

### RN-21 · La asignación resultado↔actividad no admite duplicados
🛢️ Esquema · clave primaria compuesta en `actividad_resultado`

### RN-22 · Asignar un proyecto no reasigna fichas que ya tienen uno
⚙️ Código · `Ficha::linkToProjectByProgram()` filtra por `id_proyecto IS NULL`

---

## 13.7 Novedades de retiro

### RN-26 · La novedad de retiro se deriva del estado del aprendiz
⚙️ Código · `NovedadRetiroService::sincronizar()`

Sofía Plus no entrega una hoja de novedades: entrega el estado actual de cada aprendiz.
La novedad se reconstruye con la huella que dejaron sus juicios evaluativos:

| Campo | Origen |
|---|---|
| `motivo` | El propio estado (`RETIRO VOLUNTARIO`, `TRASLADADO`, `CANCELADO`…) |
| `fecha` | La del último juicio que se le registró |
| `id_funcionario` | Quien registró ese último juicio |
| `id_fase` | La fase del último resultado que alcanzó a aprobar |

**Estados que cuentan como salida:** los que contienen `RETIRO`, `CANCELAD`,
`CANCELAMIENTO`, `TRASLADAD` o `DESERC`. Se comparan por contenido porque Sofía Plus
añade sufijos. `APLAZADO`, `CONDICIONADO`, `CERTIFICADO` y `POR CERTIFICAR` **no** son
salidas: el aprendiz sigue vinculado.

**Sincronización:** se ejecuta al final de cada importación. Es idempotente —actualiza
en vez de duplicar— y si un aprendiz vuelve a formación, su novedad se elimina.

> **Es un dato derivado, no declarado.** La fase de abandono y el instructor son
> inferencias razonables, no un registro que alguien haya hecho. Sirven para detectar
> patrones; no deberían usarse como base de una decisión sobre una persona concreta sin
> contrastarlos primero.

---

## 13.8 Ficha y programa

### RN-23 · El estado de la ficha se normaliza a tres valores
🛢️ Esquema + ⚙️ Código · `EN EJECUCION`, `TERMINADA`, `CANCELADA`; ante un valor
desconocido se asume `EN EJECUCION`.

### RN-24 · La modalidad se normaliza a tres valores
🛢️ Esquema + ⚙️ Código · `PRESENCIAL`, `VIRTUAL`, `A DISTANCIA`; por defecto
`PRESENCIAL`.

### RN-25 · El número de ficha y el código de programa son únicos
🛢️ Esquema · `ficha.nu_ficha UNIQUE`, `programa.codigo_programa UNIQUE`

Son los identificadores oficiales de Sofía Plus y la clave de reconciliación entre
importaciones.

---

## 13.9 Cumplimiento de las reglas del enunciado

El enunciado propone diez reglas de negocio de ejemplo. Cobertura:

| # | Regla del enunciado | RN | Estado |
|---|---|---|---|
| 1 | Todo aprendiz debe tener un tipo de documento | RN-01 | ✅ Esquema |
| 2 | El número de documento no se repite entre aprendices | RN-02 | ✅ Esquema |
| 3 | Todo aprendiz debe tener un estado | RN-04 | ⚠️ **No aplicada** — columna nullable |
| 4 | Una competencia puede tener uno o varios resultados | RN-06 | ✅ Esquema |
| 5 | Un resultado pertenece a una competencia | RN-05 | ✅ Esquema |
| 6 | Un juicio se asocia a un aprendiz | RN-10 | ✅ Esquema |
| 7 | Un juicio se asocia a un resultado de aprendizaje | RN-10 | ✅ Esquema |
| 8 | Todo juicio registra fecha y hora | RN-13 | ⚠️ **Desviación justificada** |
| 9 | Todo juicio indica el funcionario que lo registró | RN-13 | ⚠️ **Desviación justificada** |
| 10 | El juicio solo admite valores controlados | RN-12 | ✅ Esquema |

**Siete de diez** se aplican en el esquema, que es el lugar más fuerte donde puede
vivir una regla. Las tres restantes son de dos naturalezas distintas:

- La **regla 3** es un incumplimiento real y corregible: la obligatoriedad del estado se
  perdió al migrar la columna de `ENUM` a `VARCHAR`.
- Las **reglas 8 y 9** son una desviación consciente y documentada: el dato simplemente
  no existe en el origen para los juicios pendientes. Aplicarlas obligaría a fabricar
  información.

---

## 13.10 Reglas que faltan por aplicar

| RN | Regla | Mecanismo propuesto |
|---|---|---|
| RN-04 | Todo aprendiz tiene estado | `NOT NULL DEFAULT 'EN FORMACION'` |
| RN-09 | Un programa, un proyecto | `UNIQUE KEY (id_programa)` en `proyecto_formativo` |
| RN-13 | Juicio evaluado ⇒ fecha y funcionario | Control de calidad reportado en la importación |
| RN-19 | Recarga destructiva del PDF | Confirmación explícita en la interfaz |

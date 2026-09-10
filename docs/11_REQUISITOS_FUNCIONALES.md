# 11 — Requisitos Funcionales (RF)

## 11.1 Propósito y convenciones

Este documento enuncia **qué debe hacer** el sistema. Cada requisito es verificable:
tiene entradas, salidas y un criterio objetivo de aceptación. La numeración `RF-nn`
es estable y se referencia desde las historias de usuario
([14_HISTORIAS_USUARIO.md](14_HISTORIAS_USUARIO.md)), el plan de pruebas
([16_PLAN_PRUEBAS.md](16_PLAN_PRUEBAS.md)) y la matriz de trazabilidad
([15_MATRIZ_TRAZABILIDAD.md](15_MATRIZ_TRAZABILIDAD.md)).

### Estado

| Marca | Significado |
|-------|-------------|
| ✅ **Implementado** | Construido y verificado ejecutando el sistema |
| 🟡 **Parcial** | Funciona en parte, o falta exponerlo en la interfaz |
| ⛔ **No implementado** | Documentado como alcance, pero sin construir |

### Prioridad (MoSCoW)

`M` = imprescindible · `S` = importante · `C` = deseable · `W` = fuera de esta versión

> **Origen de los requisitos.** RF-01 a RF-28 derivan del enunciado
> *«Diseño lógico de un sistema de gestión de juicios evaluativos»*, variante avanzada (b).
> RF-29 en adelante son alcance propio del SGPD, no exigido por el enunciado.

---

## 11.2 Módulo: Importación de datos

### RF-01 · Cargar el reporte de juicios evaluativos
**Prioridad:** M · **Estado:** ✅

El sistema debe permitir subir un archivo `.xls` o `.xlsx` con el formato del
«Reporte de Juicios de Evaluación» de Sofía Plus.

- **Entrada:** archivo `multipart/form-data`, campo `excel_file`.
- **Salida:** JSON `{success, message, data:{stats, errors}}`.
- **Criterio de aceptación:** un archivo con extensión distinta de `.xls`/`.xlsx` se
  rechaza con HTTP 400 y mensaje explicativo; un archivo válido responde HTTP 200.
- **Realización:** `POST /api/import/upload` → `ImportController::upload()`.

### RF-02 · Extraer los metadatos de la ficha y el programa
**Prioridad:** M · **Estado:** ✅

Del encabezado del reporte se deben leer ficha, código y nombre de programa, estado,
fechas y modalidad.

- **Celdas de origen:** `C3` ficha · `C4` código de programa · `C6` denominación ·
  `C7` estado de la ficha · `C8` fecha de inicio · `C9` fecha de fin · `C10` modalidad.
- **Criterio de aceptación:** tras importar el archivo de muestra, la tabla `ficha`
  contiene `nu_ficha = 3142784`, `fecha_inicio = 2025-02-10`, `fecha_fin = 2027-05-10`,
  y `programa` contiene `codigo_programa = 228118`.
- **Realización:** `ExcelImportService::import()`.
- **Nota:** las fechas vienen como número de serie de Excel y se convierten con
  `ExcelDate::excelToDateTimeObject()`.

### RF-03 · Detectar las columnas por su encabezado
**Prioridad:** S · **Estado:** ✅

El mapeo de columnas no debe depender de posiciones fijas: se resuelve leyendo los
encabezados de la fila 13.

| Palabra(s) clave en el encabezado | Campo |
|---|---|
| «Tipo» + «Doc» | tipo de documento |
| «Número» + «Doc» | número de documento |
| «Nombre» sin «Doc» | nombres |
| «Apellido» | apellidos |
| «Estado» | estado del aprendiz |
| «Competencia» | competencia (código + nombre) |
| «Resultado» | resultado de aprendizaje |
| «Juicio» sin «Fecha» ni «Funcionario» | juicio evaluativo |
| «Fecha» + «Juicio» | fecha y hora del juicio |
| «Funcionario» | funcionario evaluador |

- **Criterio de aceptación:** si se intercambian dos columnas de datos en el archivo,
  la importación produce los mismos registros.

### RF-04 · Normalizar y persistir la información
**Prioridad:** M · **Estado:** ✅

Cada fila del reporte debe distribuirse en las entidades correspondientes, sin
duplicar información entre ellas.

- **Criterio de aceptación:** importando el archivo de muestra (2.325 filas) se obtienen
  exactamente **31** aprendices, **20** competencias, **75** resultados de aprendizaje,
  **14** funcionarios y **2.325** calificaciones, con **0 errores**.
- **Realización:** `ExcelImportService` + modelos `Aprendiz`, `Competencia`,
  `ResultadoAprendizaje`, `Funcionario`, `Calificacion`.
- **Depende de:** RN-01, RN-02, RN-03, RN-04, RN-10.

### RF-05 · Ser idempotente frente a reimportaciones
**Prioridad:** M · **Estado:** ✅

Volver a cargar el mismo archivo debe actualizar, nunca duplicar.

- **Criterio de aceptación:** tras importar dos veces el mismo archivo, los conteos de
  todas las tablas son idénticos a los de la primera importación.
- **Realización:** `INSERT IGNORE` y `ON DUPLICATE KEY UPDATE` sobre las claves únicas.
- **Depende de:** RN-02, RN-05.

### RF-06 · Informar el resultado de la importación
**Prioridad:** S · **Estado:** 🟡

Al terminar debe mostrarse un resumen con filas procesadas, registros por entidad y
errores.

- **Criterio de aceptación:** el resumen distingue entre registros **nuevos** y
  **actualizados**.
- **Brecha conocida:** hoy se informan registros *procesados*, no nuevos: al reimportar
  el mismo archivo se vuelve a anunciar «31 aprendices». Ver defecto **F-11**.

---

## 11.3 Módulo: Dashboard analítico

### RF-07 · Seleccionar la ficha de trabajo
**Prioridad:** M · **Estado:** ✅

Un selector permite elegir la ficha; todo el tablero se recalcula para ella.

- **Criterio de aceptación:** al cambiar de ficha, KPIs, gráficas y tabla reflejan solo
  los aprendices de esa ficha.
- **Realización:** `GET /api/dashboard/fichasActivas` y `GET /api/dashboard/stats?id_ficha={id}`.

### RF-08 · Presentar los indicadores clave
**Prioridad:** M · **Estado:** ✅

Total de aprendices activos, avance global (%), aprendices en riesgo y destacados.

- **Criterio de aceptación:** el avance global es
  `aprobados ÷ (aprobados + por evaluar) × 100`, redondeado a un decimal.
- **Depende de:** RN-07.

### RF-09 · Graficar la distribución del rendimiento
**Prioridad:** S · **Estado:** ✅

Histograma con las cuatro categorías: Crítico, Rezagado, Al Día, Adelantado.

- **Criterio de aceptación:** la suma de las cuatro categorías es igual al total de
  aprendices activos de la ficha.
- **Depende de:** RN-09.

### RF-10 · Graficar el cumplimiento por fases
**Prioridad:** S · **Estado:** ✅

- **Criterio de aceptación:** solo aparecen las fases que tienen al menos un resultado
  de aprendizaje vinculado.

### RF-11 · Destacar las competencias con menor aprobación
**Prioridad:** S · **Estado:** ✅

- **Criterio de aceptación:** se listan las 5 competencias de menor porcentaje de
  aprobación, en orden ascendente.

### RF-12 · Señalar los aprendices que requieren intervención
**Prioridad:** M · **Estado:** ✅

Panel «Foco de Atención» con los aprendices en categoría Crítico o Rezagado, ordenados
del más urgente al menos urgente.

- **Criterio de aceptación:** **todo** aprendiz contado en el KPI «En Riesgo» aparece en
  la lista, y el de menor puntaje figura en primer lugar.
- **Historial:** el defecto **F-01** hacía que los aprendices Críticos se contaran en el
  KPI pero se omitieran de la lista. Corregido en `5d27772` y verificado.
- **Depende de:** RN-08, RN-09.

### RF-13 · Listar el directorio de aprendices con su avance
**Prioridad:** M · **Estado:** ✅

Documento, nombre, estado, aprobados, por evaluar, porcentaje y barra de progreso.

- **Criterio de aceptación:** cada fila enlaza al perfil del aprendiz; el porcentaje
  coincide con el del perfil individual.

### RF-14 · Filtrar el directorio
**Prioridad:** M · **Estado:** 🟡

El enunciado exige filtrar por **aprendiz, estado, documento, competencia y resultado
de aprendizaje**.

| Filtro | Backend | Interfaz |
|---|---|---|
| Nombre / apellido | ✅ | ✅ |
| Estado | ✅ | ✅ |
| Documento | ✅ | ✅ |
| Competencia | ✅ | ⛔ falta el `<select>` en la vista |
| Resultado de aprendizaje | ⛔ | ⛔ |

- **Criterio de aceptación:** los cinco filtros son combinables y el conteo de registros
  se actualiza en cada cambio.
- **Brecha conocida:** defecto **F-08**. `dashboard.js` ya lee un
  `#filter-competencia` que ninguna vista declara.

---

## 11.4 Módulo: Aprendices

### RF-15 · Listar todos los aprendices
**Prioridad:** S · **Estado:** ✅ — `GET /aprendiz`, `GET /api/aprendiz/list`

### RF-16 · Consultar el perfil individual
**Prioridad:** M · **Estado:** ✅

Datos personales, ficha, programa e historial completo de juicios por competencia y
resultado de aprendizaje, con el instructor que evaluó cada uno.

- **Criterio de aceptación:** cada juicio se muestra con distintivo visual según su
  valor (aprobado / no aprobado / por evaluar) y con el nombre del funcionario cuando
  existe.
- **Realización:** `GET /aprendiz/{id}`, `GET /api/aprendiz/{id}/stats`.

### RF-17 · Detallar los resultados «en deuda»
**Prioridad:** C · **Estado:** ✅

Resultados que el aprendiz tiene por evaluar y que ya fueron aprobados a algún
compañero de su misma ficha.

- **Criterio de aceptación:** cada resultado en deuda indica su competencia y, cuando se
  puede determinar, el instructor que lo aprobó a los demás.
- **Realización:** `GET /api/dashboard/deudasAprendiz?id_aprendiz={id}`.
- **Depende de:** RN-08.

---

## 11.5 Módulo: Programas y fichas

### RF-18 · Listar los programas con sus estadísticas
**Prioridad:** S · **Estado:** ✅ — fichas, aprendices, activos y retirados por programa.

### RF-19 · Consultar el detalle de un programa
**Prioridad:** S · **Estado:** ✅ — sus fichas con avance calculado.

### RF-20 · Consultar y eliminar una ficha
**Prioridad:** C · **Estado:** 🟡

- **Criterio de aceptación:** eliminar una ficha borra en cascada sus aprendices y
  calificaciones, dentro de una transacción, previa confirmación del usuario y **solo
  si quien la ejecuta tiene permiso**.
- **Brecha conocida:** hoy la operación es anónima, no transaccional, y responde
  «eliminada correctamente» aunque la ficha no exista. Ver **V-02** y RNF-06.

---

## 11.6 Módulo: Proyecto formativo

### RF-21 · Crear un proyecto formativo
**Prioridad:** M · **Estado:** ✅

- **Criterio de aceptación:** al crearlo se generan automáticamente las cuatro fases
  SENA (Análisis, Planeación, Ejecución, Evaluación) y se rechaza el alta si el programa
  ya tiene proyecto o el código está repetido.
- **Depende de:** RN-11, RN-12.

### RF-22 · Extraer la estructura del proyecto desde su PDF
**Prioridad:** M · **Estado:** ✅

Del PDF oficial del proyecto formativo se deben identificar fases, actividades y los
resultados de aprendizaje vinculados a cada actividad.

- **Criterio de aceptación:** con el PDF del proyecto ADSO 2480542 se obtienen 4 fases,
  6 actividades y 75 vínculos, quedando **0 resultados de aprendizaje sin vincular**.
- **Realización:** `POST /api/proyecto/upload-pdf` → `ProjectPdfParserService::parse()`.
- **Depende de:** RN-13, RN-14.
- **Brecha conocida:** el informe de resultados acumula códigos que no son resultados de
  aprendizaje y los reporta como «no encontrados». Ver **F-06**.

### RF-23 · Crear fases manualmente
**Prioridad:** C · **Estado:** 🟡 — el endpoint no valida nombre vacío ni proyecto inexistente.

### RF-24 · Crear actividades manualmente
**Prioridad:** C · **Estado:** 🟡 — misma carencia de validación que RF-23.

### RF-25 · Asignar resultados de aprendizaje a las actividades
**Prioridad:** M · **Estado:** ✅

Es el requisito 6 del enunciado: relacionar competencias y resultados con las fases y
actividades del proyecto.

- **Criterio de aceptación:** solo se ofrecen resultados del programa del proyecto que
  aún no estén asignados; asignar dos veces el mismo par no crea duplicados.
- **Realización:** `GET /proyecto/asignar`, `POST /api/proyecto/asignar`.
- **Depende de:** RN-14, RN-15.

### RF-26 · Vincular el proyecto a las fichas de su programa
**Prioridad:** S · **Estado:** ✅ — `Ficha::linkToProjectByProgram()`, solo sobre fichas sin proyecto.

---

## 11.7 Módulo: Análisis por fases

### RF-27 · Mostrar el cumplimiento de cada fase por ficha
**Prioridad:** M · **Estado:** ✅

Es el requisito 7 del enunciado.

- **Criterio de aceptación:** cada tarjeta de fase muestra porcentaje de cumplimiento,
  aprobados, pendientes y total de resultados de la fase.
- **Realización:** `GET /dashboard/fases`, `GET /api/dashboard/fases-stats`.

### RF-28 · Detallar una fase aprendiz por aprendiz
**Prioridad:** S · **Estado:** ✅ — `GET /api/dashboard/fase-detalle?id_fase={f}&id_ficha={h}`.

---

## 11.8 Módulo: Análisis de deserción

> Alcance propio del SGPD; no exigido por el enunciado.

### RF-29 · Registrar la novedad de retiro de un aprendiz
**Prioridad:** M · **Estado:** ⛔

Cuando un aprendiz pasa a un estado de retiro, cancelación o traslado, debe quedar una
novedad con su motivo, la fase en que ocurrió y el funcionario que la reporta.

- **Criterio de aceptación:** tras importar un reporte con 6 retiros y 1 traslado, la
  tabla `novedad_retiro` contiene 7 registros.
- **Brecha conocida:** **ninguna línea del sistema escribe en `novedad_retiro`**. Los
  tres requisitos siguientes leen de esa tabla, por lo que hoy el módulo completo
  muestra pantallas vacías. Ver **F-02**.

### RF-30 · Graficar los motivos de retiro
**Prioridad:** S · **Estado:** ⛔ — depende de RF-29.

### RF-31 · Graficar los retiros por instructor
**Prioridad:** C · **Estado:** ⛔ — depende de RF-29.

### RF-32 · Presentar la trazabilidad de los retiros
**Prioridad:** S · **Estado:** ⛔ — depende de RF-29.

### RF-33 · Estimar el riesgo de deserción con IA
**Prioridad:** C · **Estado:** 🟡

- **Brecha conocida:** el endpoint no envía datos reales al modelo y devuelve un texto
  fijo inventado cuando la llamada falla, sin distinguirlo de una respuesta real.

---

## 11.9 Módulo: SENA-IA

### RF-34 · Consultar los datos en lenguaje natural
**Prioridad:** S · **Estado:** ✅

- **Criterio de aceptación:** la respuesta se construye únicamente sobre datos leídos de
  la base; si Ollama no está disponible, la interfaz lo indica sin bloquear el resto del
  sistema.
- **Realización:** `POST /api/chat/send` → `DataContextService` + `OllamaService`.
- **Depende de:** RNF-11.

### RF-35 · Transmitir la respuesta en tiempo real
**Prioridad:** C · **Estado:** ✅ — Server-Sent Events, token a token.

### RF-36 · Generar gráficas dentro de la conversación
**Prioridad:** C · **Estado:** ✅ — acciones `[CHART:…]` renderizadas con Chart.js.

### RF-37 · Exportar reportes CSV desde la conversación
**Prioridad:** C · **Estado:** ✅ — reportes de riesgo, pendientes y avance general.

### RF-38 · Leer la respuesta en voz alta
**Prioridad:** C · **Estado:** ✅

- **Criterio de aceptación:** si el microservicio de voz no está encendido, el endpoint
  responde HTTP 200 con `tts_available: false` y el chat sigue funcionando en texto.
- **Realización:** `POST /api/chat/synthesize` → `TTSService`.
- **Historial:** el defecto **F-03** dejaba la funcionalidad inalcanzable (ruta y método
  desalineados). Corregido en `5d27772`.

### RF-39 · Dictar la consulta por voz
**Prioridad:** C · **Estado:** ✅ — `public/js/voice.js`, Web Speech API en `es-CO`.
Degrada a entrada de texto en navegadores sin soporte.

---

## 11.10 Requisitos diferidos

| Código | Requisito | Motivo |
|--------|-----------|--------|
| RF-40 | Autenticación e inicio de sesión | `W` en v2.0; **obligatorio** antes de cualquier despliegue real (RNF-06) |
| RF-41 | Perfiles y permisos por rol | Depende de RF-40 |
| RF-42 | Registro de auditoría de acciones | Depende de RF-40 |
| RF-43 | Conexión directa con Sofía Plus | Fuera de alcance: no hay API pública |
| RF-44 | Edición manual de juicios evaluativos | Decisión de diseño: la fuente de verdad es Sofía Plus |

---

## 11.11 Resumen de cobertura

| Estado | Requisitos | % |
|--------|-----------:|--:|
| ✅ Implementado | 29 | 74,4 % |
| 🟡 Parcial | 6 | 15,4 % |
| ⛔ No implementado | 4 | 10,3 % |
| **Total (RF-01 … RF-39)** | **39** | **100 %** |

Los cuatro no implementados son el módulo de deserción completo (RF-29 a RF-32) y todos
dependen de un mismo faltante: nada escribe en `novedad_retiro`.

Los seis parciales se concentran en dos causas: falta de validación en los endpoints de
escritura (RF-20, RF-23, RF-24) y funcionalidad construida en el backend pero no
expuesta en la interfaz (RF-06, RF-14, RF-33).

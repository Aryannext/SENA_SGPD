# 15 — Matriz de Trazabilidad

## 15.1 Para qué sirve

La trazabilidad responde tres preguntas que, sin ella, solo se contestan leyendo todo el
código:

1. **Hacia atrás:** ¿de dónde salió este requisito? ¿Alguien lo pidió o nos lo inventamos?
2. **Hacia adelante:** ¿dónde está construido y cómo se comprueba que funciona?
3. **De impacto:** si cambio este archivo, ¿qué requisitos y qué pruebas debo revisar?

---

## 15.2 Del enunciado a los requisitos

Punto de partida: *«Diseño lógico de un sistema de gestión de juicios evaluativos»*,
variante avanzada (b).

| Enunciado (variante b) | RF | HU | Estado |
|---|---|---|---|
| b1 · Base de datos, motor libre | RF-04 | — | ✅ |
| b2 · Cargue masivo con estructura de Sofía Plus | RF-01, RF-02, RF-03 | HU-01 | ✅ |
| b3 · Formulario de importación de juicios | RF-01, RF-06 | HU-01, HU-02 | ✅ |
| b4 · Avance por aprendiz con gráfica por competencia **y por resultado** | RF-16 | HU-07 | 🟡 falta el seguimiento gráfico a nivel de resultado |
| b5a · Total de aprendices, aprobados y por evaluar | RF-08 | HU-04 | ✅ |
| b5b · Filtro por aprendiz, estado, documento, **competencia y resultado** | RF-13, RF-14 | HU-06 | 🟡 faltan dos de los cinco filtros |
| b5c · Pendientes de cada aprendiz agrupados por estado | RF-08 | HU-04 | ✅ |
| b5d · % de avance por aprendiz según cada competencia | RF-16 | HU-07 | ✅ |
| b5e · % de aprobación por competencia | RF-11 | HU-09 | ✅ |
| b6 · Relacionar competencias y resultados con fases y actividades | RF-21, RF-22, RF-25 | HU-13, HU-14 | ✅ |
| b7 · Cumplimiento por fase con aprobados y pendientes | RF-10, RF-27, RF-28 | HU-10, HU-11 | ✅ |

**Cobertura del enunciado:** 9 de 11 puntos completos, 2 parciales. Ambos parciales
tienen la misma causa: falta exponer en la interfaz un nivel de detalle —el resultado de
aprendizaje— que el backend ya sabe manejar.

### Las diez preguntas de la coordinación

El enunciado plantea diez preguntas que el sistema debe poder responder. Verificadas una
por una contra la base cargada:

| # | Pregunta | Dónde se responde | Estado |
|---|---|---|---|
| 1 | ¿Cuántos aprendices tiene la ficha? | KPI del tablero · RF-08 | ✅ |
| 2 | ¿Cuántos están en formación, retirados o trasladados? | Aprendices por estado · RF-08 | ✅ |
| 3 | ¿Cuántos resultados tiene cada aprendiz? | Perfil del aprendiz · RF-16 | ✅ |
| 4 | ¿Cuántos resultados han sido aprobados? | KPI del tablero · RF-08 | ✅ |
| 5 | ¿Cuántos están pendientes por evaluar? | KPI del tablero · RF-08 | ✅ |
| 6 | ¿Cuál es el avance porcentual de cada aprendiz? | Directorio · RF-13 · RN-14 | ✅ |
| 7 | ¿Qué competencias tienen mayor aprobación? | Avance por competencia · RF-11 | ✅ |
| 8 | ¿Qué funcionario registró determinado juicio? | Perfil del aprendiz · RF-16 | 🟡 |
| 9 | ¿En qué fecha y hora se registró cada juicio? | Perfil del aprendiz · RF-16 | 🟡 |
| 10 | ¿Qué aprendices tienen juicios pendientes? | Foco de atención · RF-12 | ✅ |

Las preguntas 8 y 9 quedan limitadas **por el dato de origen, no por el diseño**: en el
reporte de Sofía Plus los juicios `POR EVALUAR` llegan sin funcionario ni fecha. De
2.401 registros, 1.078 responden la pregunta 8 y 1.152 la pregunta 9 — es decir, todos
los que efectivamente fueron evaluados. Ver RN-13.

---

## 15.3 De los requisitos al código y a las pruebas

| RF | HU | RN | Dónde está construido | Prueba |
|---|---|---|---|---|
| RF-01 | HU-01 | — | `ImportController::upload()` | CP-01, CP-02 |
| RF-02 | HU-01 | RN-23, RN-24, RN-25 | `ExcelImportService::import()` | CP-03 |
| RF-03 | HU-01 | — | `ExcelImportService::import()` (fila 13) | CP-04 |
| RF-04 | HU-01 | RN-01…RN-12 | `ExcelImportService` + modelos | CP-05 |
| RF-05 | HU-03 | RN-02, RN-11 | `insertIgnore()` de cada modelo | CP-06 |
| RF-06 | HU-02 | — | `ImportController` + `import.js` | CP-07 |
| RF-07 | HU-04 | — | `DashboardController::fichasActivas/stats` | CP-08 |
| RF-08 | HU-04 | RN-14, RN-15 | `DashboardController::stats()` | CP-09 |
| RF-09 | HU-04 | RN-17 | `DashboardController::stats()` | CP-10 |
| RF-10 | HU-10 | — | `DashboardController::stats()` | CP-11 |
| RF-11 | HU-09 | — | `DashboardController::stats()` | CP-12 |
| RF-12 | HU-05 | RN-16, RN-17 | `DashboardController::stats()` | **CP-13** |
| RF-13 | HU-06 | RN-14 | `DashboardController::stats/filtrar` | CP-14 |
| RF-14 | HU-06 | — | `DashboardController::filtrar()` | CP-15 |
| RF-15 | — | — | `AprendizController::index/list` | CP-16 |
| RF-16 | HU-07 | RN-13 | `Calificacion::detalleAprendiz()` | CP-17 |
| RF-17 | HU-08 | RN-16 | `DashboardController::deudasAprendiz()` | CP-18 |
| RF-18 | HU-12 | — | `Programa::findAllWithStats()` | CP-19 |
| RF-19 | HU-12 | — | `ProgramaController::detalle()` | CP-20 |
| RF-20 | — | — | `ProgramaController::deleteFicha()` | CP-21 |
| RF-21 | HU-13 | RN-09, RN-18 | `ProyectoController::crearProyecto()` | CP-22 |
| RF-22 | HU-13 | RN-19, RN-20 | `ProjectPdfParserService::parse()` | CP-23 |
| RF-23 | — | — | `ProyectoController::crearFase()` | CP-24 |
| RF-24 | — | — | `ProyectoController::crearActividad()` | CP-24 |
| RF-25 | HU-14 | RN-20, RN-21 | `Actividad::asignarResultado()` | CP-25 |
| RF-26 | HU-13 | RN-22 | `Ficha::linkToProjectByProgram()` | CP-26 |
| RF-27 | HU-10 | — | `Fase::cumplimientoPorFase()` | CP-27 |
| RF-28 | HU-11 | — | `DashboardController::faseDetalle()` | CP-28 |
| RF-29 | HU-15 | — | ⛔ **sin construir** | CP-29 |
| RF-30 | HU-15 | — | `DesercionController::stats()` (sin datos) | CP-30 |
| RF-31 | HU-15 | — | `DesercionController::stats()` (sin datos) | CP-30 |
| RF-32 | HU-15 | — | `DesercionController::stats()` (sin datos) | CP-30 |
| RF-33 | HU-15 | — | `DesercionController::predecir()` | CP-31 |
| RF-34 | HU-16 | — | `AIController::send()` + `DataContextService` | CP-32 |
| RF-35 | HU-16 | — | `OllamaService::chatStream()` | CP-33 |
| RF-36 | HU-16 | — | `DataContextService::generate*Graph()` | CP-34 |
| RF-37 | HU-18 | — | `DataContextService::formatAsCsvAction()` | CP-35 |
| RF-38 | HU-17 | — | `AIController::synthesize()` + `TTSService` | **CP-36** |
| RF-39 | HU-17 | — | `public/js/voice.js` | CP-37 |
| RF-40 | HU-19 | — | ⛔ **sin construir** | CP-38 |

Los casos en **negrita** cubren defectos ya corregidos y deben quedar como pruebas de
regresión permanentes.

---

## 15.4 De las reglas de negocio a su punto de aplicación

| RN | Dónde se aplica | Tipo | Prueba |
|---|---|---|---|
| RN-01 | `aprendiz.ti_documento NOT NULL` | 🛢️ | CP-39 |
| RN-02 | `aprendiz.nu_documento UNIQUE` | 🛢️ | CP-40 |
| RN-03 | Consecuencia de RN-02 | 🛢️ | CP-41 |
| RN-04 | ⚠️ **sin aplicar** | — | CP-42 |
| RN-05 | `resultado_aprendizaje.id_competencia` FK | 🛢️ | CP-43 |
| RN-06 | Relación 1:N | 🛢️ | CP-43 |
| RN-07 | `cod_competencia` / `cod_resultado` UNIQUE | 🛢️ | CP-44 |
| RN-08 | `programa_competencia` (PK compuesta) | 🛢️ | CP-45 |
| RN-09 | `ProyectoController::crearProyecto()` | ⚙️ | CP-22 |
| RN-10 | FK de `calificacion` | 🛢️ | CP-46 |
| RN-11 | `UNIQUE (id_aprendiz, id_resultado)` | 🛢️ | CP-06 |
| RN-12 | `ENUM` + `normalizeJuicio()` | 🛢️⚙️ | CP-47 |
| RN-13 | Columnas nulas (desviación) | 🛢️ | CP-48 |
| RN-14 | Cálculo de avance | ⚙️ | CP-09 |
| RN-15 | `WHERE estado LIKE '%FORMACION%'` | ⚙️ | CP-49 |
| RN-16 | Subconsulta `en_deuda` | ⚙️ | CP-18 |
| RN-17 | Categorización relativa | ⚙️ | CP-10, CP-13 |
| RN-18 | Cuatro fases por defecto | ⚙️ | CP-22 |
| RN-19 | Borrado previo en `uploadPdf()` | ⚙️ | CP-50 |
| RN-20 | `findByCodigoAndPrograma()` | ⚙️ | CP-23 |
| RN-21 | PK compuesta en `actividad_resultado` | 🛢️ | CP-25 |
| RN-22 | `id_proyecto IS NULL` | ⚙️ | CP-26 |
| RN-23 | `normalizeEstadoFicha()` | 🛢️⚙️ | CP-03 |
| RN-24 | `normalizeModalidad()` | 🛢️⚙️ | CP-03 |
| RN-25 | `nu_ficha` / `codigo_programa` UNIQUE | 🛢️ | CP-06 |

---

## 15.5 Impacto por archivo

Qué requisitos y qué pruebas revisar al tocar cada archivo. Ordenado por riesgo.

| Archivo | Requisitos afectados | Pruebas a ejecutar |
|---|---|---|
| `app/Controllers/DashboardController.php` | RF-07 … RF-14, RF-17, RF-28 | CP-08 … CP-15, CP-18, CP-28 |
| `app/Services/ExcelImportService.php` | RF-01 … RF-05 · RN-01…RN-12, RN-23, RN-24 | CP-01 … CP-06, CP-47 |
| `app/Services/ProjectPdfParserService.php` | RF-22 · RN-19, RN-20 | CP-23, CP-50 |
| `app/Services/DataContextService.php` | RF-34, RF-36, RF-37 · RNF-11, RNF-12 | CP-32, CP-34, CP-35 |
| `docs/SGPD_SENA.sql` | Todos · RN-01…RN-12, RN-21, RN-25 | Suite completa |
| `index.php` | Todos los endpoints | CP-51 (contrato de rutas) |
| `public/js/dashboard.js` | RF-08 … RF-14, RF-17 | CP-09 … CP-15, CP-18, CP-52 |
| `public/js/app.js` | Transversal · RNF-08 | CP-52 (escapado) |
| `views/layouts/main.php` | Transversal · RNF-08, RNF-10 | CP-52, CP-53 |
| `config/ollama.php` | RF-34 · **RNF-11** | CP-32 + revisión legal si cambia `base_url` |

---

## 15.6 Requisitos sin prueba y pruebas sin requisito

**Requisitos sin cobertura automatizada:** todos. El proyecto no tiene ninguna prueba
automatizada (RNF-24). Los casos CP-01 a CP-53 de
[16_PLAN_PRUEBAS.md](16_PLAN_PRUEBAS.md) están especificados y una parte se ejecutó
manualmente durante la auditoría del 9 de septiembre de 2026, pero **ninguno está
automatizado todavía**.

**Funcionalidad construida sin requisito previo.** Se documenta ahora para que deje de
ser conocimiento tácito:

| Funcionalidad | Dónde | Requisito asignado |
|---|---|---|
| Dictado por voz (Web Speech API, `es-CO`) | `public/js/voice.js` | RF-39 |
| Exportación CSV desde el chat | `DataContextService` | RF-37 |
| Gráficas generadas por el modelo | `chat.js` + `DataContextService` | RF-36 |
| Modo widget (`?widget=true`) | `views/layouts/main.php` | *sin asignar* |
| Detección de ficha por número en el chat | `DataContextService` | RF-34 |

El **modo widget** es el único que sigue sin requisito: renderiza cualquier vista sin
barra lateral ni encabezado para incrustarla en el iframe del chat flotante. Conviene
enunciarlo, porque es un camino de acceso alternativo a todas las vistas y, cuando se
implemente la autenticación (RF-40), tendrá que protegerse igual que las demás.

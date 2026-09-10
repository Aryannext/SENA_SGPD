# 14 — Historias de Usuario y Criterios de Aceptación

## 14.1 Propósito y formato

Mientras los requisitos funcionales dicen *qué hace el sistema*, las historias dicen
*qué necesita resolver una persona*. Cada historia se escribe desde el rol que la
necesita y trae criterios de aceptación verificables en formato **Dado / Cuando /
Entonces**, de modo que se puedan convertir directamente en casos de prueba
([16_PLAN_PRUEBAS.md](16_PLAN_PRUEBAS.md)).

### Roles

| Rol | Quién es | Qué necesita del sistema |
|---|---|---|
| **Instructor** | Conduce la formación de una o varias fichas | Saber cómo va su grupo y a quién atender |
| **Coordinador académico** | Supervisa varios programas y fichas | Detectar patrones y comparar grupos |
| **Gestor de proyecto** | Estructura el proyecto formativo | Relacionar resultados con fases y actividades |
| **Administrador** | Responsable técnico | Controlar accesos *(rol previsto, aún sin implementar)* |

### Estado

✅ Implementado y verificado · 🟡 Parcial · ⛔ No implementado

---

## 14.2 Épica 1 — Traer los datos de Sofía Plus

### HU-01 · Importar el reporte de juicios evaluativos
**Como** instructor, **quiero** cargar el archivo Excel que descargo de Sofía Plus,
**para** no tener que analizarlo a mano cada vez.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-01, RF-02, RF-03, RF-04

**Criterios de aceptación**

1. **Dado** que tengo el reporte oficial en `.xls` o `.xlsx`,
   **cuando** lo arrastro al área de carga,
   **entonces** el sistema lo procesa y crea el programa, la ficha, los aprendices, las
   competencias, los resultados de aprendizaje, los funcionarios y los juicios.
2. **Dado** que intento subir un archivo que no es Excel,
   **cuando** confirmo la carga,
   **entonces** el sistema lo rechaza indicando que solo admite `.xls` y `.xlsx`, y no
   modifica ningún dato.
3. **Dado** que el archivo tiene las columnas en orden distinto al habitual,
   **cuando** lo importo,
   **entonces** el resultado es el mismo, porque las columnas se identifican por su
   encabezado y no por su posición.
4. **Dado** un archivo con fechas de juicio,
   **cuando** se importa,
   **entonces** las fechas quedan almacenadas como fecha y hora reales, no como el
   número de serie de Excel.

---

### HU-02 · Saber exactamente qué ocurrió al importar
**Como** instructor, **quiero** ver un resumen de la carga,
**para** confiar en que los datos entraron completos y detectar si algo falló.

**Prioridad:** S · **Estado:** 🟡 · **Requisitos:** RF-06

**Criterios de aceptación**

1. **Dado** que la importación terminó,
   **cuando** se muestra el resumen,
   **entonces** veo filas procesadas, registros por entidad y cantidad de errores.
2. **Dado** que alguna fila falló,
   **cuando** reviso el resumen,
   **entonces** puedo ver el número de fila y el motivo del fallo.
3. **Dado** que vuelvo a subir el mismo archivo,
   **cuando** termina,
   **entonces** el resumen distingue cuántos registros son **nuevos** y cuántos fueron
   **actualizados**.

> ⚠️ El criterio 3 **no se cumple hoy**: el resumen informa registros procesados, así
> que reimportar el mismo archivo vuelve a anunciar «31 aprendices» aunque no haya
> creado ninguno. Defecto **F-11**.

---

### HU-03 · Volver a cargar sin ensuciar los datos
**Como** instructor, **quiero** actualizar la información subiendo un reporte más
reciente, **para** mantener el tablero al día sin duplicar aprendices ni juicios.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-05 · **Reglas:** RN-02, RN-11

**Criterios de aceptación**

1. **Dado** que ya importé un reporte,
   **cuando** importo el mismo archivo otra vez,
   **entonces** los conteos de todas las tablas quedan idénticos.
2. **Dado** un reporte con juicios actualizados,
   **cuando** lo importo,
   **entonces** los juicios existentes se actualizan al nuevo valor y se conserva un
   único registro por aprendiz y resultado.

---

## 14.3 Épica 2 — Seguir el avance del grupo

### HU-04 · Ver el estado de mi ficha de un vistazo
**Como** instructor, **quiero** abrir un tablero y entender en segundos cómo va mi
ficha, **para** no tener que construir el panorama a partir de una hoja de cálculo.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-07, RF-08, RF-09

**Criterios de aceptación**

1. **Dado** que hay fichas importadas,
   **cuando** entro al tablero,
   **entonces** se selecciona una ficha por defecto y veo total de aprendices, avance
   global, aprendices en riesgo y destacados.
2. **Dado** que cambio de ficha en el selector,
   **cuando** termina la carga,
   **entonces** todos los indicadores, gráficas y tablas corresponden únicamente a la
   ficha elegida.
3. **Dado** que no hay ninguna ficha importada,
   **cuando** entro al tablero,
   **entonces** el sistema me indica que debo importar datos, en lugar de mostrar cifras
   en cero sin explicación.

---

### HU-05 · Saber a quién debo atender primero
**Como** instructor, **quiero** una lista corta de los aprendices que necesitan
intervención, **para** dirigir mi tiempo a quien más lo necesita.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-12 · **Reglas:** RN-16, RN-17

**Criterios de aceptación**

1. **Dado** que hay aprendices en categoría Crítico o Rezagado,
   **cuando** miro el panel «Foco de Atención»,
   **entonces** aparecen todos ellos, ordenados del más urgente al menos urgente.
2. **Dado** que el indicador «En Riesgo» muestra un número mayor que cero,
   **cuando** miro la lista,
   **entonces** **cada** aprendiz contado en ese indicador está presente en la lista.
3. **Dado** que ningún aprendiz está en riesgo,
   **cuando** miro el panel,
   **entonces** veo un mensaje afirmativo y no una tabla vacía.

> El criterio 2 falló durante meses: los aprendices Críticos se contaban en el indicador
> pero se omitían de la lista, por un literal corrompido en la comparación. Defecto
> **F-01**, corregido en `5d27772` y verificado con un aprendiz al 13,3 % frente a un
> promedio del 53,4 %.

---

### HU-06 · Encontrar a un aprendiz concreto
**Como** instructor, **quiero** filtrar el directorio por estado, documento, nombre,
competencia y resultado de aprendizaje, **para** llegar rápido a un caso puntual.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-13, RF-14

**Criterios de aceptación**

1. **Dado** que escribo parte de un nombre o de un documento,
   **cuando** aplico el filtro,
   **entonces** la tabla muestra solo las coincidencias y el contador de registros se
   actualiza.
2. **Dado** que elijo un estado,
   **cuando** se aplica,
   **entonces** solo se listan aprendices en ese estado.
3. **Dado** que elijo una competencia,
   **cuando** se aplica,
   **entonces** solo se listan aprendices con juicios en esa competencia.
4. **Dado** que elijo un resultado de aprendizaje,
   **cuando** se aplica,
   **entonces** solo se listan aprendices con juicios en ese resultado.
5. **Dado** que combino varios filtros,
   **cuando** se aplican,
   **entonces** actúan de forma acumulativa.

> Los criterios 3 y 4 estuvieron incumplidos: el filtro por competencia existía en el
> backend y en el JavaScript, pero el control no estaba en ninguna vista, y el de
> resultado de aprendizaje no existía. Ambos los exige el enunciado. Defecto **F-08**,
> corregido: los cinco filtros están ahora en la barra, el de resultado depende del de
> competencia, y se añadió un botón para limpiarlos.

---

### HU-07 · Revisar el historial completo de un aprendiz
**Como** instructor, **quiero** abrir el perfil de un aprendiz y ver todos sus juicios,
**para** sustentar una conversación de seguimiento con datos.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-16

**Criterios de aceptación**

1. **Dado** que hago clic en un aprendiz del directorio,
   **cuando** se abre su perfil,
   **entonces** veo sus datos, su ficha, su programa y la lista completa de juicios
   agrupada por competencia y resultado de aprendizaje.
2. **Dado** que un juicio fue registrado por un funcionario,
   **cuando** miro la fila,
   **entonces** aparece el nombre de quien lo registró.
3. **Dado** un juicio sin funcionario registrado en el origen,
   **cuando** miro la fila,
   **entonces** el campo aparece vacío o marcado como no disponible, sin romper la
   pantalla.

---

### HU-08 · Saber qué le falta a un aprendiz respecto de su grupo
**Como** instructor, **quiero** ver qué resultados ya aprobó el resto de la ficha y este
aprendiz no, **para** distinguir el rezago individual del avance normal del grupo.

**Prioridad:** S · **Estado:** ✅ · **Requisitos:** RF-17 · **Reglas:** RN-16

**Criterios de aceptación**

1. **Dado** que un aprendiz tiene resultados en deuda,
   **cuando** abro el detalle,
   **entonces** veo cada resultado con su competencia y, cuando se puede determinar, el
   instructor que lo aprobó a los demás.
2. **Dado** que un aprendiz va al día,
   **cuando** miro su fila en el foco de atención,
   **entonces** aparece marcado como «Al día» sin enlace de deudas.

---

## 14.4 Épica 3 — Analizar competencias y fases

### HU-09 · Detectar qué competencias están frenando al grupo
**Como** coordinador académico, **quiero** ver las competencias con menor porcentaje de
aprobación, **para** reforzar la formación donde hace falta.

**Prioridad:** S · **Estado:** ✅ · **Requisitos:** RF-11

**Criterios de aceptación**

1. **Dado** que hay juicios registrados,
   **cuando** miro la gráfica de competencias críticas,
   **entonces** veo las cinco de menor aprobación, en orden ascendente y con su
   porcentaje.

---

### HU-10 · Medir el cumplimiento de cada fase del proyecto
**Como** coordinador académico, **quiero** ver el avance por fase del proyecto
formativo, **para** saber si la ficha va acorde con la planeación.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-10, RF-27

**Criterios de aceptación**

1. **Dado** que la ficha tiene un proyecto formativo con resultados vinculados,
   **cuando** abro el análisis por fases,
   **entonces** veo una tarjeta por fase con porcentaje de cumplimiento, aprobados,
   pendientes y total de resultados.
2. **Dado** que una fase no tiene resultados vinculados,
   **cuando** se listan las fases,
   **entonces** esa fase no aparece, porque su porcentaje no sería interpretable.

---

### HU-11 · Ver el detalle de una fase aprendiz por aprendiz
**Como** instructor, **quiero** abrir una fase y ver cómo va cada aprendiz en ella,
**para** planear refuerzos concretos.

**Prioridad:** S · **Estado:** ✅ · **Requisitos:** RF-28

**Criterios de aceptación**

1. **Dado** que hago clic en una tarjeta de fase,
   **cuando** se abre el detalle,
   **entonces** veo la lista de aprendices de la ficha con sus aprobados, pendientes y
   porcentaje **dentro de esa fase**.

---

### HU-12 · Comparar programas y fichas
**Como** coordinador académico, **quiero** ver todos los programas con sus fichas y
conteos, **para** ubicar rápidamente dónde mirar.

**Prioridad:** S · **Estado:** ✅ · **Requisitos:** RF-18, RF-19

**Criterios de aceptación**

1. **Dado** que hay varios programas importados,
   **cuando** abro el listado,
   **entonces** cada programa muestra sus fichas, total de aprendices, activos y
   retirados.

---

## 14.5 Épica 4 — Estructurar el proyecto formativo

### HU-13 · Cargar el proyecto formativo desde su PDF
**Como** gestor de proyecto, **quiero** subir el PDF oficial y que el sistema extraiga
fases, actividades y resultados, **para** no transcribir cientos de vínculos a mano.

**Prioridad:** M · **Estado:** 🟡 · **Requisitos:** RF-22 · **Reglas:** RN-19, RN-20

**Criterios de aceptación**

1. **Dado** el PDF oficial del proyecto formativo,
   **cuando** lo subo,
   **entonces** el sistema identifica sus fases, sus actividades y los resultados de
   aprendizaje vinculados a cada una.
2. **Dado** que el PDF corresponde a un programa que no está en el sistema,
   **cuando** lo subo,
   **entonces** se rechaza indicando que primero debe importarse el Excel de Sofía Plus.
3. **Dado** que el PDF contiene códigos de otros programas,
   **cuando** se procesa,
   **entonces** esos códigos no se vinculan, para no contaminar la estructura.
4. **Dado** que ya había una estructura cargada,
   **cuando** subo un PDF nuevo,
   **entonces** el sistema **me advierte** de que se reemplazará antes de confirmar.

> ⚠️ El criterio 4 **no se cumple**: la sustitución ocurre sin aviso y arrastra las
> asignaciones manuales previas. Regla RN-19.
>
> El informe de resultados, además, reporta como «no encontrados» códigos de seis
> dígitos que no son resultados de aprendizaje, produciendo un mensaje alarmante después
> de una carga exitosa. Defecto **F-06**.

---

### HU-14 · Asignar a mano los resultados que quedaron sueltos
**Como** gestor de proyecto, **quiero** ver qué resultados no quedaron vinculados a
ninguna actividad y asignarlos yo, **para** completar la estructura del proyecto.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-25 · **Reglas:** RN-20, RN-21

**Criterios de aceptación**

1. **Dado** que hay resultados sin vincular en el programa del proyecto,
   **cuando** abro la pantalla de asignación,
   **entonces** los veo agrupados por competencia.
2. **Dado** que asigno un resultado a una actividad,
   **cuando** confirmo,
   **entonces** desaparece de la lista de pendientes y queda vinculado.
3. **Dado** que intento asignar dos veces el mismo par,
   **cuando** confirmo,
   **entonces** no se crea un vínculo duplicado.

---

## 14.6 Épica 5 — Entender la deserción

### HU-15 · Entender por qué se retiran los aprendices
**Como** coordinador académico, **quiero** ver los motivos de retiro, en qué fase
ocurren y qué instructor los reporta, **para** intervenir sobre las causas.

**Prioridad:** M · **Estado:** ✅ · **Requisitos:** RF-29 a RF-32

**Criterios de aceptación**

1. **Dado** que se importó un reporte con aprendices retirados,
   **cuando** abro el análisis de deserción,
   **entonces** veo la distribución de motivos de retiro.
2. **Dado** que hay retiros registrados,
   **cuando** miro la tabla de trazabilidad,
   **entonces** veo fecha, aprendiz, fase de abandono, motivo e instructor responsable.
3. **Dado** que **no** hay retiros,
   **cuando** abro el módulo,
   **entonces** veo un mensaje claro de que no hay datos, y **ninguna gráfica dibuja
   valores inventados**.

> Ningún criterio se cumplía: el módulo consultaba `novedad_retiro`, una tabla en la que
> **ninguna línea del sistema escribía**. Con 6 retiros y 1 traslado ya importados, el
> endpoint devolvía `{"motivos":[],"instructores":[],"auditoria":[]}`, y la gráfica
> dibujaba un segmento ficticio «Sin datos» que podía leerse como un dato real.
> Defecto **F-02**, corregido.
>
> Ahora la importación deriva las novedades del estado reportado. Verificado con la
> muestra: 7 novedades, con su fase de abandono e instructor resueltos. El criterio 3 se
> cumple también: sin datos se muestra un estado vacío explícito y **ninguna gráfica
> dibuja valores inventados**.

---

## 14.7 Épica 6 — Consultar con ayuda de la IA

### HU-16 · Preguntar en lenguaje natural
**Como** instructor, **quiero** preguntarle al asistente por mis datos con mis propias
palabras, **para** obtener respuestas sin construir consultas ni filtros.

**Prioridad:** C · **Estado:** ✅ · **Requisitos:** RF-34, RF-35, RF-36

**Criterios de aceptación**

1. **Dado** que Ollama está activo,
   **cuando** escribo una pregunta sobre una ficha,
   **entonces** recibo una respuesta construida sobre los datos de la base, mostrada
   token a token.
2. **Dado** que menciono un número de ficha,
   **cuando** envío la pregunta,
   **entonces** la respuesta usa los datos concretos de esa ficha.
3. **Dado** que pido comparar o graficar,
   **cuando** llega la respuesta,
   **entonces** se dibuja una gráfica junto al texto.
4. **Dado** que Ollama **no** está disponible,
   **cuando** abro el chat,
   **entonces** la interfaz me lo indica y el resto del sistema sigue funcionando.

---

### HU-17 · Escuchar la respuesta y dictar la pregunta
**Como** instructor, **quiero** dictar mi consulta y escuchar la respuesta,
**para** usar el sistema mientras hago otra cosa.

**Prioridad:** C · **Estado:** ✅ · **Requisitos:** RF-38, RF-39

**Criterios de aceptación**

1. **Dado** que el microservicio de voz está encendido,
   **cuando** pido escuchar una respuesta,
   **entonces** se reproduce el audio.
2. **Dado** que el microservicio **no** está encendido,
   **cuando** pido escuchar,
   **entonces** el sistema responde correctamente indicando que la voz no está
   disponible, sin errores ni bloqueos.
3. **Dado** un navegador sin reconocimiento de voz,
   **cuando** abro el chat,
   **entonces** puedo seguir escribiendo con normalidad.

> El criterio 1 era inalcanzable: la ruta registrada apuntaba a un método inexistente
> (HTTP 500) mientras el frontend llamaba a otra que no estaba registrada (HTTP 404).
> Defecto **F-03**, corregido en `5d27772`.

---

### HU-18 · Llevarme los datos en un archivo
**Como** coordinador académico, **quiero** exportar los listados que consulto,
**para** trabajarlos fuera del sistema o adjuntarlos a un informe.

**Prioridad:** C · **Estado:** ✅ · **Requisitos:** RF-37

**Criterios de aceptación**

1. **Dado** que pido un reporte de aprendices en riesgo, pendientes o avance general,
   **cuando** la respuesta llega,
   **entonces** aparece un botón que descarga el CSV correspondiente.
2. **Dado** que no hay datos para ese reporte,
   **cuando** se genera,
   **entonces** el archivo se descarga con los encabezados y una indicación de que no
   hay registros.

---

## 14.8 Épica 7 — Controlar el acceso

### HU-19 · Entrar con mi usuario
**Como** instructor, **quiero** iniciar sesión con mis credenciales,
**para** que solo el personal autorizado vea los datos de los aprendices.

**Prioridad:** M · **Estado:** ⛔ · **Requisitos:** RF-40, RF-41 · **RNF:** RNF-06, RNF-07

**Criterios de aceptación**

1. **Dado** que no he iniciado sesión,
   **cuando** solicito cualquier ruta que muestre datos de aprendices,
   **entonces** el sistema me redirige al inicio de sesión.
2. **Dado** que inicié sesión como instructor,
   **cuando** intento eliminar una ficha,
   **entonces** el sistema lo rechaza por falta de permisos.
3. **Dado** que una página externa intenta enviar una petición de escritura en mi
   nombre, **cuando** llega al servidor,
   **entonces** se rechaza por falta de un token válido.

> ⚠️ Ningún criterio se cumple. Ninguna de las 32 rutas valida sesión, rol ni token, y
> se verificó que `POST /api/programa/delete-ficha` responde `{"success":true}` sin
> credencial alguna. Vulnerabilidad **V-02**.
>
> **Es la historia bloqueante para cualquier despliegue con datos reales.** Mientras
> siga sin implementar, el sistema es apto para demostración académica y para ejecución
> local, no para un servidor accesible.

---

## 14.9 Resumen

| Épica | Historias | ✅ | 🟡 | ⛔ |
|---|--:|--:|--:|--:|
| 1 · Traer los datos de Sofía Plus | 3 | 2 | 1 | 0 |
| 2 · Seguir el avance del grupo | 5 | 5 | 0 | 0 |
| 3 · Analizar competencias y fases | 4 | 4 | 0 | 0 |
| 4 · Estructurar el proyecto formativo | 2 | 1 | 1 | 0 |
| 5 · Entender la deserción | 1 | 1 | 0 | 0 |
| 6 · Consultar con ayuda de la IA | 3 | 3 | 0 | 0 |
| 7 · Controlar el acceso | 1 | 0 | 0 | 1 |
| **Total** | **19** | **16** | **2** | **1** |

**HU-19, el control de acceso, es la única historia sin implementar**, y es la que
bloquea cualquier despliegue con datos reales de aprendices.

Las dos parciales incumplen un criterio cada una, ninguno exigido por el enunciado:
HU-02 no distingue registros nuevos de actualizados (F-11) y HU-13 reemplaza la
estructura del proyecto sin advertirlo antes (RN-19).

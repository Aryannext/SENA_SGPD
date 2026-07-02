# 05 — Diccionario de Datos

## 5.1 Tabla: `funcionario`
Almacena la información de los instructores y funcionarios que registran juicios evaluativos.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_funcionario` | INT | PK, AUTO_INCREMENT | Identificador único |
| `ti_documento` | ENUM('CC','TI','CE','PA','PEP') | NOT NULL | Tipo de documento de identidad |
| `nu_documento` | VARCHAR(20) | UNIQUE | Número de documento |
| `nombre` | VARCHAR(150) | — | Nombre completo del funcionario |

---

## 5.2 Tabla: `programa`
Programas de formación ofrecidos por el SENA.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_programa` | INT | PK, AUTO_INCREMENT | Identificador único |
| `codigo_programa` | VARCHAR(50) | UNIQUE | Código oficial del programa en Sofía Plus |
| `nombre_programa` | VARCHAR(255) | — | Nombre completo del programa |
| `modalidad` | ENUM('PRESENCIAL','VIRTUAL','A DISTANCIA') | — | Modalidad de formación |

---

## 5.3 Tabla: `proyecto_formativo`
Proyectos formativos asociados a los programas de formación.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_proyecto` | INT | PK, AUTO_INCREMENT | Identificador único |
| `codigo_proyecto` | VARCHAR(50) | UNIQUE | Código del proyecto |
| `nombre_proyecto` | VARCHAR(255) | — | Nombre descriptivo del proyecto |
| `ruta_pdf` | VARCHAR(255) | — | Ruta al archivo PDF del proyecto subido |
| `id_programa` | INT | FK → programa | Programa al que pertenece |

---

## 5.4 Tabla: `fase`
Fases del proyecto formativo (Análisis, Planeación, Ejecución, Evaluación).

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_fase` | INT | PK, AUTO_INCREMENT | Identificador único |
| `nombre_fase` | VARCHAR(100) | — | Nombre de la fase |
| `id_proyecto` | INT | FK → proyecto_formativo | Proyecto al que pertenece |

---

## 5.5 Tabla: `actividad`
Actividades de aprendizaje dentro de cada fase.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_actividad` | INT | PK, AUTO_INCREMENT | Identificador único |
| `cod_actividad` | VARCHAR(50) | — | Código de la actividad |
| `nombre_actividad` | VARCHAR(255) | — | Descripción de la actividad |
| `id_fase` | INT | FK → fase | Fase a la que pertenece |

---

## 5.6 Tabla: `competencia`
Competencias técnicas y transversales del programa.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_competencia` | INT | PK, AUTO_INCREMENT | Identificador único |
| `cod_competencia` | VARCHAR(50) | UNIQUE | Código oficial de la competencia |
| `nombre` | VARCHAR(255) | — | Nombre descriptivo de la competencia |

---

## 5.7 Tabla: `programa_competencia` (Pivote)
Relación muchos a muchos entre programas y competencias.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_programa` | INT | PK, FK → programa | Programa asociado |
| `id_competencia` | INT | PK, FK → competencia | Competencia asociada |

---

## 5.8 Tabla: `ficha`
Fichas de formación (grupos de aprendices).

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_ficha` | INT | PK, AUTO_INCREMENT | Identificador único |
| `nu_ficha` | VARCHAR(50) | UNIQUE | Número de ficha de Sofía Plus |
| `estado` | ENUM('EN EJECUCION','TERMINADA','CANCELADA') | — | Estado actual de la ficha |
| `fecha_inicio` | DATE | — | Fecha de inicio de la formación |
| `fecha_fin` | DATE | — | Fecha prevista de finalización |
| `id_programa` | INT | FK → programa | Programa al que pertenece |
| `id_proyecto` | INT | FK → proyecto_formativo | Proyecto formativo asignado |

---

## 5.9 Tabla: `aprendiz`
Aprendices matriculados en las fichas.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_aprendiz` | INT | PK, AUTO_INCREMENT | Identificador único |
| `ti_documento` | ENUM('CC','TI','CE','PA','PEP') | NOT NULL | Tipo de documento |
| `nu_documento` | VARCHAR(20) | UNIQUE | Número de documento |
| `nombre` | VARCHAR(100) | — | Nombres del aprendiz |
| `apellido` | VARCHAR(100) | — | Apellidos del aprendiz |
| `estado` | VARCHAR(50) | — | Estado actual (EN FORMACION, RETIRO VOLUNTARIO, TRASLADO, etc.) |
| `id_ficha` | INT | FK → ficha | Ficha en la que está matriculado |

> **Nota:** La columna `estado` fue migrada de ENUM a VARCHAR(50) para aceptar todos los estados posibles de Sofía Plus sin restricciones.

---

## 5.10 Tabla: `resultado_aprendizaje`
Resultados de aprendizaje asociados a cada competencia.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_resultado` | INT | PK, AUTO_INCREMENT | Identificador único |
| `cod_resultado` | VARCHAR(50) | UNIQUE | Código del resultado |
| `nombre_resultado` | TEXT | — | Descripción del resultado de aprendizaje |
| `id_competencia` | INT | FK → competencia | Competencia a la que pertenece |

---

## 5.11 Tabla: `calificacion`
Juicios evaluativos asignados a los aprendices por los instructores.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_calificacion` | INT | PK, AUTO_INCREMENT | Identificador único |
| `id_aprendiz` | INT | FK → aprendiz | Aprendiz evaluado |
| `id_resultado` | INT | FK → resultado_aprendizaje | Resultado evaluado |
| `id_funcionario` | INT | FK → funcionario | Instructor que registró el juicio |
| `jui_evaluativo` | ENUM('APROBADO','POR EVALUAR','NO APROBADO') | — | Resultado del juicio evaluativo |
| `fecha_registro` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Fecha y hora del registro |

> **Índice único:** `UNIQUE KEY (id_aprendiz, id_resultado)` — Cada aprendiz solo puede tener un juicio por resultado de aprendizaje.

---

## 5.12 Tabla: `actividad_resultado` (Pivote)
Relación muchos a muchos entre actividades y resultados de aprendizaje.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_actividad` | INT | PK, FK → actividad | Actividad vinculada |
| `id_resultado` | INT | PK, FK → resultado_aprendizaje | Resultado vinculado |

---

## 5.13 Tabla: `novedad_retiro`
Registro de novedades de retiro de aprendices.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id_novedad` | INT | PK, AUTO_INCREMENT | Identificador único |
| `id_aprendiz` | INT | FK → aprendiz | Aprendiz retirado |
| `id_funcionario` | INT | FK → funcionario | Funcionario que reportó el retiro |
| `id_fase` | INT | FK → fase | Fase en la que se produjo el retiro |
| `motivo` | VARCHAR(100) | — | Motivo del retiro |
| `observaciones` | TEXT | — | Observaciones adicionales |
| `fecha` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Fecha del registro de la novedad |

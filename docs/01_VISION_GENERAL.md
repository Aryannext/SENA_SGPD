# 01 — Visión General del Sistema

## 1.1 Nombre del Proyecto
**SGPD SENA** — Sistema de Gestión de Progreso y Desempeño Académico

## 1.2 Contexto Institucional
El Servicio Nacional de Aprendizaje (SENA), Regional Caquetá, utiliza la plataforma **Sofía Plus** como sistema oficial para registrar juicios evaluativos de los aprendices. Sin embargo, Sofía Plus carece de herramientas analíticas que permitan a los instructores y coordinadores académicos visualizar de manera rápida y eficiente el progreso de los aprendices, identificar rezagados o detectar patrones de deserción.

## 1.3 Problemática
- Los instructores deben descargar reportes Excel de Sofía Plus y analizarlos manualmente.
- No existe un dashboard centralizado que muestre el avance por competencias o resultados de aprendizaje.
- No hay alertas tempranas para aprendices en riesgo de deserción.
- La trazabilidad de quién evaluó cada competencia es difícil de rastrear.

## 1.4 Objetivo General
Desarrollar una aplicación web que permita importar automáticamente los reportes de juicios evaluativos de Sofía Plus (Excel) y transformarlos en un sistema analítico visual con dashboards interactivos, seguimiento por fases, gestión de proyectos formativos e inteligencia artificial integrada.

## 1.5 Objetivos Específicos
1. **Automatizar la importación** de datos desde archivos Excel de Sofía Plus hacia una base de datos relacional normalizada.
2. **Visualizar el progreso académico** de cada aprendiz mediante gráficos interactivos y métricas de rendimiento.
3. **Identificar aprendices en riesgo** a través de análisis de competencias pendientes y distribución del rendimiento.
4. **Gestionar proyectos formativos** con sus fases, actividades y relaciones con resultados de aprendizaje.
5. **Integrar inteligencia artificial** (SENA-IA) para análisis predictivo y consultas en lenguaje natural.

## 1.6 Alcance del Sistema
El sistema abarca los siguientes módulos funcionales:

| Módulo | Descripción |
|--------|-------------|
| Dashboard Principal | Panel analítico con KPIs, gráficos de rendimiento y tabla de aprendices |
| Análisis por Fases | Seguimiento del avance académico organizado por fases del proyecto |
| Importar Datos | Carga automática de archivos Excel (.xls/.xlsx) de Sofía Plus |
| Programas de Formación | Gestión de programas, fichas y aprendices asociados |
| Aprendices | Perfil individual con historial de calificaciones y competencias |
| Proyecto Formativo | Estructuración de fases, actividades y resultados de aprendizaje |
| Análisis de Deserción | Monitoreo de retiros, cancelaciones y traslados |
| SENA-IA (Chat) | Asistente inteligente con IA local (Ollama) para consultas analíticas |

## 1.7 Usuarios del Sistema
- **Instructores:** Consultan el avance de sus aprendices y fichas asignadas.
- **Coordinadores Académicos:** Visualizan estadísticas generales y detectan patrones de riesgo.
- **Gestores de Proyecto:** Estructuran el proyecto formativo con fases y actividades.

## 1.8 Restricciones y Limitaciones
- El sistema no se conecta directamente con Sofía Plus; la alimentación de datos es mediante carga manual de archivos Excel.
- La funcionalidad de IA requiere que el servidor Ollama esté ejecutándose localmente.
- No incluye autenticación de usuarios (en la versión actual v2.0).

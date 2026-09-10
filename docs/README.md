# 📋 SGPD SENA — Documentación Técnica del Sistema

**Sistema de Gestión de Progreso y Desempeño Académico**
Servicio Nacional de Aprendizaje — SENA Regional Caquetá

---

## Índice de Documentación

| N° | Documento | Descripción |
|----|-----------|-------------|
| 01 | [Visión General del Sistema](01_VISION_GENERAL.md) | Alcance, objetivos y contexto del proyecto |
| 02 | [Stack Tecnológico](02_STACK_TECNOLOGICO.md) | Tecnologías, versiones y justificación |
| 03 | [Arquitectura del Sistema](03_ARQUITECTURA.md) | Patrón MVC, estructura de carpetas y flujo de datos |
| 04 | [Modelo Entidad-Relación (Base de Datos)](04_MODELO_ER.md) | Diagrama ER, tablas y relaciones |
| 05 | [Diccionario de Datos](05_DICCIONARIO_DATOS.md) | Descripción detallada de cada tabla y columna |
| 06 | [Mapa de Rutas y Endpoints](06_RUTAS_ENDPOINTS.md) | Rutas Web y API REST del sistema |
| 07 | [Diagrama de Componentes](07_DIAGRAMA_COMPONENTES.md) | Controladores, Modelos, Servicios y Vistas |
| 08 | [Flujos de Proceso](08_FLUJOS_PROCESO.md) | Diagramas de secuencia de los procesos clave |
| 09 | [Módulos Funcionales](09_MODULOS_FUNCIONALES.md) | Descripción detallada de cada módulo del sistema |
| 10 | [Guía de Instalación y Despliegue](10_INSTALACION_DESPLIEGUE.md) | Requisitos, configuración local y hosting |
| 11 | [Requisitos Funcionales](11_REQUISITOS_FUNCIONALES.md) | Qué debe hacer el sistema (RF-01 … RF-44) |
| 12 | [Requisitos No Funcionales](12_REQUISITOS_NO_FUNCIONALES.md) | Rendimiento, seguridad, datos personales y operación (RNF-01 … RNF-30) |
| 13 | [Reglas de Negocio](13_REGLAS_NEGOCIO.md) | Restricciones del dominio y dónde se aplican (RN-01 … RN-25) |
| 14 | [Historias de Usuario](14_HISTORIAS_USUARIO.md) | Necesidades por rol con criterios de aceptación (HU-01 … HU-19) |
| 15 | [Matriz de Trazabilidad](15_MATRIZ_TRAZABILIDAD.md) | Enunciado → requisito → código → prueba |
| 16 | [Plan de Pruebas](16_PLAN_PRUEBAS.md) | Casos de prueba derivados de los criterios (CP-01 … CP-53) |

---


---

## Datos de prueba y protección de datos personales

Este repositorio **no contiene reportes reales de Sofía Plus**. Los reportes de
juicios evaluativos incluyen número de documento, nombres y estado de aprendices
identificables: son datos personales protegidos por la **Ley 1581 de 2012** y no
deben versionarse ni publicarse.

Para probar la importación, ejecutar la demostración o desarrollar, use el archivo
anonimizado incluido:

```
docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx
```

Conserva la estructura exacta del reporte de Sofía Plus y produce **los mismos
conteos** que el archivo original (31 aprendices, 20 competencias, 75 resultados de
aprendizaje, 14 funcionarios y 2.325 calificaciones), pero con documentos, nombres
de aprendices y nombres de instructores ficticios. El resto —programa, ficha,
competencias, resultados y juicios— es idéntico al real.

### Si trabaja con un reporte real

1. Manténgalo **fuera** de la carpeta del proyecto, o dentro de una ruta ya excluida
   en `.gitignore`.
2. `public/uploads/` está excluido del control de versiones: los archivos que suban
   los usuarios se quedan solo en el servidor.
3. `tts_server/reference_voice/voice.wav` tampoco se versiona, porque es la voz de
   una persona real. Cada instalación debe grabar la suya (ver paso 5 del README).

---

**Versión:** 2.0  
**Ficha:** ADSO 2480542  
**Última actualización:** Julio 2026

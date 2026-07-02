# 08 — Flujos de Proceso

## 8.1 Proceso de Importación de Datos (Excel → Base de Datos)

Este es el proceso más crítico del sistema. Transforma un archivo Excel descargado de Sofía Plus en datos normalizados en la base de datos.

```mermaid
sequenceDiagram
    actor User as Instructor
    participant UI as import.js
    participant API as ImportController
    participant SVC as ExcelImportService
    participant XL as PhpSpreadsheet
    participant DB as MySQL

    User->>UI: Selecciona archivo Excel (.xls/.xlsx)
    UI->>UI: Validación del tipo de archivo
    UI->>API: POST /api/import/upload (multipart)
    API->>API: Mueve archivo a /public/uploads/
    API->>SVC: import($filePath)
    
    SVC->>XL: IOFactory::load($filePath)
    XL-->>SVC: Objeto Spreadsheet
    
    Note over SVC: Fase 1: Leer metadata (Filas 4-9)
    SVC->>XL: getCellValue('C4') → Programa
    SVC->>XL: getCellValue('C5') → Ficha
    SVC->>DB: INSERT programa (si no existe)
    SVC->>DB: INSERT ficha (si no existe)
    
    Note over SVC: Fase 2: Detectar columnas (Fila 13)
    SVC->>XL: Escanear encabezados
    SVC-->>SVC: Mapeo automático de columnas
    
    Note over SVC: Fase 3: Procesar filas (14+)
    loop Por cada fila de datos
        SVC->>DB: INSERT aprendiz (ON DUPLICATE KEY UPDATE)
        SVC->>DB: INSERT competencia (si nueva)
        SVC->>DB: INSERT resultado_aprendizaje (si nuevo)
        SVC->>DB: INSERT funcionario (si nuevo)
        SVC->>DB: INSERT calificacion (juicio evaluativo)
    end
    
    SVC-->>API: Estadísticas de importación
    API-->>UI: JSON con resultados
    UI->>User: Resumen visual de la importación
```

### Detalle del Mapeo Automático de Columnas

El servicio escanea la fila 13 del Excel buscando estas palabras clave:

| Palabra clave en encabezado | Campo mapeado |
|---------------------------|---------------|
| "Tipo" + "Doc" | `tiDoc` (tipo de documento) |
| "Número" + "Doc" | `nuDoc` (número de documento) |
| "Nombre" (sin "Doc") | `nombre` |
| "Apellido" | `apellido` |
| "Estado" | `estado` |
| "Competencia" | `compRaw` (código + nombre) |
| "Resultado" | `resRaw` (código + nombre) |
| "Juicio" (sin "Fecha"/"Funcionario") | `juicio` |
| "Fecha" + "Juicio" | `fechaJuicio` |
| "Funcionario" | `funcRaw` |

---

## 8.2 Proceso de Consulta del Dashboard

```mermaid
sequenceDiagram
    actor User as Instructor
    participant Browser as Navegador
    participant JS as dashboard.js
    participant API as DashboardController
    participant DB as MySQL
    participant Chart as Chart.js

    User->>Browser: Abre /dashboard
    Browser->>API: GET /dashboard (HTML)
    API-->>Browser: Vista HTML con estructura vacía
    
    Browser->>JS: DOMContentLoaded
    JS->>API: GET /api/dashboard/fichasActivas
    API->>DB: SELECT fichas con programas
    DB-->>API: Lista de fichas
    API-->>JS: JSON fichas
    JS->>JS: Popular selector de fichas
    
    JS->>API: GET /api/dashboard/stats?ficha=1
    API->>DB: Múltiples consultas SQL
    Note over API,DB: KPIs, histograma, aprendices,<br/>avance por competencia,<br/>foco de atención
    DB-->>API: Datos agregados
    API-->>JS: JSON completo
    
    JS->>Chart: Renderizar gráficos
    JS->>JS: Renderizar tabla de aprendices
    JS->>JS: Popular filtros (estados)
    JS-->>User: Dashboard completo e interactivo
```

---

## 8.3 Proceso del Chat SENA-IA

```mermaid
sequenceDiagram
    actor User as Instructor
    participant UI as chat.js
    participant API as AIController
    participant CTX as DataContextService
    participant DB as MySQL
    participant OLL as OllamaService
    participant LLM as Ollama (DeepSeek-R1)

    User->>UI: Escribe mensaje
    UI->>API: POST /api/chat/send {message, history}
    
    API->>CTX: getContext()
    CTX->>DB: Consultas de fichas, aprendices, estadísticas
    DB-->>CTX: Datos del sistema
    CTX-->>API: Contexto textual formateado
    
    API->>OLL: chatStream(messages, context)
    OLL->>LLM: POST /api/chat (streaming)
    
    loop Streaming SSE
        LLM-->>OLL: Token de texto
        OLL-->>API: data: {token}
        API-->>UI: Server-Sent Event
        UI->>UI: Renderizar token en tiempo real
    end
    
    LLM-->>OLL: {done: true}
    OLL-->>API: Fin del streaming
    
    Note over UI: Detectar acciones especiales
    UI->>UI: ¿Contiene [CHART:{...}]?
    UI->>UI: Si sí → Renderizar gráfico Chart.js
    UI->>UI: ¿Contiene [REPORT:...]?
    UI->>UI: Si sí → Ofrecer exportar reporte
    
    UI-->>User: Respuesta completa con gráficos
```

---

## 8.4 Proceso de Filtrado del Dashboard

```mermaid
flowchart TD
    A[Usuario cambia filtro] --> B{¿Qué filtro cambió?}
    B -->|Ficha| C[GET /api/dashboard/stats?ficha=X]
    B -->|Estado / Documento / Búsqueda| D[GET /api/dashboard/filtrar]
    C --> E[Recalcular TODO: KPIs + gráficos + tabla]
    D --> F[Recalcular solo tabla de aprendices]
    E --> G[Actualizar UI completa]
    F --> H[Actualizar solo tabla]
    G --> I[Dashboard actualizado]
    H --> I
```

---

## 8.5 Proceso de Creación de Proyecto Formativo

```mermaid
sequenceDiagram
    actor User as Instructor
    participant UI as proyecto/detalle.php
    participant API as ProyectoController
    participant PDF as ProjectPdfParserService
    participant DB as MySQL

    User->>UI: Sube PDF del proyecto
    UI->>API: POST /api/proyecto/upload-pdf
    API->>PDF: parse($filePath)
    PDF->>PDF: Extraer texto del PDF
    PDF->>PDF: Identificar fases y actividades
    PDF-->>API: Estructura del proyecto
    API->>DB: INSERT proyecto_formativo
    API->>DB: INSERT fases (por cada fase detectada)
    API->>DB: INSERT actividades (por cada actividad)
    API-->>UI: Proyecto creado con estructura
    
    User->>UI: Asigna proyecto a ficha
    UI->>API: POST /api/proyecto/asignar
    API->>DB: UPDATE ficha SET id_proyecto = ?
    API-->>UI: Confirmación
```

---

## 8.6 Ciclo de Vida de los Datos

```mermaid
flowchart LR
    A[Sofía Plus] -->|Descarga Excel| B[Archivo .xls/.xlsx]
    B -->|Carga en SGPD| C[ExcelImportService]
    C -->|Normaliza| D[(Base de Datos MySQL)]
    D -->|Consulta| E[Dashboard]
    D -->|Consulta| F[Perfil Aprendiz]
    D -->|Contexto| G[SENA-IA]
    D -->|Análisis| H[Deserción]
    E --> I[Toma de Decisiones]
    G --> I
    H --> I
```

# 04 — Modelo Entidad-Relación (Base de Datos)

## 4.1 Diagrama Entidad-Relación

```mermaid
erDiagram
    PROGRAMA ||--o{ FICHA : "tiene"
    PROGRAMA ||--o{ PROGRAMA_COMPETENCIA : "asocia"
    PROGRAMA ||--o{ PROYECTO_FORMATIVO : "pertenece"
    
    COMPETENCIA ||--o{ PROGRAMA_COMPETENCIA : "asocia"
    COMPETENCIA ||--o{ RESULTADO_APRENDIZAJE : "contiene"
    
    PROYECTO_FORMATIVO ||--o{ FASE : "contiene"
    
    FASE ||--o{ ACTIVIDAD : "contiene"
    FASE ||--o{ NOVEDAD_RETIRO : "registra"
    
    ACTIVIDAD ||--o{ ACTIVIDAD_RESULTADO : "vincula"
    
    RESULTADO_APRENDIZAJE ||--o{ ACTIVIDAD_RESULTADO : "vincula"
    RESULTADO_APRENDIZAJE ||--o{ CALIFICACION : "evalúa"
    
    FICHA ||--o{ APRENDIZ : "matricula"
    FICHA }o--o| PROYECTO_FORMATIVO : "asigna"
    
    APRENDIZ ||--o{ CALIFICACION : "recibe"
    APRENDIZ ||--o{ NOVEDAD_RETIRO : "genera"
    
    FUNCIONARIO ||--o{ CALIFICACION : "registra"
    FUNCIONARIO ||--o{ NOVEDAD_RETIRO : "reporta"

    PROGRAMA {
        int id_programa PK
        varchar codigo_programa UK
        varchar nombre_programa
        enum modalidad
    }
    
    PROYECTO_FORMATIVO {
        int id_proyecto PK
        varchar codigo_proyecto UK
        varchar nombre_proyecto
        varchar ruta_pdf
        int id_programa FK
    }
    
    FASE {
        int id_fase PK
        varchar nombre_fase
        int id_proyecto FK
    }
    
    ACTIVIDAD {
        int id_actividad PK
        varchar cod_actividad
        varchar nombre_actividad
        int id_fase FK
    }
    
    COMPETENCIA {
        int id_competencia PK
        varchar cod_competencia UK
        varchar nombre
    }
    
    PROGRAMA_COMPETENCIA {
        int id_programa PK_FK
        int id_competencia PK_FK
    }
    
    RESULTADO_APRENDIZAJE {
        int id_resultado PK
        varchar cod_resultado UK
        text nombre_resultado
        int id_competencia FK
    }
    
    ACTIVIDAD_RESULTADO {
        int id_actividad PK_FK
        int id_resultado PK_FK
    }
    
    FICHA {
        int id_ficha PK
        varchar nu_ficha UK
        enum estado
        date fecha_inicio
        date fecha_fin
        int id_programa FK
        int id_proyecto FK
    }
    
    APRENDIZ {
        int id_aprendiz PK
        enum ti_documento
        varchar nu_documento UK
        varchar nombre
        varchar apellido
        varchar estado
        int id_ficha FK
    }
    
    FUNCIONARIO {
        int id_funcionario PK
        enum ti_documento
        varchar nu_documento UK
        varchar nombre
    }
    
    CALIFICACION {
        int id_calificacion PK
        int id_aprendiz FK
        int id_resultado FK
        int id_funcionario FK
        enum jui_evaluativo
        datetime fecha_registro
    }
    
    NOVEDAD_RETIRO {
        int id_novedad PK
        int id_aprendiz FK
        int id_funcionario FK
        int id_fase FK
        varchar motivo
        text observaciones
        datetime fecha
    }
```

## 4.2 Relaciones Principales

| Relación | Tipo | Descripción |
|----------|------|-------------|
| `programa` → `ficha` | 1:N | Un programa tiene muchas fichas |
| `programa` ↔ `competencia` | N:M | Tabla pivote `programa_competencia` |
| `competencia` → `resultado_aprendizaje` | 1:N | Una competencia tiene varios resultados de aprendizaje |
| `ficha` → `aprendiz` | 1:N | Una ficha matricula muchos aprendices |
| `aprendiz` → `calificacion` | 1:N | Un aprendiz recibe muchas calificaciones |
| `resultado_aprendizaje` → `calificacion` | 1:N | Un resultado puede ser evaluado en muchas calificaciones |
| `funcionario` → `calificacion` | 1:N | Un funcionario registra muchas calificaciones |
| `proyecto_formativo` → `fase` | 1:N | Un proyecto tiene varias fases |
| `fase` → `actividad` | 1:N | Una fase contiene varias actividades |
| `actividad` ↔ `resultado_aprendizaje` | N:M | Tabla pivote `actividad_resultado` |
| `ficha` → `proyecto_formativo` | N:1 | Una ficha se asocia a un proyecto |

## 4.3 Reglas de Integridad Referencial

| Tabla Padre | Tabla Hija | Acción ON DELETE |
|------------|------------|------------------|
| `programa` | `ficha` | `SET NULL` |
| `programa` | `proyecto_formativo` | `SET NULL` |
| `ficha` | `aprendiz` | `CASCADE` |
| `aprendiz` | `calificacion` | `CASCADE` |
| `aprendiz` | `novedad_retiro` | `CASCADE` |
| `competencia` | `resultado_aprendizaje` | `CASCADE` |
| `resultado_aprendizaje` | `calificacion` | `CASCADE` |
| `funcionario` | `calificacion` | `SET NULL` |
| `proyecto_formativo` | `fase` | `CASCADE` |
| `fase` | `actividad` | `CASCADE` |

## 4.4 Motor de Almacenamiento
Todas las tablas utilizan **InnoDB** para garantizar:
- Soporte de transacciones ACID
- Bloqueo a nivel de fila
- Claves foráneas con integridad referencial

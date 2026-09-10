# 🎓 Sistema de Gestión de Progreso y Desempeño (SGPD) - SENA

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Python Version](https://img.shields.io/badge/Python-3.8%2B-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org/)
[![Ollama AI](https://img.shields.io/badge/Ollama-Local_AI-000000?style=for-the-badge&logo=ollama&logoColor=white)](https://ollama.com/)
[![Architecture](https://img.shields.io/badge/Arquitectura-MVC_%2B_Services-00C853?style=for-the-badge)](#-arquitectura-y-diseño-por-qué-está-estructurado-así)

El **Sistema de Gestión de Progreso y Desempeño (SGPD)** es una plataforma web moderna y escalable desarrollada para el **SENA (Servicio Nacional de Aprendizaje)**. Su misión es centralizar, optimizar y auditar el rendimiento académico y los juicios evaluativos de los aprendices (enfocado inicialmente en programas como *Análisis y Desarrollo de Software - ADSO*), complementado con capacidades de **Inteligencia Artificial local (SENA-IA)** para análisis de datos, alertas tempranas de deserción y **síntesis/clonación de voz**.

---

## 🏛️ Arquitectura y Diseño: ¿Por qué está estructurado así?

A diferencia de muchos proyectos que utilizan frameworks pesados (como Laravel o Symfony) que añaden capas innecesarias y consumo excesivo de recursos para aplicaciones a la medida, el **SGPD** fue diseñado bajo una arquitectura **MVC (Modelo-Vista-Controlador) pura con capa de Servicios**, construida en PHP Vanilla utilizando el estándar **PSR-4** de autocarga (vía Composer).

### ¿Por qué esta arquitectura y no otra?

1. **Separación Estricta de Responsabilidades (SRP - Single Responsibility Principle):**
   - **Núcleo (`core/`):** Contiene la infraestructura pura (`Router.php`, `Controller.php`, `Model.php`). No sabe nada del negocio, solo maneja solicitudes HTTP, vistas y el ciclo de vida del PDO.
   - **Controladores (`app/Controllers/`):** Actúan únicamente como directores de tráfico. Reciben la petición del usuario, validan entradas, llaman a un modelo o servicio, y devuelven una vista o una respuesta JSON.
   - **Modelos (`app/Models/`):** Encapsulan las consultas SQL (`FIND`, `INSERT`, `UPDATE`). Ningún controlador ejecuta SQL crudo directamente para mantener el código limpio y seguro contra inyecciones SQL.
   - **Servicios (`app/Services/`):** Aquí reside la magia del código limpio. Toda lógica pesada o externa se extrajo de los controladores hacia servicios especializados:
     - `ExcelImportService`: Procesa reportes de SOFIA Plus sin saturar al controlador.
     - `OllamaService`: Comunica el backend con la IA local mediante cURL.
     - `TTSService`: Gestiona la síntesis de voz con el microservicio Python.
     - `ProjectPdfParserService`: Extrae información estructurada de PDFs formativos.

2. **Patrón Singleton para Base de Datos (`core/Model.php`):**
   Garantiza que toda la aplicación comparta **una única instancia de conexión PDO** por petición HTTP, evitando la saturación de conexiones en MySQL/MariaDB y mejorando drásticamente el rendimiento.

3. **Inversión de Dependencias y Código Abierto/Cerrado (SOLID):**
   Los controladores no dependen de contraseñas de base de datos ni de URLs de IA quemadas en el código; dependen de abstracciones que leen archivos de configuración centralizados en la carpeta `config/`. Si mañana la base de datos migra a un servidor en la nube o cambia el modelo de IA en Ollama, **solo se modifica un archivo de configuración (`config/`), sin tocar una sola línea de lógica de negocio**.

---

## 📁 Estructura del Proyecto Detallada

```text
SENA_SGPD/
├── app/                      # Lógica de negocio de la aplicación (Namespace PSR-4: App\)
│   ├── Controllers/          # Controladores (Dashboard, Aprendiz, Proyecto, AI, Desercion, etc.)
│   ├── Models/               # Modelos de Base de Datos (Aprendiz, Ficha, Calificacion, etc.)
│   └── Services/             # Servicios especializados (ExcelImportService, OllamaService, etc.)
├── config/                   # Configuración centralizada (desacoplada del código fuente)
│   ├── database.php          # Credenciales de conexión PDO a MySQL/MariaDB
│   ├── ollama.php            # Configuración y prompts del modelo de Inteligencia Artificial local
│   └── tts.php               # Rutas y parámetros del microservicio de clonación de voz
├── core/                     # Motor del framework propio (Namespace PSR-4: Core\)
│   ├── Controller.php        # Clase base para renderizado de vistas layouts y respuestas JSON
│   ├── Model.php             # Clase base de acceso a datos con patrón Singleton PDO
│   └── Router.php            # Enrutador dinámico para rutas Web (HTML) y API REST (JSON)
├── docs/                     # Documentación técnica completa (10 módulos explicativos + SQL)
│   ├── SGPD_SENA.sql         # Script SQL maestro para crear y poblar la base de datos
│   ├── 01_VISION_GENERAL.md  # Manual de visión e hitos del sistema
│   └── ...                   # Diagramas ER, rutas, flujos de proceso y diccionario de datos
├── public/                   # Activos estáticos públicos (Punto de acceso de estilos y JS)
│   ├── css/                  # Estilos CSS Vanilla optimizados (diseño moderno y responsive)
│   ├── js/                   # Controladores JavaScript (AJAX, Chart.js, interactividad)
│   └── audio/                # Caché de archivos de voz generados por IA (.wav)
├── tts_server/               # Microservicio independiente en Python (Flask + Coqui XTTS v2)
│   ├── server.py             # API REST en Python para síntesis y clonación de voz
│   ├── requirements.txt      # Dependencias de Python necesarias (torch, TTS, flask, etc.)
│   └── reference_voice/      # Muestra de audio de referencia para clonar la voz (voice.wav)
├── vendor/                   # Dependencias de PHP instaladas por Composer (NO tocar)
├── views/                    # Plantillas de interfaz gráfica y modales (aprendiz, dashboard, etc.)
├── composer.json             # Declaración de dependencias PHP (PhpSpreadsheet, PdfParser) y PSR-4
├── index.php                 # Front Controller: Único punto de entrada HTTP para toda la web
└── README.md                 # Este documento de presentación e instrucciones
```

---

## 🛠️ Guía de Instalación Paso a Paso (Explicada a Detalle)

Esta guía está diseñada para que **cualquier persona** (sea desarrollador o no) pueda clonar el proyecto en su computadora local y ponerlo a funcionar **al 100% en menos de 15 minutos**, sin errores ni suposiciones.

### 🧩 0. Prerrequisitos (Lo que debes instalar primero en tu PC)

Antes de empezar, asegúrate de tener instalados los siguientes programas en tu computadora:

1. **Servidor Local XAMPP o WAMP (o Laragon):**
   - Debe incluir **PHP 8.0 o superior** y **MySQL / MariaDB**.
   - Descárgalo de [apachefriends.org](https://www.apachefriends.org/) e instálalo.
2. **Composer (Gestor de paquetes PHP):**
   - Permite descargar las librerías para leer archivos Excel y PDF.
   - Descárgalo de [getcomposer.org](https://getcomposer.org/) e instálalo dejando las opciones por defecto.
3. **Python 3.8 o superior (Para que la IA hable):**
   - Descárgalo de [python.org](https://www.python.org/downloads/).
   - ⚠️ **MUY IMPORTANTE EN WINDOWS:** Al instalar Python, asegúrate de marcar la casilla **`"Add Python to PATH"`** en la primera pantalla del instalador.
4. **Ollama (Para el cerebro de la Inteligencia Artificial local):**
   - Descárgalo e instálalo desde [ollama.com](https://ollama.com/).

---

### 🚀 Paso 1: Ubicar el proyecto en tu servidor web (`htdocs`)

Para que tu servidor Apache pueda leer la página, el proyecto debe estar en la carpeta pública del servidor web.

1. Si usas **XAMPP en Windows**, ve a la carpeta `C:\xampp\htdocs\`.
2. Si clonas desde Git, abre una terminal o Git Bash dentro de `htdocs` y ejecuta:
   ```bash
   git clone https://github.com/Aryannext/SENA_SGPD.git SENA_SGPD
   ```
   *(Si descargaste el archivo `.zip`, descomprímelo dentro de `C:\xampp\htdocs\` y asegúrate de que la carpeta principal se llame exactamente `SENA_SGPD`).*

---

### 🗄️ Paso 2: Crear y poblar la Base de Datos

1. Abre **XAMPP Control Panel** y enciende los botones **Apache** y **MySQL** (ambos deben ponerse en verde).
2. Abre tu navegador web y entra a: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. En la barra lateral izquierda, haz clic en **"Nueva"** para crear una base de datos.
4. En el campo *Nombre de base de datos*, escribe exactamente: `sistema_sena`
5. En el menú desplegable al lado (Cotejamiento/Collation), selecciona `utf8mb4_general_ci` y haz clic en **Crear**.
6. Con la base de datos `sistema_sena` seleccionada, haz clic en la pestaña superior **Importar**.
7. Haz clic en **Seleccionar archivo**, busca dentro de la carpeta de tu proyecto el archivo:
   `C:\xampp\htdocs\SENA_SGPD\docs\SGPD_SENA.sql`
8. Ve al fondo de la página y haz clic en el botón **Continuar/Importar**. Verás un mensaje en verde confirmando que se crearon las 13 tablas del sistema con sus datos iniciales.

> [!NOTE]
> **Verificación de conexión:** Por defecto, XAMPP usa usuario `root` y contraseña en blanco (`""`). Si tu servidor MySQL tiene contraseña, edita el archivo `config/database.php` y coloca tu usuario y clave en las líneas `'username'` y `'password'`.

---

### 📦 Paso 3: Instalar las dependencias de PHP (Excel y PDF)

El proyecto utiliza librerías de PHP (`PhpSpreadsheet` y `PdfParser`) que deben instalarse antes de abrir la web.

1. Abre una terminal (o consola de comandos `cmd` o `PowerShell`).
2. Entra a la carpeta del proyecto escribiendo:
   ```bash
   cd C:\xampp\htdocs\SENA_SGPD
   ```
3. Ejecuta el comando de Composer para descargar todo automáticamente:
   ```bash
   composer install
   ```
4. Espera unos segundos hasta que aparezca el mensaje de éxito y se cree la carpeta `vendor/`.

---

### 🤖 Paso 4: Encender la Inteligencia Artificial Local (Ollama)

Para que el asistente **SENA-IA** y la predicción de riesgo de deserción funcionen, el motor de IA local debe estar activo.

1. Abre una nueva terminal en tu computadora (`cmd` o `PowerShell`).
2. Ejecuta el siguiente comando para descargar y encender el modelo configurado en el sistema:
   ```bash
   ollama run deepseek-r1:8b
   ```
   *(Nota: Si prefieres o tienes instalado otro modelo rápido como `qwen3:8b` o `llama3`, puedes cambiar el nombre en el archivo `config/ollama.php` en la línea `'model' => 'tu-modelo'`).*
3. Una vez termine de descargar y te permita escribir en la consola de Ollama, **deja esa consola abierta o minimizada en segundo plano**. El servicio quedará escuchando en el puerto `11434`.

---

### 🎙️ Paso 5: Encender el Microservicio de Voz (Clonación TTS - Opcional pero recomendado)

Para que al presionar el botón del altavoz en el chat **SENA-IA** te responda hablando con voz sintetizada y clonada:

1. Abre otra terminal (`cmd` o `PowerShell`) y entra a la carpeta del microservicio Python:
   ```bash
   cd C:\xampp\htdocs\SENA_SGPD\tts_server
   ```
2. *(Recomendado para no interferir con otros proyectos Python)* Crea un entorno virtual:
   ```bash
   python -m venv venv
   ```
3. Activa el entorno virtual:
   - En **Windows (cmd)**: `venv\Scripts\activate.bat`
   - En **Windows (PowerShell)**: `.\venv\Scripts\Activate.ps1`
   - En **Linux/Mac**: `source venv/bin/activate`
4. Instala las librerías de Python requeridas (Flask, PyTorch, Coqui TTS):
   ```bash
   pip install -r requirements.txt
   ```
5. **Asegúrate de tener un archivo de voz de muestra:**
   Verifica que exista un archivo de audio en formato `.wav` corto (de unos 5 a 15 segundos con la voz de la persona que quieres clonar) en la ruta exacta:
   `SENA_SGPD/tts_server/reference_voice/voice.wav`
   *(Si no existe, puedes grabar un audio corto diciendo "Hola, soy el asistente del SENA", guardarlo como `voice.wav` en esa carpeta).*
6. Enciende el servidor de voz en Python:
   ```bash
   python server.py
   ```
   > [!IMPORTANT]
   > **Nota sobre la primera ejecución del TTS:** La primera vez que ejecutes `python server.py`, el motor descargará el modelo **Coqui XTTS v2** (aprox. 1.8 GB). Esto puede tardar varios minutos dependiendo de tu conexión a internet. Una vez descargado, el servidor iniciará instantáneamente en el puerto `http://127.0.0.1:5050`. Deja esta terminal abierta en segundo plano.

---

### 🌐 Paso 6: ¡Listo! Abrir el sistema en tu navegador

Con tu Apache, MySQL, Ollama y el servidor de voz en ejecución, abre tu navegador favorito (Chrome, Edge, Firefox) y entra a:

👉 **[http://localhost/SENA_SGPD](http://localhost/SENA_SGPD)**

¡Deberías ver de inmediato el **Dashboard Principal del SGPD** cargado con métricas, gráficas interactivas y acceso total a todas las herramientas!

---

## 🧪 Ejecutar las pruebas

El proyecto trae una suite propia, sin dependencias externas:

```bash
php tests/run.php
```

Devuelve `0` si todo pasa y `1` si algo falla, así que sirve tal cual en un hook de
pre-commit o en integración continua. También acepta un filtro:

```bash
php tests/run.php Unit          # solo un directorio
php tests/run.php RutasTest     # solo una clase
```

Las pruebas de integración necesitan un servidor MySQL. Si no lo encuentran **se omiten
en lugar de fallar**, de modo que la suite corre en cualquier máquina:

```bash
TEST_DB_PORT=3306 TEST_DB_USER=root TEST_DB_PASS= php tests/run.php
```

Crean y destruyen su propia base `sgpd_pruebas`; nunca tocan `sistema_sena`. Los datos
de prueba salen de `docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx`, un reporte con la
estructura real de Sofía Plus pero con aprendices e instructores ficticios.

El detalle de los 54 casos está en
[docs/16_PLAN_PRUEBAS.md](docs/16_PLAN_PRUEBAS.md).

---

## 📚 Documentación Técnica y Manuales Avanzados

Si eres evaluador, instructor o desarrollador y deseas profundizar en cada aspecto técnico, matemático o arquitectónico del proyecto, consulta nuestra documentación integral dentro de la carpeta [`docs/`](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs):

1. **[01_VISION_GENERAL.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/01_VISION_GENERAL.md)**: Alcance, objetivos y contexto formativo del SENA.
2. **[02_STACK_TECNOLOGICO.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/02_STACK_TECNOLOGICO.md)**: Justificación técnica de cada lenguaje, librería y herramienta elegida.
3. **[03_ARQUITECTURA.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/03_ARQUITECTURA.md)**: Patrones de diseño, separación de capas y flujos de datos del núcleo MVC.
4. **[04_MODELO_ER.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/04_MODELO_ER.md)**: Modelo Entidad-Relación y explicación de la normalización de la base de datos.
5. **[05_DICCIONARIO_DATOS.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/05_DICCIONARIO_DATOS.md)**: Definición campo por campo, tipos de datos e índices de todas las tablas MySQL.
6. **[06_RUTAS_ENDPOINTS.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/06_RUTAS_ENDPOINTS.md)**: Catálogo exhaustivo de todas las rutas Web (HTML) y endpoints API REST (JSON).
7. **[07_DIAGRAMA_COMPONENTES.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/07_DIAGRAMA_COMPONENTES.md)**: Interacción entre el front-end, los controladores, servicios y microservicios externos.
8. **[08_FLUJOS_PROCESO.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/08_FLUJOS_PROCESO.md)**: Diagramas de secuencia y flujos paso a paso de importación y chat con IA.
9. **[09_MODULOS_FUNCIONALES.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/09_MODULOS_FUNCIONALES.md)**: Guía de uso y reglas de negocio de los módulos de Fichas, Proyectos y Deserción.
10. **[10_INSTALACION_DESPLIEGUE.md](file:///c:/Users/crist/OneDrive/Documentos/Trabajos_github/SENA_SGPD/docs/10_INSTALACION_DESPLIEGUE.md)**: Guía avanzada de despliegue en servidores de producción o Linux.

---

## 👨‍💻 Créditos y Licencia

Desarrollado para el **Servicio Nacional de Aprendizaje (SENA)** como solución tecnológica innovadora de apoyo a la gestión formativa, seguimiento académico e integración con Inteligencia Artificial de última generación.

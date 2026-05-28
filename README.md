# Sistema de Gestión de Progreso y Desempeño (SGPD) - SENA

El SGPD es una plataforma web desarrollada para el SENA, diseñada para gestionar el progreso de los aprendices, visualizar métricas de evaluación, y asistir a los instructores mediante Inteligencia Artificial (con capacidades de chat y clonación de voz).

## 🚀 Características Principales
- **Gestión de Fichas y Aprendices**: Control de juicios evaluativos y progreso por fases.
- **Importación de Datos**: Carga mediante archivos Excel y PDF.
- **SENA-IA**: Asistente virtual basado en IA local, capaz de generar gráficos, reportes, e interactuar por voz.
- **Síntesis de Voz (TTS)**: Respuestas leídas con clonación de voz a través de un microservicio Python.

---

## 🛠 Requisitos Previos (Prerrequisitos)

Para ejecutar este proyecto en tu entorno local, necesitas tener instalado:

1. **XAMPP** (con PHP >= 8.0 y MySQL/MariaDB).
2. **Composer**: Gestor de dependencias de PHP.
3. **Python 3.8+ y pip**: Para ejecutar el microservicio de Text-to-Speech (TTS).
4. **Ollama**: Motor local de modelos de Inteligencia Artificial (para ejecutar `qwen3:8b`).

---

## ⚙️ Guía de Instalación Paso a Paso

### Paso 1: Configurar el proyecto en XAMPP
El proyecto debe estar ubicado en la carpeta `htdocs` de tu instalación de XAMPP. Si estás en Linux (XAMPP instalado en `/opt/lampp/`), se recomienda crear un enlace simbólico o mover la carpeta:

```bash
# Ejemplo para crear un enlace simbólico (recomendado en Linux):
sudo ln -s /ruta/a/tu/proyecto/SENA_SGPD /opt/lampp/htdocs/SENA_SGPD
```
*Asegúrate de tener los servicios de Apache y MySQL encendidos en el panel de XAMPP.*

### Paso 2: Base de Datos
1. Abre tu navegador y dirígete a [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Crea una nueva base de datos llamada `sistema_sena` (con cotejamiento `utf8mb4_general_ci`).
3. Importa el archivo SQL proporcionado en el proyecto: selecciona la base de datos `sistema_sena`, ve a la pestaña **Importar** y sube el archivo `SGPD_SENA.sql`.
*(Si decides usar otro nombre de base de datos o tienes contraseña de root, modifícalo en `config/database.php`)*.

### Paso 3: Instalar dependencias de PHP
Abre una terminal en la carpeta del proyecto (`SENA_SGPD`) y ejecuta:
```bash
composer install
```
Esto descargará las librerías necesarias como `PhpSpreadsheet` y `PdfParser`.

### Paso 4: Configurar la IA Local (Ollama)
El asistente del sistema utiliza un modelo de IA corriendo localmente a través de Ollama.
1. Instala [Ollama](https://ollama.com/) si no lo has hecho.
2. Abre una terminal y descarga/ejecuta el modelo requerido:
```bash
ollama run qwen3:8b
```
Deja esta terminal corriendo, o asegúrate de que el servicio de Ollama esté activo en el puerto `11434`.

### Paso 5: Configurar el Servidor de Voz (TTS - Python)
Para que SENA-IA pueda "hablar", necesitas correr el microservicio en Python.
1. Abre una nueva terminal en la carpeta `tts_server`.
2. (Opcional pero recomendado) Crea y activa un entorno virtual:
```bash
python3 -m venv venv
source venv/bin/activate  # En Linux/Mac
# venv\Scripts\activate   # En Windows
```
3. Instala los requerimientos:
```bash
pip install -r requirements.txt
```
4. **Importante**: Asegúrate de tener un archivo de audio de referencia `.wav` corto de la voz que deseas clonar, y guárdalo como `tts_server/reference_voice/voice.wav`.
5. Ejecuta el servidor Flask:
```bash
python server.py
```
*(El servidor quedará corriendo en `http://127.0.0.1:5050`. Ten en cuenta que la primera vez que se ejecute, descargará el modelo Coqui XTTS v2, lo cual puede tardar unos minutos).*

### Paso 6: Ejecutar el Proyecto
Con Apache, MySQL, Ollama y el servidor TTS en ejecución, abre tu navegador web y dirígete a:
**[http://localhost/SENA_SGPD](http://localhost/SENA_SGPD)**

¡Listo! Ya deberías ver el Dashboard principal del sistema.

---

## 📁 Estructura del Proyecto

- `app/` - Controladores, modelos y lógica de negocio.
- `config/` - Archivos de configuración (BD, Ollama, TTS).
- `core/` - Clases base y enrutador principal (`Router.php`).
- `public/` - Activos públicos como CSS, JS e imágenes.
- `tts_server/` - Microservicio Python para síntesis de voz clonada.
- `views/` - Plantillas y vistas de la interfaz de usuario.
- `index.php` - Controlador frontal y entrada de la aplicación.

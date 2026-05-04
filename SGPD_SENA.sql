CREATE DATABASE IF NOT EXISTS sistema_sena;
USE sistema_sena;

CREATE TABLE funcionario (
  id_funcionario INT AUTO_INCREMENT PRIMARY KEY,
  ti_documento ENUM('CC','TI','CE','PA','PEP') NOT NULL,
  nu_documento VARCHAR(20) UNIQUE,
  nombre VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE programa (
  id_programa INT AUTO_INCREMENT PRIMARY KEY,
  codigo_programa VARCHAR(50) UNIQUE,
  nombre_programa VARCHAR(255),
  modalidad ENUM('PRESENCIAL', 'VIRTUAL', 'A DISTANCIA')
) ENGINE=InnoDB;

CREATE TABLE proyecto_formativo (
  id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
  codigo_proyecto VARCHAR(50) UNIQUE,
  nombre_proyecto VARCHAR(255),
  ruta_pdf VARCHAR(255),
  id_programa INT,
  FOREIGN KEY (id_programa) REFERENCES programa(id_programa) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE fase (
  id_fase INT AUTO_INCREMENT PRIMARY KEY,
  nombre_fase VARCHAR(100),
  id_proyecto INT,
  FOREIGN KEY (id_proyecto) REFERENCES proyecto_formativo(id_proyecto) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE actividad (
  id_actividad INT AUTO_INCREMENT PRIMARY KEY,
  cod_actividad VARCHAR(50),
  nombre_actividad VARCHAR(255),
  id_fase INT,
  FOREIGN KEY (id_fase) REFERENCES fase(id_fase) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE competencia (
  id_competencia INT AUTO_INCREMENT PRIMARY KEY,
  cod_competencia VARCHAR(50) UNIQUE,
  nombre VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE programa_competencia (
  id_programa INT,
  id_competencia INT,
  PRIMARY KEY (id_programa, id_competencia),
  FOREIGN KEY (id_programa) REFERENCES programa(id_programa) ON DELETE CASCADE,
  FOREIGN KEY (id_competencia) REFERENCES competencia(id_competencia) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ficha (
  id_ficha INT AUTO_INCREMENT PRIMARY KEY,
  nu_ficha VARCHAR(50) UNIQUE,
  estado ENUM('EN EJECUCION', 'TERMINADA', 'CANCELADA'),
  fecha_inicio DATE,
  fecha_fin DATE,
  id_programa INT,
  id_proyecto INT,
  FOREIGN KEY (id_programa) REFERENCES programa(id_programa) ON DELETE SET NULL,
  FOREIGN KEY (id_proyecto) REFERENCES proyecto_formativo(id_proyecto) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE aprendiz (
  id_aprendiz INT AUTO_INCREMENT PRIMARY KEY,
  ti_documento ENUM('CC','TI','CE','PA','PEP') NOT NULL,
  nu_documento VARCHAR(20) UNIQUE,
  nombre VARCHAR(100),
  apellido VARCHAR(100),
  estado ENUM('EN FORMACION','RETIRADO','CANCELADO','EGRESADO'),
  id_ficha INT,
  FOREIGN KEY (id_ficha) REFERENCES ficha(id_ficha) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE resultado_aprendizaje (
  id_resultado INT AUTO_INCREMENT PRIMARY KEY,
  cod_resultado VARCHAR(50) UNIQUE,
  nombre_resultado TEXT,
  id_competencia INT,
  FOREIGN KEY (id_competencia) REFERENCES competencia(id_competencia) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE calificacion (
  id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_aprendiz INT,
  id_resultado INT,
  id_funcionario INT,
  jui_evaluativo ENUM('APROBADO', 'POR EVALUAR', 'NO APROBADO'),
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_juicio (id_aprendiz, id_resultado),
  FOREIGN KEY (id_aprendiz) REFERENCES aprendiz(id_aprendiz) ON DELETE CASCADE,
  FOREIGN KEY (id_resultado) REFERENCES resultado_aprendizaje(id_resultado) ON DELETE CASCADE,
  FOREIGN KEY (id_funcionario) REFERENCES funcionario(id_funcionario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE actividad_resultado (
  id_actividad INT,
  id_resultado INT,
  PRIMARY KEY (id_actividad, id_resultado),
  FOREIGN KEY (id_actividad) REFERENCES actividad(id_actividad) ON DELETE CASCADE,
  FOREIGN KEY (id_resultado) REFERENCES resultado_aprendizaje(id_resultado) ON DELETE CASCADE
) ENGINE=InnoDB;
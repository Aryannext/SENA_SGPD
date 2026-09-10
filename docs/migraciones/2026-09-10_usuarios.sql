-- ---------------------------------------------------------------------------
-- Migración · 10 de septiembre de 2026
-- Autenticación de usuarios (RF-40, RF-41 · RNF-06)
--
-- Aplicar sobre instalaciones existentes. Las bases creadas desde cero con
-- docs/SGPD_SENA.sql ya incluyen esta tabla.
--
--   mysql -u root -p sistema_sena < docs/migraciones/2026-09-10_usuarios.sql
--
-- Después, crear el primer usuario desde la terminal:
--   php tools/crear-usuario.php admin ADMIN
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS usuario (
  id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
  usuario        VARCHAR(50)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  nombre         VARCHAR(150) NOT NULL,
  rol            ENUM('ADMIN','COORDINADOR','INSTRUCTOR') NOT NULL DEFAULT 'INSTRUCTOR',
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  id_funcionario INT          NULL,
  creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso  DATETIME     NULL,
  FOREIGN KEY (id_funcionario) REFERENCES funcionario(id_funcionario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- RN-04 · Todo aprendiz debe tener un estado. La obligatoriedad se perdió al
-- migrar la columna de ENUM a VARCHAR; se restablece aquí.
UPDATE aprendiz SET estado = 'EN FORMACION' WHERE estado IS NULL OR estado = '';
ALTER TABLE aprendiz MODIFY estado VARCHAR(50) NOT NULL DEFAULT 'EN FORMACION';

-- RN-09 · Un programa tiene, como máximo, un proyecto formativo. Estaba
-- validado solo en PHP, así que otra vía de escritura podía saltárselo.
ALTER TABLE proyecto_formativo ADD UNIQUE KEY uk_proyecto_programa (id_programa);

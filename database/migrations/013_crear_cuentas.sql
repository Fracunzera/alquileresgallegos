-- Cuentas de anunciantes (particulares e inmobiliarias). Ingreso sin contraseña, con link por email.
CREATE TABLE cuentas (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email                   VARCHAR(160) NOT NULL,
  nombre                  VARCHAR(120) NOT NULL,
  whatsapp                VARCHAR(20)  NULL,
  telefono                VARCHAR(30)  NULL,
  tipo                    ENUM('particular','inmobiliaria') NOT NULL DEFAULT 'particular',
  inmobiliaria_id         SMALLINT UNSIGNED NULL COMMENT 'Se vincula desde el panel; hasta entonces no puede publicar',
  inmobiliaria_solicitada VARCHAR(120) NULL COMMENT 'Nombre de la inmobiliaria que indicó al registrarse',
  estado                  ENUM('activa','suspendida') NOT NULL DEFAULT 'activa',
  confiable               TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = publica sin pasar por moderación',
  email_confirmado_en     DATETIME NULL COMMENT 'Primer ingreso con el link del email',
  ultimo_login            DATETIME NULL,
  ip_registro             VARBINARY(16) NULL,
  creado_en               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cuentas_email (email),
  CONSTRAINT fk_cuentas_inmobiliaria FOREIGN KEY (inmobiliaria_id) REFERENCES inmobiliarias (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cuenta_tokens (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cuenta_id  INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL COMMENT 'SHA-256 del token del link de ingreso (un solo uso)',
  vence_en   DATETIME NOT NULL,
  usado_en   DATETIME NULL,
  creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cuenta_tokens_hash (token_hash),
  CONSTRAINT fk_cuenta_tokens_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE avisos
  MODIFY origen ENUM('admin','formulario','cuenta') NOT NULL,
  ADD COLUMN cuenta_id INT UNSIGNED NULL AFTER inmobiliaria_id,
  ADD COLUMN cambios_sin_revisar_en DATETIME NULL COMMENT 'Editado por una cuenta no confiable estando publicado' AFTER motivo_estado,
  ADD CONSTRAINT fk_avisos_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id) ON DELETE SET NULL,
  ADD KEY idx_avisos_cuenta (cuenta_id, estado);

CREATE TABLE inmobiliarias (
  id             SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(120) NOT NULL,
  slug           VARCHAR(130) NOT NULL,
  logo           VARCHAR(255) NULL,
  descripcion    TEXT NULL,
  telefono       VARCHAR(30)  NULL,
  whatsapp       VARCHAR(20)  NULL COMMENT 'Solo dígitos, formato internacional: 5492966xxxxxx',
  email          VARCHAR(160) NULL,
  web            VARCHAR(255) NULL,
  direccion      VARCHAR(160) NULL,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inmobiliarias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

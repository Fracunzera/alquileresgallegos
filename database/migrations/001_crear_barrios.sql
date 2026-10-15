CREATE TABLE barrios (
  id               SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(80)  NOT NULL,
  slug             VARCHAR(90)  NOT NULL,
  intro            TEXT NULL COMMENT 'Párrafo propio de la landing del barrio',
  meta_description VARCHAR(160) NULL COMMENT 'Si es NULL se genera desde los datos',
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  creado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_barrios_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

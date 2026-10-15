CREATE TABLE aviso_fotos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aviso_id  INT UNSIGNED NOT NULL,
  archivo   VARCHAR(255) NOT NULL COMMENT 'Ruta base; existen {archivo}-1200.webp y {archivo}-400.webp',
  ancho     SMALLINT UNSIGNED NOT NULL,
  alto      SMALLINT UNSIGNED NOT NULL,
  orden     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  alt       VARCHAR(200) NULL COMMENT 'Si es NULL se genera desde los datos del aviso',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fotos_aviso FOREIGN KEY (aviso_id) REFERENCES avisos (id) ON DELETE CASCADE,
  KEY idx_fotos_aviso_orden (aviso_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

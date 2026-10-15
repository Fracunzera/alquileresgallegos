CREATE TABLE denuncias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aviso_id    INT UNSIGNED NOT NULL,
  motivo      ENUM('alquilado','falso','mi_numero','datos','otro') NOT NULL,
  detalle     VARCHAR(500) NULL,
  email       VARCHAR(160) NULL,
  ip          VARBINARY(16) NULL,
  estado      ENUM('abierta','resuelta','descartada') NOT NULL DEFAULT 'abierta',
  resuelta_en DATETIME NULL,
  admin_id    SMALLINT UNSIGNED NULL,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_denuncias_aviso FOREIGN KEY (aviso_id) REFERENCES avisos (id) ON DELETE CASCADE,
  CONSTRAINT fk_denuncias_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL,
  KEY idx_denuncias_estado (estado, aviso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE verificaciones
  MODIFY canal ENUM('manual','whatsapp','email','link','cron','denuncia') NOT NULL;

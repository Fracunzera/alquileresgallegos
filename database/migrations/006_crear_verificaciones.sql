CREATE TABLE verificaciones (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aviso_id  INT UNSIGNED NOT NULL,
  accion    ENUM('solicitada','confirmada','alquilado','pausado','despublicado_sin_respuesta') NOT NULL,
  canal     ENUM('manual','whatsapp','email','link','cron') NOT NULL,
  nota      VARCHAR(255) NULL,
  admin_id  SMALLINT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_verif_aviso FOREIGN KEY (aviso_id) REFERENCES avisos (id) ON DELETE CASCADE,
  CONSTRAINT fk_verif_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL,
  KEY idx_verif_aviso (aviso_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

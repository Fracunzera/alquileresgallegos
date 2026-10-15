CREATE TABLE bloqueos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo      ENUM('whatsapp','email','ip') NOT NULL,
  valor     VARCHAR(160) NOT NULL COMMENT 'WhatsApp normalizado (549…), email en minúsculas o IP',
  motivo    VARCHAR(255) NULL,
  vence_en  DATETIME NULL COMMENT 'NULL = permanente. Las IP vencen: en celulares muchos usuarios comparten IP (CGNAT)',
  aviso_id  INT UNSIGNED NULL COMMENT 'Aviso que originó el bloqueo (sin FK: el registro sobrevive si se borra el aviso)',
  admin_id  SMALLINT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bloqueos_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL,
  UNIQUE KEY uq_bloqueos (tipo, valor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

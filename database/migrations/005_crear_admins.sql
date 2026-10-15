CREATE TABLE admins (
  id            SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(160) NOT NULL,
  nombre        VARCHAR(80)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  ultimo_login  DATETIME NULL,
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

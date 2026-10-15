CREATE TABLE rate_limits (
  clave       VARCHAR(100) NOT NULL PRIMARY KEY COMMENT 'Ej: login:<ip>, publicar:<ip>',
  intentos    SMALLINT UNSIGNED NOT NULL,
  ventana_fin DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

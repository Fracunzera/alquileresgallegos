-- Totales por aviso y por día. Se cuentan con navigator.sendBeacon desde la ficha (sin bots).
CREATE TABLE aviso_estadisticas (
  aviso_id INT UNSIGNED NOT NULL,
  fecha    DATE NOT NULL,
  vistas   INT UNSIGNED NOT NULL DEFAULT 0,
  whatsapp INT UNSIGNED NOT NULL DEFAULT 0,
  telefono INT UNSIGNED NOT NULL DEFAULT 0,
  email    INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (aviso_id, fecha),
  CONSTRAINT fk_estadisticas_aviso FOREIGN KEY (aviso_id) REFERENCES avisos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

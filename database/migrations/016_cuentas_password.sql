-- Ingreso con email y contraseña. NULL = cuenta creada antes, que todavía no eligió contraseña
-- (entra con "¿Olvidaste tu contraseña?" y la crea desde Mis datos).
ALTER TABLE cuentas
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER email;

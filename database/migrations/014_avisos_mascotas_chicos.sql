-- Si se aceptan mascotas y chicos. NULL = el aviso no lo informa.
ALTER TABLE avisos
  ADD COLUMN acepta_mascotas ENUM('si','no','consultar') NULL AFTER banos,
  ADD COLUMN acepta_chicos   ENUM('si','no','consultar') NULL AFTER acepta_mascotas,
  ADD KEY idx_avisos_mascotas (estado, acepta_mascotas);

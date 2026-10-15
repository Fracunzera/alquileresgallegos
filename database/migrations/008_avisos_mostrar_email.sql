-- El email de /publicar es obligatorio (para avisar y verificar), pero el anunciante puede elegir no mostrarlo.
ALTER TABLE avisos
  ADD COLUMN mostrar_email TINYINT(1) NOT NULL DEFAULT 1 AFTER contacto_email;

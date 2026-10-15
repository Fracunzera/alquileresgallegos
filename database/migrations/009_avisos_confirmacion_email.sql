-- Los avisos de /publicar entran a moderación recién cuando confirman el email.
ALTER TABLE avisos
  ADD COLUMN email_confirmado_en DATETIME NULL AFTER mostrar_email,
  ADD COLUMN token_confirmacion CHAR(64) NULL COMMENT 'SHA-256 del token del link de confirmación' AFTER email_confirmado_en;

-- Lo que ya estaba en moderación antes de este cambio no se pierde.
UPDATE avisos SET email_confirmado_en = creado_en WHERE origen = 'formulario';

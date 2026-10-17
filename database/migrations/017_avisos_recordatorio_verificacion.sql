-- Recordatorio antes de pausar un aviso que no respondió el "¿sigue disponible?".
ALTER TABLE avisos
  ADD COLUMN recordatorio_enviado_en DATETIME NULL AFTER verificacion_solicitada_en;

ALTER TABLE verificaciones
  MODIFY accion ENUM('solicitada','recordatorio','confirmada','alquilado','pausado','despublicado_sin_respuesta') NOT NULL;

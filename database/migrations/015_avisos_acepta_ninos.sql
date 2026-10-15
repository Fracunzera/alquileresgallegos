-- "chicos" era muy informal: el campo pasa a llamarse acepta_ninos (mismos valores).
ALTER TABLE avisos
  CHANGE acepta_chicos acepta_ninos ENUM('si','no','consultar') NULL;

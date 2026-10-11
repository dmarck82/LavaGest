CREATE TRIGGER IF NOT EXISTS validar_usu_status_insert
BEFORE INSERT ON usu_usuario
WHEN NEW.usu_status NOT IN ('ativo', 'inativo')
BEGIN
    SELECT RAISE(ABORT, 'Status deve ser ativo ou inativo.');
END;

CREATE TRIGGER IF NOT EXISTS validar_usu_status_update
BEFORE UPDATE OF usu_status ON usu_usuario
WHEN NEW.usu_status NOT IN ('ativo', 'inativo')
BEGIN
    SELECT RAISE(ABORT, 'Status deve ser ativo ou inativo.');
END;

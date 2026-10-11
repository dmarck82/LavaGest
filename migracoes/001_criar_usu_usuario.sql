CREATE TABLE
    IF NOT EXISTS usu_usuario (
        usu_id INTEGER PRIMARY KEY AUTOINCREMENT,
        usu_nome TEXT NOT NULL,
        usu_email TEXT NOT NULL UNIQUE,
        usu_senha TEXT NOT NULL,
        usu_telefone TEXT,
        usu_status TEXT NOT NULL DEFAULT 'ativo' CHECK (usu_status IN ('ativo', 'inativo')),
        usu_ultimo_login TEXT DEFAULT NULL
    );
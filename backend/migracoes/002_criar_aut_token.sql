CREATE TABLE
    IF NOT EXISTS aut_token (
        aut_id INTEGER PRIMARY KEY AUTOINCREMENT,
        usu_id INTEGER NOT NULL,
        aut_token_hash TEXT NOT NULL UNIQUE,
        aut_expira_em TEXT NOT NULL,
        aut_criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usu_id) REFERENCES usu_usuario (usu_id) ON DELETE CASCADE
    );
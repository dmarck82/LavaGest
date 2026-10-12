CREATE TABLE
    IF NOT EXISTS cli_cliente (
        cli_id INTEGER PRIMARY KEY AUTOINCREMENT,
        cli_nome TEXT NOT NULL,
        cli_telefone TEXT DEFAULT NULL,
        cli_whatsapp TEXT NOT NULL UNIQUE,
        cli_email TEXT DEFAULT NULL,
        cli_uf TEXT DEFAULT NULL,
        cli_cidade TEXT DEFAULT NULL,
        cli_endereco TEXT DEFAULT NULL,
        cli_bairro TEXT DEFAULT NULL,
        cli_cep TEXT DEFAULT NULL,
        cli_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
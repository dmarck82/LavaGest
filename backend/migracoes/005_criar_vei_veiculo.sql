CREATE TABLE IF NOT EXISTS vei_veiculo (
 vei_id INTEGER PRIMARY KEY AUTOINCREMENT,
 vei_cliente_id INTEGER NOT NULL,
 vei_placa TEXT NOT NULL UNIQUE,
 vei_marca TEXT DEFAULT NULL,
 vei_modelo TEXT NOT NULL,
 vei_cor TEXT DEFAULT NULL,
 vei_ano_fabricacao INTEGER DEFAULT NULL,
 vei_categoria TEXT NOT NULL,
 vei_observacoes TEXT DEFAULT NULL,
 vei_criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (vei_cliente_id) REFERENCES cli_cliente(cli_id)
);
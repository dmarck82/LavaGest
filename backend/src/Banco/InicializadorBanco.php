<?php

declare(strict_types=1);

namespace LavaGest\Banco;

use PDO;
use RuntimeException;
use Throwable;

class InicializadorBanco
{
    public static function executar(PDO $conexao): int
    {
        $arquivosSql = glob(dirname(__DIR__, 2) . '/migracoes/*.sql');

        if ($arquivosSql === false) {
            throw new RuntimeException('Não foi possível localizar as migrações.');
        }

        // Os nomes dos arquivos definem a ordem: 001, 002, 003...
        sort($arquivosSql);
        $conexao->exec(
            'CREATE TABLE IF NOT EXISTS migracoes (
                nome TEXT PRIMARY KEY,
                aplicada_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $aplicadas = 0;

        foreach ($arquivosSql as $arquivoSql) {
            $nome = basename($arquivoSql);
            $consulta = $conexao->prepare('SELECT 1 FROM migracoes WHERE nome = :nome');
            $consulta->execute([':nome' => $nome]);

            $jaAplicada = $consulta->fetchColumn();

            if ($jaAplicada !== false) {
                continue;
            }

            $sql = file_get_contents($arquivoSql);

            if ($sql === false) {
                throw new RuntimeException('Não foi possível ler a migração: ' . $nome);
            }

            // A transação confirma o SQL e seu registro juntos. Se falhar, desfaz ambos.
            $conexao->beginTransaction();

            try {
                $conexao->exec($sql);
                $registro = $conexao->prepare('INSERT INTO migracoes (nome) VALUES (:nome)');
                $registro->execute([':nome' => $nome]);
                $conexao->commit();
                $aplicadas++;
            } catch (Throwable $erro) {
                $conexao->rollBack();
                throw $erro;
            }
        }

        return $aplicadas;
    }
}

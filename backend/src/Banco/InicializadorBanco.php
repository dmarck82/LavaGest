<?php

declare(strict_types=1);

namespace LavaGest\Banco;

use PDO;
use RuntimeException;

class InicializadorBanco
{
    public static function executar(PDO $conexao): void
    {
        $arquivosSql = glob(
            dirname(__DIR__, 2) . '/migracoes/*.sql'
        );

        if ($arquivosSql === false) {
            throw new RuntimeException(
                'Não foi possível localizar as migrações.'
            );
        }

        sort($arquivosSql);

        foreach ($arquivosSql as $arquivoSql) {
            $sql = file_get_contents($arquivoSql);

            if ($sql === false) {
                throw new RuntimeException(
                    'Não foi possível ler uma migração.'
                );
            }

            $conexao->exec($sql);
        }
    }
}

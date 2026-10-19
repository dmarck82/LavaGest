<?php

declare(strict_types=1);

namespace LavaGest\Banco;

use LavaGest\Configuracao;
use PDO;
use RuntimeException;

class Conexao
{
    public static function criar(?string $caminho = null): PDO
    {
        if ($caminho === null) {
            $caminho = Configuracao::caminhoBanco();
        }

        // ":memory:" cria um SQLite temporário na memória, útil para testes.
        if ($caminho !== ':memory:') {
            $diretorio = dirname($caminho);

            if (!is_dir($diretorio)) {
                $diretorioCriado = mkdir($diretorio, 0775, true);

                if (!$diretorioCriado && !is_dir($diretorio)) {
                    throw new RuntimeException('Não foi possível criar o diretório do banco.');
                }
            }
        }

        $enderecoBanco = 'sqlite:' . $caminho;
        $conexao = new PDO($enderecoBanco);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ativa as chaves estrangeiras e dá tempo para aguardar um banco ocupado.
        $conexao->exec('PRAGMA foreign_keys = ON');
        $conexao->exec('PRAGMA busy_timeout = 5000');

        return $conexao;
    }
}

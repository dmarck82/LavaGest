<?php

declare(strict_types=1);

namespace LavaGest\Banco;

use PDO;

class Conexao
{
    public static function criar(): PDO
    {
        $caminhoBanco = dirname(__DIR__, 2) . '/dados/banco.sqlite';

        $conexao = new PDO('sqlite:' . $caminhoBanco);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conexao;
    }
}

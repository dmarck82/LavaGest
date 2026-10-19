<?php

declare(strict_types=1);

namespace LavaGest;

// Lê configurações do ambiente ou usa os valores do desenvolvimento local.
class Configuracao
{
    public static function caminhoBanco(): string
    {
        $caminho = getenv('LAVAGEST_DB_PATH');

        if (!$caminho) {
            $caminho = dirname(__DIR__) . '/dados/banco.sqlite';
        }

        return $caminho;
    }

    public static function origemCors(): string
    {
        $origem = getenv('LAVAGEST_CORS_ORIGIN');

        if (!$origem) {
            $origem = 'http://localhost:5500';
        }

        return $origem;
    }
}

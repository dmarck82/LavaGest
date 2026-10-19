<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use LavaGest\Banco\Conexao;
use LavaGest\Banco\InicializadorBanco;

try {
    $aplicadas = InicializadorBanco::executar(Conexao::criar());
    echo $aplicadas . " migração(ões) aplicada(s)." . PHP_EOL;
} catch (Throwable $erro) {
    fwrite(STDERR, $erro->getMessage() . PHP_EOL);
    exit(1);
}

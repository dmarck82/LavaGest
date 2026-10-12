<?php

declare(strict_types=1);

use LavaGest\Controladores\AutenticacaoControlador;

function processarRotasAutenticacao(
    string $metodo,
    string $caminho,
    AutenticacaoControlador $controlador
): bool {
    if ($metodo !== 'POST' || $caminho !== '/autenticacao') {
        return false;
    }

    $dados = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    echo json_encode($controlador->autenticar($dados));

    return true;
}

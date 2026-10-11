<?php

declare(strict_types=1);

require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/autenticacao.php';

function processarRotas(
    string $metodo,
    string $caminho,
    UsuarioControlador $usuarioControlador,
    AutenticacaoControlador $autenticacaoControlador
): void {
    if (processarRotasUsuarios(
        $metodo,
        $caminho,
        $usuarioControlador
    )) {
        return;
    }

    if (processarRotasAutenticacao(
        $metodo,
        $caminho,
        $autenticacaoControlador
    )) {
        return;
    }

    http_response_code(404);

    echo json_encode([
        'erro' => 'Rota não encontrada.'
    ]);
}

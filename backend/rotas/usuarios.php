<?php

declare(strict_types=1);

use LavaGest\Controladores\UsuarioControlador;
use LavaGest\Seguranca\Autenticador;

function processarRotasUsuarios(
    string $metodo,
    string $caminho,
    UsuarioControlador $controlador,
    Autenticador $autenticador
): bool {
    $partes = explode('/', trim($caminho, '/'));

    $ehRotaComId = (
        count($partes) === 2
        && $partes[0] === 'usuarios'
        && ctype_digit($partes[1])
    );

    if ($metodo === 'POST' && $caminho === '/usuarios') {
        $dados = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        http_response_code(201);

        echo json_encode(
            $controlador->criarUsuario($dados)
        );

        return true;
    }

    if ($partes[0] === 'usuarios') {
        $autenticador->autenticar();
    }

    if ($metodo === 'GET' && $caminho === '/usuarios') {
        echo json_encode($controlador->listarUsuarios());

        return true;
    }

    if ($metodo === 'GET' && $ehRotaComId) {
        echo json_encode(
            $controlador->buscarUsuario((int) $partes[1])
        );

        return true;
    }

    if ($metodo === 'PUT' && $ehRotaComId) {
        $dados = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        echo json_encode(
            $controlador->atualizarUsuario((int) $partes[1], $dados)
        );

        return true;
    }

    if ($metodo === 'DELETE' && $ehRotaComId) {
        $controlador->deletarUsuario((int) $partes[1]);

        http_response_code(204);

        return true;
    }

    return false;
}

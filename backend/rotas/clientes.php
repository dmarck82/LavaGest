<?php

declare(strict_types=1);

use LavaGest\Controladores\ClienteControlador;
use LavaGest\Seguranca\Autenticador;

function processarRotasClientes(
    string $metodo,
    string $caminho,
    ClienteControlador $controlador,
    Autenticador $autenticador
): bool {
    $partes = explode('/', trim($caminho, '/'));

    $ehRotaComId = (
        count($partes) === 2
        && $partes[0] === 'clientes'
        && ctype_digit($partes[1])
    );

    if (($partes[0] ?? '') !== 'clientes') {
        return false;
    }

    $autenticador->autenticar();

    if ($metodo === 'GET' && $caminho === '/clientes') {
        echo json_encode($controlador->listarClientes());

        return true;
    }

    if ($metodo === 'POST' && $caminho === '/clientes') {
        $dados = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        http_response_code(201);

        echo json_encode($controlador->criarCliente($dados));

        return true;
    }

    if ($metodo === 'GET' && $ehRotaComId) {
        echo json_encode(
            $controlador->buscarCliente((int) $partes[1])
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
            $controlador->atualizarCliente((int) $partes[1], $dados)
        );

        return true;
    }

    if ($metodo === 'DELETE' && $ehRotaComId) {
        $controlador->deletarCliente((int) $partes[1]);

        http_response_code(204);

        return true;
    }

    return false;
}

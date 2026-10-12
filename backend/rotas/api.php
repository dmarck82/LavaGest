<?php

declare(strict_types=1);

use LavaGest\Controladores\AutenticacaoControlador;
use LavaGest\Controladores\ClienteControlador;
use LavaGest\Controladores\UsuarioControlador;
use LavaGest\Controladores\VeiculoControlador;
use LavaGest\Seguranca\Autenticador;

require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/clientes.php';
require_once __DIR__ . '/veiculos.php';

function processarRotas(
    string $metodo,
    string $caminho,
    UsuarioControlador $usuarioControlador,
    ClienteControlador $clienteControlador,
    VeiculoControlador $veiculoControlador,
    AutenticacaoControlador $autenticacaoControlador,
    Autenticador $autenticador
): void {
    if (processarRotasUsuarios(
        $metodo,
        $caminho,
        $usuarioControlador,
        $autenticador
    )) {
        return;
    }

    if (processarRotasClientes(
        $metodo,
        $caminho,
        $clienteControlador,
        $autenticador
    )) {
        return;
    }

    if (processarRotasVeiculos($metodo, $caminho, $veiculoControlador, $autenticador)) {
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

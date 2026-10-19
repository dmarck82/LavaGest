<?php

declare(strict_types=1);

use LavaGest\Controladores\AutenticacaoControlador;
use LavaGest\Controladores\ClienteControlador;
use LavaGest\Controladores\UsuarioControlador;
use LavaGest\Controladores\VeiculoControlador;
use LavaGest\Http\Roteador;

require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/clientes.php';
require_once __DIR__ . '/veiculos.php';
require_once __DIR__ . '/autenticacao.php';

// Este é o ponto de encontro dos arquivos de rotas.
function registrarRotasApi(
    Roteador $roteador,
    UsuarioControlador $usuarioControlador,
    ClienteControlador $clienteControlador,
    VeiculoControlador $veiculoControlador,
    AutenticacaoControlador $autenticacaoControlador
): void {
    registrarRotasUsuarios($roteador, $usuarioControlador);
    registrarRotasClientes($roteador, $clienteControlador);
    registrarRotasVeiculos($roteador, $veiculoControlador);
    registrarRotasAutenticacao($roteador, $autenticacaoControlador);
}

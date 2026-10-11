<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Banco/Conexao.php';
require_once __DIR__ . '/../src/Banco/InicializadorBanco.php';
require_once __DIR__ . '/../src/Modelos/Usuario.php';
require_once __DIR__ . '/../src/Repositorios/UsuarioRepositorio.php';
require_once __DIR__ . '/../src/Controladores/UsuarioControlador.php';
require_once __DIR__ . '/../rotas/api.php';
require_once __DIR__ . '/../src/Excecoes/CredenciaisInvalidasException.php';
require_once __DIR__ . '/../src/Repositorios/TokenRepositorio.php';
require_once __DIR__ . '/../src/Controladores/AutenticacaoControlador.php';


header('Content-Type: application/json; charset=utf-8');

$conexao = Conexao::criar();

InicializadorBanco::executar($conexao);

$usuarioRepositorio = new UsuarioRepositorio($conexao);
$tokenRepositorio = new TokenRepositorio($conexao);

$usuarioControlador = new UsuarioControlador($usuarioRepositorio);
$autenticacaoControlador = new AutenticacaoControlador($usuarioRepositorio, $tokenRepositorio);

$metodo = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    processarRotas($metodo, $caminho, $usuarioControlador, $autenticacaoControlador);
} catch (JsonException $erro) {
    http_response_code(400);

    echo json_encode([
        'erro' => 'JSON inválido.'
    ]);
} catch (InvalidArgumentException $erro) {
    http_response_code(422);

    echo json_encode([
        'erro' => $erro->getMessage()
    ]);
} catch (CredenciaisInvalidasException $erro) {
    http_response_code(401);

    echo json_encode([
        'erro' => $erro->getMessage()
    ]);
} catch (RuntimeException $erro) {
    http_response_code(404);

    echo json_encode([
        'erro' => $erro->getMessage()
    ]);
}

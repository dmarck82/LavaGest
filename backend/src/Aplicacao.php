<?php

declare(strict_types=1);

namespace LavaGest;

use LavaGest\Banco\Conexao;
use LavaGest\Banco\InicializadorBanco;
use LavaGest\Controladores\AutenticacaoControlador;
use LavaGest\Controladores\UsuarioControlador;
use LavaGest\Http\TratadorDeErros;
use LavaGest\Repositorios\TokenRepositorio;
use LavaGest\Repositorios\UsuarioRepositorio;
use LavaGest\Seguranca\Autenticador;

class Aplicacao
{
    public function executar(): void
    {
        $this->configurarCabecalhos();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);

            return;
        }

        $tratadorDeErros = new TratadorDeErros();

        $tratadorDeErros->executar(
            fn() => $this->processarRequisicao()
        );
    }

    private function configurarCabecalhos(): void
    {
        header(
            'Access-Control-Allow-Origin: http://localhost:5500'
        );

        header(
            'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'
        );

        header(
            'Access-Control-Allow-Headers: Content-Type, Authorization'
        );

        header('Content-Type: application/json; charset=utf-8');
    }

    private function processarRequisicao(): void
    {
        require_once dirname(__DIR__) . '/rotas/api.php';

        $conexao = Conexao::criar();

        InicializadorBanco::executar($conexao);

        $usuarioRepositorio = new UsuarioRepositorio($conexao);
        $tokenRepositorio = new TokenRepositorio($conexao);

        $autenticador = new Autenticador($tokenRepositorio);

        $usuarioControlador = new UsuarioControlador(
            $usuarioRepositorio
        );

        $autenticacaoControlador = new AutenticacaoControlador(
            $usuarioRepositorio,
            $tokenRepositorio
        );

        $metodo = $_SERVER['REQUEST_METHOD'];

        $caminho = parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        ) ?: '/';

        \processarRotas(
            $metodo,
            $caminho,
            $usuarioControlador,
            $autenticacaoControlador,
            $autenticador
        );
    }
}

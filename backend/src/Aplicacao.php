<?php

declare(strict_types=1);

namespace LavaGest;

use LavaGest\Banco\Conexao;
use LavaGest\Controladores\AutenticacaoControlador;
use LavaGest\Controladores\ClienteControlador;
use LavaGest\Controladores\UsuarioControlador;
use LavaGest\Controladores\VeiculoControlador;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Http\Roteador;
use LavaGest\Http\TratadorDeErros;
use LavaGest\Repositorios\ClienteRepositorio;
use LavaGest\Repositorios\TokenRepositorio;
use LavaGest\Repositorios\UsuarioRepositorio;
use LavaGest\Repositorios\VeiculoRepositorio;
use LavaGest\Seguranca\Autenticador;
use LavaGest\Servicos\AutenticacaoServico;
use LavaGest\Servicos\ClienteServico;
use LavaGest\Servicos\UsuarioServico;
use LavaGest\Servicos\VeiculoServico;

// Monta os objetos e inicia o atendimento de uma requisição.
class Aplicacao
{
    public function executar(): void
    {
        $this->configurarCabecalhos();

        $tratadorDeErros = new TratadorDeErros();

        // Passamos uma função para o tratador executar dentro de seu try/catch.
        $operacao = function (): RespostaJson {
            return $this->processarRequisicao();
        };

        $resposta = $tratadorDeErros->executar($operacao);
        $resposta->enviar();
    }

    private function configurarCabecalhos(): void
    {
        $origemPermitida = Configuracao::origemCors();

        header('Access-Control-Allow-Origin: ' . $origemPermitida);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    private function processarRequisicao(): RespostaJson
    {
        $requisicao = Requisicao::capturar();

        if ($requisicao->getMetodo() === 'OPTIONS') {
            return new RespostaJson(null, 204);
        }

        $roteador = $this->criarRoteador();
        $resposta = $roteador->despachar($requisicao);

        return $resposta;
    }

    private function criarRoteador(): Roteador
    {
        $conexao = Conexao::criar();

        // 1. Repositórios: cada um recebe a mesma conexão com o banco.
        $clienteRepositorio = new ClienteRepositorio($conexao);
        $usuarioRepositorio = new UsuarioRepositorio($conexao);
        $tokenRepositorio = new TokenRepositorio($conexao);
        $veiculoRepositorio = new VeiculoRepositorio($conexao);

        // 2. Serviços: recebem os repositórios de que precisam.
        $clienteServico = new ClienteServico($clienteRepositorio, $veiculoRepositorio);
        $usuarioServico = new UsuarioServico($usuarioRepositorio);
        $veiculoServico = new VeiculoServico($veiculoRepositorio, $clienteRepositorio);
        $autenticacaoServico = new AutenticacaoServico($usuarioRepositorio, $tokenRepositorio);

        // 3. Controladores: recebem os serviços.
        $clienteControlador = new ClienteControlador($clienteServico);
        $usuarioControlador = new UsuarioControlador($usuarioServico);
        $veiculoControlador = new VeiculoControlador($veiculoServico);
        $autenticacaoControlador = new AutenticacaoControlador($autenticacaoServico);

        // 4. Roteador: recebe o autenticador e as rotas que pode atender.
        $autenticador = new Autenticador($tokenRepositorio);
        $roteador = new Roteador($autenticador);

        require_once dirname(__DIR__) . '/rotas/api.php';

        registrarRotasApi(
            $roteador,
            $usuarioControlador,
            $clienteControlador,
            $veiculoControlador,
            $autenticacaoControlador
        );

        return $roteador;
    }
}

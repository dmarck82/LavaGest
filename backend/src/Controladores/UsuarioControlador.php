<?php

declare(strict_types=1);

namespace LavaGest\Controladores;

use LavaGest\Http\Campos;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Servicos\UsuarioServico;

// Recebe dados do HTTP e devolve uma resposta. As operações ficam no serviço.
class UsuarioControlador
{
    // "?" significa que o campo aceita null. As regras do valor ficam no modelo.
    private const CAMPOS = [
        'nome' => 'texto',
        'email' => 'texto',
        'senha' => 'texto',
        'telefone' => 'texto',
        'status' => 'texto',
    ];

    private UsuarioServico $servico;

    public function __construct(UsuarioServico $servico)
    {
        $this->servico = $servico;
    }

    public function criar(Requisicao $requisicao): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $camposObrigatorios = ['nome', 'email', 'senha', 'telefone'];
        $dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS, $camposObrigatorios);

        $usuario = $this->servico->criar($dadosValidados);
        $dadosResposta = $usuario->paraArray();

        return new RespostaJson($dadosResposta, 201);
    }

    public function atualizar(Requisicao $requisicao, int $id): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS);

        $usuario = $this->servico->atualizar($id, $dadosValidados);
        $dadosResposta = $usuario->paraArray();

        return new RespostaJson($dadosResposta);
    }

    public function buscar(int $id): RespostaJson
    {
        $usuario = $this->servico->buscar($id);
        $dadosResposta = $usuario->paraArray();

        return new RespostaJson($dadosResposta);
    }

    public function listar(): RespostaJson
    {
        $usuarios = $this->servico->listar();
        $dadosResposta = [];

        foreach ($usuarios as $usuario) {
            $dadosResposta[] = $usuario->paraArray();
        }

        return new RespostaJson($dadosResposta);
    }

    public function deletar(int $id): RespostaJson
    {
        $this->servico->deletar($id);

        // 204 significa sucesso sem conteúdo no corpo da resposta.
        return new RespostaJson(null, 204);
    }
}

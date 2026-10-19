<?php

declare(strict_types=1);

namespace LavaGest\Controladores;

use LavaGest\Http\Campos;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Servicos\ClienteServico;

// Recebe dados do HTTP e devolve uma resposta. As operações ficam no serviço.
class ClienteControlador
{
    // "?" significa que o campo aceita null. As regras do valor ficam no modelo.
    private const CAMPOS = [
        'nome' => 'texto',
        'whatsapp' => 'texto',
        'telefone' => '?texto',
        'email' => '?texto',
        'uf' => '?texto',
        'cidade' => '?texto',
        'endereco' => '?texto',
        'bairro' => '?texto',
        'cep' => '?texto',
    ];

    private ClienteServico $servico;

    public function __construct(ClienteServico $servico)
    {
        $this->servico = $servico;
    }

    public function criar(Requisicao $requisicao): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $camposObrigatorios = ['nome', 'whatsapp'];
        $dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS, $camposObrigatorios);

        $cliente = $this->servico->criar($dadosValidados);
        $dadosResposta = $cliente->paraArray();

        return new RespostaJson($dadosResposta, 201);
    }

    public function atualizar(Requisicao $requisicao, int $id): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS);

        $cliente = $this->servico->atualizar($id, $dadosValidados);
        $dadosResposta = $cliente->paraArray();

        return new RespostaJson($dadosResposta);
    }

    public function buscar(int $id): RespostaJson
    {
        $cliente = $this->servico->buscar($id);
        $dadosResposta = $cliente->paraArray();

        return new RespostaJson($dadosResposta);
    }

    public function listar(): RespostaJson
    {
        $clientes = $this->servico->listar();
        $dadosResposta = [];

        foreach ($clientes as $cliente) {
            $dadosResposta[] = $cliente->paraArray();
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

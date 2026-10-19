<?php

declare(strict_types=1);

namespace LavaGest\Controladores;

use LavaGest\Http\Campos;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Servicos\VeiculoServico;

// Recebe dados do HTTP e devolve uma resposta. As operações ficam no serviço.
class VeiculoControlador
{
    // "?" significa que o campo aceita null. As regras do valor ficam no modelo.
    private const CAMPOS = [
        'cliente_id' => 'inteiro',
        'placa' => 'texto',
        'modelo' => 'texto',
        'categoria' => 'texto',
        'marca' => '?texto',
        'cor' => '?texto',
        'ano_fabricacao' => '?inteiro',
        'observacoes' => '?texto',
    ];

    private VeiculoServico $servico;

    public function __construct(VeiculoServico $servico)
    {
        $this->servico = $servico;
    }

    public function criar(Requisicao $requisicao): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $camposObrigatorios = ['cliente_id', 'placa', 'modelo', 'categoria'];
        $dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS, $camposObrigatorios);

        $veiculo = $this->servico->criar($dadosValidados);
        $dadosResposta = $veiculo->paraArray();

        return new RespostaJson($dadosResposta, 201);
    }

    public function atualizar(Requisicao $requisicao, int $id): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS);

        $veiculo = $this->servico->atualizar($id, $dadosValidados);
        $dadosResposta = $veiculo->paraArray();

        return new RespostaJson($dadosResposta);
    }

    public function buscar(int $id): RespostaJson
    {
        $veiculo = $this->servico->buscar($id);
        $dadosResposta = $veiculo->paraArray();

        return new RespostaJson($dadosResposta);
    }

    public function listar(): RespostaJson
    {
        $veiculos = $this->servico->listar();
        $dadosResposta = [];

        foreach ($veiculos as $veiculo) {
            $dadosResposta[] = $veiculo->paraArray();
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

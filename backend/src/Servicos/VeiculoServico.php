<?php

declare(strict_types=1);

namespace LavaGest\Servicos;

use LavaGest\Contratos\VeiculoRepositorioInterface;
use LavaGest\Contratos\ClienteRepositorioInterface;
use LavaGest\Excecoes\RecursoNaoEncontradoException;
use LavaGest\Modelos\Veiculo;

// Coordena as operações de veiculos. O HTTP fica no controlador e o SQL no repositório.
class VeiculoServico
{
    private VeiculoRepositorioInterface $veiculoRepositorio;
    private ClienteRepositorioInterface $clienteRepositorio;

    public function __construct(
        VeiculoRepositorioInterface $veiculoRepositorio,
        ClienteRepositorioInterface $clienteRepositorio
    ) {
        // Recebemos os repositórios prontos. O serviço conhece os contratos, não o PDO.
        $this->veiculoRepositorio = $veiculoRepositorio;
        $this->clienteRepositorio = $clienteRepositorio;
    }

    public function criar(array $dados): Veiculo
    {
        $clienteId = $dados['cliente_id'];
        $this->validarCliente($clienteId);

        $veiculo = new Veiculo(
            $dados['cliente_id'],
            $dados['placa'],
            $dados['modelo'],
            $dados['categoria'],
            $dados['marca'] ?? null,
            $dados['cor'] ?? null,
            $dados['ano_fabricacao'] ?? null,
            $dados['observacoes'] ?? null
        );

        $veiculoCriado = $this->veiculoRepositorio->criar($veiculo);

        return $veiculoCriado;
    }

    public function atualizar(int $id, array $dados): Veiculo
    {
        $veiculo = $this->buscar($id);

        // Começamos com os valores atuais e trocamos apenas os campos enviados.
        // array_key_exists distingue campo ausente de campo enviado como null.
        $clienteId = $veiculo->getClienteId();
        $placa = $veiculo->getPlaca();
        $modelo = $veiculo->getModelo();
        $categoria = $veiculo->getCategoria();
        $marca = $veiculo->getMarca();
        $cor = $veiculo->getCor();
        $anoFabricacao = $veiculo->getAnoFabricacao();
        $observacoes = $veiculo->getObservacoes();

        if (array_key_exists('cliente_id', $dados)) {
            $clienteId = $dados['cliente_id'];
        }

        if (array_key_exists('placa', $dados)) {
            $placa = $dados['placa'];
        }

        if (array_key_exists('modelo', $dados)) {
            $modelo = $dados['modelo'];
        }

        if (array_key_exists('categoria', $dados)) {
            $categoria = $dados['categoria'];
        }

        if (array_key_exists('marca', $dados)) {
            $marca = $dados['marca'];
        }

        if (array_key_exists('cor', $dados)) {
            $cor = $dados['cor'];
        }

        if (array_key_exists('ano_fabricacao', $dados)) {
            $anoFabricacao = $dados['ano_fabricacao'];
        }

        if (array_key_exists('observacoes', $dados)) {
            $observacoes = $dados['observacoes'];
        }

        $this->validarCliente($clienteId);

        $veiculo->atualizar(
            $clienteId,
            $placa,
            $modelo,
            $categoria,
            $marca,
            $cor,
            $anoFabricacao,
            $observacoes
        );

        $this->veiculoRepositorio->atualizar($veiculo);

        return $veiculo;
    }

    public function buscar(int $id): Veiculo
    {
        $veiculo = $this->veiculoRepositorio->buscarPorId($id);

        if ($veiculo === null) {
            throw new RecursoNaoEncontradoException('Veículo não encontrado.');
        }

        return $veiculo;
    }

    /** @return list<Veiculo> */
    public function listar(): array
    {
        $veiculos = $this->veiculoRepositorio->listarTodos();

        return $veiculos;
    }

    public function deletar(int $id): void
    {
        $this->buscar($id);

        $this->veiculoRepositorio->deletar($id);
    }

    private function validarCliente(int $id): void
    {
        $cliente = $this->clienteRepositorio->buscarPorId($id);

        if ($cliente === null) {
            throw new RecursoNaoEncontradoException('Cliente não encontrado.');
        }
    }
}

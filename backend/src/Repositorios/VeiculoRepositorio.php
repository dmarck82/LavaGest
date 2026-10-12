<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Excecoes\PlacaJaCadastradaException;
use LavaGest\Modelos\Veiculo;
use PDO;
use PDOException;
use PDOStatement;

class VeiculoRepositorio
{
    private PDO $conexao;
    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }
    public function criar(Veiculo $veiculo): Veiculo
    {
        $comando = $this->conexao->prepare(
            'INSERT INTO vei_veiculo (
                vei_cliente_id, vei_placa, vei_marca,
                vei_modelo, vei_cor, vei_ano_fabricacao,
                vei_categoria,vei_observacoes
            )
            VALUES (
                    :cliente_id, :placa, :marca,
                    :modelo, :cor, :ano,
                    :categoria, :observacoes
                )'
        );

        $this->vincularDadosDoVeiculo($comando, $veiculo);

        $this->executarComando($comando);

        return $this->buscarPorId((int)$this->conexao->lastInsertId());
    }
    public function atualizar(Veiculo $veiculo): void
    {
        $comando = $this->conexao->prepare(
            'UPDATE vei_veiculo SET
                vei_cliente_id=:cliente_id,
                vei_placa=:placa,
                vei_marca=:marca,
                vei_modelo=:modelo,
                vei_cor=:cor,
                vei_ano_fabricacao=:ano,
                vei_categoria=:categoria,
                vei_observacoes=:observacoes
            WHERE vei_id=:id'
        );
        $this->vincularDadosDoVeiculo($comando, $veiculo);
        $comando->bindValue(
            ':id',
            $veiculo->getId(),
            PDO::PARAM_INT
        );
        $this->executarComando($comando);
    }

    public function buscarPorId(int $id): ?Veiculo
    {
        $comando = $this->conexao->prepare(
            'SELECT * FROM vei_veiculo WHERE vei_id=:id'
        );
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();

        $resultado = $comando->fetch(PDO::FETCH_ASSOC);

        return $resultado === false
            ? null
            : $this->transformarEmVeiculo($resultado);
    }

    public function listarTodos(): array
    {
        $resultados = $this->conexao->query(
            'SELECT * FROM vei_veiculo ORDER BY vei_modelo, vei_placa'
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $resultado) => $this->transformarEmVeiculo($resultado),
            $resultados
        );
    }

    public function deletar(int $id): void
    {
        $comando = $this->conexao->prepare(
            'DELETE FROM vei_veiculo WHERE vei_id=:id'
        );
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    private function vincularDadosDoVeiculo(
        PDOStatement $comando,
        Veiculo $veiculo
    ): void {
        $comando->bindValue(':cliente_id', $veiculo->getClienteId(), PDO::PARAM_INT);
        $comando->bindValue(':placa', $veiculo->getPlaca());
        $comando->bindValue(':marca', $veiculo->getMarca());
        $comando->bindValue(':modelo', $veiculo->getModelo());
        $comando->bindValue(':cor', $veiculo->getCor());
        $comando->bindValue(':ano', $veiculo->getAnoFabricacao());
        $comando->bindValue(':categoria', $veiculo->getCategoria());
        $comando->bindValue(':observacoes', $veiculo->getObservacoes());
    }

    private function executarComando(PDOStatement $comando): void
    {
        try {
            $comando->execute();
        } catch (PDOException $erro) {
            if (str_contains($erro->getMessage(), 'vei_veiculo.vei_placa')) {
                throw new PlacaJaCadastradaException(
                    'Já existe um veículo cadastrado com esta placa.',
                    0,
                    $erro
                );
            }

            throw $erro;
        }
    }

    private function transformarEmVeiculo(array $resultado): Veiculo
    {
        return new Veiculo(
            (int) $resultado['vei_cliente_id'],
            $resultado['vei_placa'],
            $resultado['vei_modelo'],
            $resultado['vei_categoria'],
            $resultado['vei_marca'],
            $resultado['vei_cor'],
            $resultado['vei_ano_fabricacao'] === null ? null : (int) $resultado['vei_ano_fabricacao'],
            $resultado['vei_observacoes'],
            (int) $resultado['vei_id'],
            $resultado['vei_criado_em']
        );
    }
}

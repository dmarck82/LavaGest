<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Contratos\VeiculoRepositorioInterface;
use LavaGest\Excecoes\PlacaJaCadastradaException;
use LavaGest\Modelos\Veiculo;
use LogicException;
use PDO;
use PDOException;
use PDOStatement;

// Implementa o contrato de veículos usando SQL e uma conexão PDO.
class VeiculoRepositorio implements VeiculoRepositorioInterface
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

        $this->executarComando($comando, $veiculo);

        $id = (int) $this->conexao->lastInsertId();
        $veiculoCriado = $this->buscarPorId($id);

        if ($veiculoCriado === null) {
            throw new LogicException('Não foi possível recuperar o veículo criado.');
        }

        return $veiculoCriado;
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
        $this->executarComando($comando, $veiculo);
    }

    public function buscarPorId(int $id): ?Veiculo
    {
        $comando = $this->conexao->prepare(
            'SELECT * FROM vei_veiculo WHERE vei_id=:id'
        );
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();

        $resultado = $comando->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return $this->transformarEmVeiculo($resultado);
    }

    public function existePorCliente(int $clienteId): bool
    {
        $comando = $this->conexao->prepare(
            'SELECT 1 FROM vei_veiculo WHERE vei_cliente_id = :cliente_id LIMIT 1'
        );
        $comando->execute([':cliente_id' => $clienteId]);

        return $comando->fetchColumn() !== false;
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM vei_veiculo ORDER BY vei_modelo, vei_placa';
        $comando = $this->conexao->query($sql);
        $resultados = $comando->fetchAll(PDO::FETCH_ASSOC);
        $veiculos = [];

        foreach ($resultados as $resultado) {
            $veiculo = $this->transformarEmVeiculo($resultado);
            $veiculos[] = $veiculo;
        }

        return $veiculos;
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

    private function executarComando(PDOStatement $comando, Veiculo $veiculo): void
    {
        try {
            $comando->execute();
        } catch (PDOException $erro) {
            // Uma placa duplicada vira uma exceção de negócio; outras falhas continuam sendo erros de banco.
            if ($erro->getCode() !== '23000') {
                throw $erro;
            }

            $consulta = $this->conexao->prepare(
                'SELECT vei_id FROM vei_veiculo WHERE vei_placa = :placa'
            );
            $consulta->execute([':placa' => $veiculo->getPlaca()]);
            $idExistente = $consulta->fetchColumn();

            if ($idExistente !== false && (int) $idExistente !== $veiculo->getId()) {
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
        $anoFabricacao = null;

        if ($resultado['vei_ano_fabricacao'] !== null) {
            $anoFabricacao = (int) $resultado['vei_ano_fabricacao'];
        }

        return new Veiculo(
            (int) $resultado['vei_cliente_id'],
            $resultado['vei_placa'],
            $resultado['vei_modelo'],
            $resultado['vei_categoria'],
            $resultado['vei_marca'],
            $resultado['vei_cor'],
            $anoFabricacao,
            $resultado['vei_observacoes'],
            (int) $resultado['vei_id'],
            $resultado['vei_criado_em']
        );
    }
}

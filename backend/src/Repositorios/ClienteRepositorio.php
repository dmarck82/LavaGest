<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Modelos\Cliente;
use LavaGest\Excecoes\WhatsappJaCadastradoException;
use LogicException;
use PDOException;
use PDO;

class ClienteRepositorio
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function criar(Cliente $cliente): Cliente
    {
        $sql = '
            INSERT INTO cli_cliente (
                cli_nome,
                cli_telefone,
                cli_whatsapp,
                cli_email,
                cli_uf,
                cli_cidade,
                cli_endereco,
                cli_bairro,
                cli_cep
            ) VALUES (
                :nome,
                :telefone,
                :whatsapp,
                :email,
                :uf,
                :cidade,
                :endereco,
                :bairro,
                :cep
            )
        ';

        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':nome', $cliente->getNome());
        $stmt->bindValue(':telefone', $cliente->getTelefone());
        $stmt->bindValue(':whatsapp', $cliente->getWhatsapp());
        $stmt->bindValue(':email', $cliente->getEmail());
        $stmt->bindValue(':uf', $cliente->getUf());
        $stmt->bindValue(':cidade', $cliente->getCidade());
        $stmt->bindValue(':endereco', $cliente->getEndereco());
        $stmt->bindValue(':bairro', $cliente->getBairro());
        $stmt->bindValue(':cep', $cliente->getCep());

        try {
            $stmt->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro);
        }

        $id = (int) $this->conexao->lastInsertId();
        $clienteCriado = $this->buscarPorId($id);

        if ($clienteCriado === null) {
            throw new LogicException(
                'Não foi possível recuperar o cliente criado.'
            );
        }

        return $clienteCriado;
    }

    public function atualizar(Cliente $cliente): void
    {
        $sql = '
            UPDATE cli_cliente
            SET
                cli_nome = :nome,
                cli_telefone = :telefone,
                cli_whatsapp = :whatsapp,
                cli_email = :email,
                cli_uf = :uf,
                cli_cidade = :cidade,
                cli_endereco = :endereco,
                cli_bairro = :bairro,
                cli_cep = :cep
            WHERE cli_id = :id
        ';

        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':nome', $cliente->getNome());
        $stmt->bindValue(':telefone', $cliente->getTelefone());
        $stmt->bindValue(':whatsapp', $cliente->getWhatsapp());
        $stmt->bindValue(':email', $cliente->getEmail());
        $stmt->bindValue(':uf', $cliente->getUf());
        $stmt->bindValue(':cidade', $cliente->getCidade());
        $stmt->bindValue(':endereco', $cliente->getEndereco());
        $stmt->bindValue(':bairro', $cliente->getBairro());
        $stmt->bindValue(':cep', $cliente->getCep());
        $stmt->bindValue(':id', $cliente->getId(), PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro);
        }
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $sql = 'SELECT * FROM cli_cliente WHERE cli_id = :id';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return $this->transformarEmCliente($resultado);
    }

    public function buscarPorWhatsapp(string $whatsapp): ?Cliente
    {
        $sql = '
            SELECT * FROM cli_cliente
            WHERE cli_whatsapp = :whatsapp
        ';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':whatsapp', $whatsapp);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return $this->transformarEmCliente($resultado);
    }

    public function buscarPorNome(string $nome): array
    {
        $sql = '
            SELECT * FROM cli_cliente
            WHERE cli_nome LIKE :nome
            ORDER BY cli_nome
        ';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':nome', '%' . $nome . '%');
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $resultado) => $this->transformarEmCliente($resultado),
            $resultados
        );
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM cli_cliente ORDER BY cli_nome';

        $stmt = $this->conexao->query($sql);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $resultado) => $this->transformarEmCliente($resultado),
            $resultados
        );
    }

    public function deletar(int $id): void
    {
        $sql = 'DELETE FROM cli_cliente WHERE cli_id = :id';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function transformarEmCliente(array $resultado): Cliente
    {
        return new Cliente(
            $resultado['cli_nome'],
            $resultado['cli_whatsapp'],
            $resultado['cli_telefone'],
            $resultado['cli_email'],
            $resultado['cli_uf'],
            $resultado['cli_cidade'],
            $resultado['cli_endereco'],
            $resultado['cli_bairro'],
            $resultado['cli_cep'],
            (int) $resultado['cli_id'],
            $resultado['cli_criado_em']
        );
    }

    private function tratarErroPersistencia(PDOException $erro): void
    {
        if (str_contains($erro->getMessage(), 'cli_cliente.cli_whatsapp')) {
            throw new WhatsappJaCadastradoException(
                'Já existe um cliente cadastrado com este WhatsApp.',
                0,
                $erro
            );
        }

        throw $erro;
    }
}

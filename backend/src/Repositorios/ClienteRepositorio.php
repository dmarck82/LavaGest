<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Contratos\ClienteRepositorioInterface;
use LavaGest\Excecoes\ClientePossuiVeiculosException;
use LavaGest\Excecoes\WhatsappJaCadastradoException;
use LavaGest\Modelos\Cliente;
use LogicException;
use PDO;
use PDOException;

// Implementa o contrato de clientes usando SQL e uma conexão PDO.
class ClienteRepositorio implements ClienteRepositorioInterface
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

        $comando = $this->conexao->prepare($sql);

        $comando->bindValue(':nome', $cliente->getNome());
        $comando->bindValue(':telefone', $cliente->getTelefone());
        $comando->bindValue(':whatsapp', $cliente->getWhatsapp());
        $comando->bindValue(':email', $cliente->getEmail());
        $comando->bindValue(':uf', $cliente->getUf());
        $comando->bindValue(':cidade', $cliente->getCidade());
        $comando->bindValue(':endereco', $cliente->getEndereco());
        $comando->bindValue(':bairro', $cliente->getBairro());
        $comando->bindValue(':cep', $cliente->getCep());

        try {
            $comando->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro, $cliente);
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

        $comando = $this->conexao->prepare($sql);

        $comando->bindValue(':nome', $cliente->getNome());
        $comando->bindValue(':telefone', $cliente->getTelefone());
        $comando->bindValue(':whatsapp', $cliente->getWhatsapp());
        $comando->bindValue(':email', $cliente->getEmail());
        $comando->bindValue(':uf', $cliente->getUf());
        $comando->bindValue(':cidade', $cliente->getCidade());
        $comando->bindValue(':endereco', $cliente->getEndereco());
        $comando->bindValue(':bairro', $cliente->getBairro());
        $comando->bindValue(':cep', $cliente->getCep());
        $comando->bindValue(':id', $cliente->getId(), PDO::PARAM_INT);

        try {
            $comando->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro, $cliente);
        }
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $sql = 'SELECT * FROM cli_cliente WHERE cli_id = :id';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();

        $resultado = $comando->fetch(PDO::FETCH_ASSOC);

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

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':whatsapp', $whatsapp);
        $comando->execute();

        $resultado = $comando->fetch(PDO::FETCH_ASSOC);

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

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':nome', '%' . $nome . '%');
        $comando->execute();

        $resultados = $comando->fetchAll(PDO::FETCH_ASSOC);

        $clientes = [];

        foreach ($resultados as $resultado) {
            $cliente = $this->transformarEmCliente($resultado);
            $clientes[] = $cliente;
        }

        return $clientes;
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM cli_cliente ORDER BY cli_nome';

        $comando = $this->conexao->query($sql);
        $resultados = $comando->fetchAll(PDO::FETCH_ASSOC);

        $clientes = [];

        foreach ($resultados as $resultado) {
            $cliente = $this->transformarEmCliente($resultado);
            $clientes[] = $cliente;
        }

        return $clientes;
    }

    public function deletar(int $id): void
    {
        $sql = 'DELETE FROM cli_cliente WHERE cli_id = :id';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            $comando->execute();
        } catch (PDOException $erro) {
            // SQLSTATE 23000 indica uma violação de integridade, como um vínculo obrigatório.
            if ($erro->getCode() !== '23000') {
                throw $erro;
            }

            // Protege também contra um vínculo criado entre a consulta e o DELETE.
            $consulta = $this->conexao->prepare(
                'SELECT 1 FROM vei_veiculo WHERE vei_cliente_id = :id LIMIT 1'
            );
            $consulta->execute([':id' => $id]);

            if ($consulta->fetchColumn() !== false) {
                throw new ClientePossuiVeiculosException(
                    'Cliente possui veículos cadastrados.',
                    0,
                    $erro
                );
            }

            throw $erro;
        }
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

    private function tratarErroPersistencia(PDOException $erro, Cliente $cliente): void
    {
        if ($erro->getCode() !== '23000') {
            throw $erro;
        }

        $existente = $this->buscarPorWhatsapp($cliente->getWhatsapp());

        if ($existente !== null && $existente->getId() !== $cliente->getId()) {
            throw new WhatsappJaCadastradoException(
                'Já existe um cliente cadastrado com este WhatsApp.',
                0,
                $erro
            );
        }

        throw $erro;
    }
}

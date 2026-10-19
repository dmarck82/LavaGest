<?php

declare(strict_types=1);

namespace LavaGest\Servicos;

use LavaGest\Contratos\ClienteRepositorioInterface;
use LavaGest\Contratos\VeiculoRepositorioInterface;
use LavaGest\Excecoes\ClientePossuiVeiculosException;
use LavaGest\Excecoes\RecursoNaoEncontradoException;
use LavaGest\Modelos\Cliente;

// Coordena as operações de clientes. O HTTP fica no controlador e o SQL no repositório.
class ClienteServico
{
    private ClienteRepositorioInterface $clienteRepositorio;
    private VeiculoRepositorioInterface $veiculoRepositorio;

    public function __construct(
        ClienteRepositorioInterface $clienteRepositorio,
        VeiculoRepositorioInterface $veiculoRepositorio
    ) {
        // Recebemos os repositórios prontos. O serviço conhece os contratos, não o PDO.
        $this->clienteRepositorio = $clienteRepositorio;
        $this->veiculoRepositorio = $veiculoRepositorio;
    }

    public function criar(array $dados): Cliente
    {
        $cliente = new Cliente(
            $dados['nome'],
            $dados['whatsapp'],
            $dados['telefone'] ?? null,
            $dados['email'] ?? null,
            $dados['uf'] ?? null,
            $dados['cidade'] ?? null,
            $dados['endereco'] ?? null,
            $dados['bairro'] ?? null,
            $dados['cep'] ?? null
        );

        $clienteCriado = $this->clienteRepositorio->criar($cliente);

        return $clienteCriado;
    }

    public function atualizar(int $id, array $dados): Cliente
    {
        $cliente = $this->buscar($id);

        // Começamos com os valores atuais e trocamos apenas os campos enviados.
        // array_key_exists distingue campo ausente de campo enviado como null.
        $nome = $cliente->getNome();
        $whatsapp = $cliente->getWhatsapp();
        $telefone = $cliente->getTelefone();
        $email = $cliente->getEmail();
        $uf = $cliente->getUf();
        $cidade = $cliente->getCidade();
        $endereco = $cliente->getEndereco();
        $bairro = $cliente->getBairro();
        $cep = $cliente->getCep();

        if (array_key_exists('nome', $dados)) {
            $nome = $dados['nome'];
        }

        if (array_key_exists('whatsapp', $dados)) {
            $whatsapp = $dados['whatsapp'];
        }

        if (array_key_exists('telefone', $dados)) {
            $telefone = $dados['telefone'];
        }

        if (array_key_exists('email', $dados)) {
            $email = $dados['email'];
        }

        if (array_key_exists('uf', $dados)) {
            $uf = $dados['uf'];
        }

        if (array_key_exists('cidade', $dados)) {
            $cidade = $dados['cidade'];
        }

        if (array_key_exists('endereco', $dados)) {
            $endereco = $dados['endereco'];
        }

        if (array_key_exists('bairro', $dados)) {
            $bairro = $dados['bairro'];
        }

        if (array_key_exists('cep', $dados)) {
            $cep = $dados['cep'];
        }

        $cliente->atualizar(
            $nome,
            $whatsapp,
            $telefone,
            $email,
            $uf,
            $cidade,
            $endereco,
            $bairro,
            $cep
        );

        $this->clienteRepositorio->atualizar($cliente);

        return $cliente;
    }

    public function buscar(int $id): Cliente
    {
        $cliente = $this->clienteRepositorio->buscarPorId($id);

        if ($cliente === null) {
            throw new RecursoNaoEncontradoException('Cliente não encontrado.');
        }

        return $cliente;
    }

    /** @return list<Cliente> */
    public function listar(): array
    {
        $clientes = $this->clienteRepositorio->listarTodos();

        return $clientes;
    }

    public function deletar(int $id): void
    {
        $this->buscar($id);

        $possuiVeiculos = $this->veiculoRepositorio->existePorCliente($id);

        if ($possuiVeiculos) {
            throw new ClientePossuiVeiculosException('Cliente possui veículos cadastrados.');
        }

        $this->clienteRepositorio->deletar($id);
    }
}

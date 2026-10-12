<?php

declare(strict_types=1);

namespace LavaGest\Controladores;

use InvalidArgumentException;
use LavaGest\Modelos\Cliente;
use LavaGest\Repositorios\ClienteRepositorio;
use RuntimeException;

class ClienteControlador
{
    private ClienteRepositorio $clienteRepositorio;

    public function __construct(
        ClienteRepositorio $clienteRepositorio
    ) {
        $this->clienteRepositorio = $clienteRepositorio;
    }

    public function criarCliente(array $dados): array
    {
        $cliente = new Cliente(
            $this->obterCampoObrigatorio($dados, 'nome'),
            $this->obterCampoObrigatorio($dados, 'whatsapp'),
            $this->obterCampoOpcional($dados, 'telefone'),
            $this->obterCampoOpcional($dados, 'email'),
            $this->obterCampoOpcional($dados, 'uf'),
            $this->obterCampoOpcional($dados, 'cidade'),
            $this->obterCampoOpcional($dados, 'endereco'),
            $this->obterCampoOpcional($dados, 'bairro'),
            $this->obterCampoOpcional($dados, 'cep')
        );

        $clienteCriado = $this->clienteRepositorio->criar($cliente);

        return $clienteCriado->paraArray();
    }

    public function atualizarCliente(int $id, array $dados): array
    {
        $cliente = $this->buscarClienteOuFalhar($id);

        $cliente->atualizar(
            array_key_exists('nome', $dados) ? $this->obterCampoObrigatorio($dados, 'nome') : $cliente->getNome(),
            array_key_exists('whatsapp', $dados) ? $this->obterCampoObrigatorio($dados, 'whatsapp') : $cliente->getWhatsapp(),
            array_key_exists('telefone', $dados) ? $this->obterCampoOpcional($dados, 'telefone') : $cliente->getTelefone(),
            array_key_exists('email', $dados) ? $this->obterCampoOpcional($dados, 'email') : $cliente->getEmail(),
            array_key_exists('uf', $dados) ? $this->obterCampoOpcional($dados, 'uf') : $cliente->getUf(),
            array_key_exists('cidade', $dados) ? $this->obterCampoOpcional($dados, 'cidade') : $cliente->getCidade(),
            array_key_exists('endereco', $dados) ? $this->obterCampoOpcional($dados, 'endereco') : $cliente->getEndereco(),
            array_key_exists('bairro', $dados) ? $this->obterCampoOpcional($dados, 'bairro') : $cliente->getBairro(),
            array_key_exists('cep', $dados) ? $this->obterCampoOpcional($dados, 'cep') : $cliente->getCep()
        );

        $this->clienteRepositorio->atualizar($cliente);

        return $cliente->paraArray();
    }

    public function buscarCliente(int $id): array
    {
        return $this->buscarClienteOuFalhar($id)->paraArray();
    }

    public function listarClientes(): array
    {
        $clientes = $this->clienteRepositorio->listarTodos();

        return array_map(
            fn(Cliente $cliente) => $cliente->paraArray(),
            $clientes
        );
    }

    public function buscarClientesPorNome(string $nome): array
    {
        $clientes = $this->clienteRepositorio->buscarPorNome($nome);

        return array_map(
            fn(Cliente $cliente) => $cliente->paraArray(),
            $clientes
        );
    }

    public function deletarCliente(int $id): void
    {
        $this->buscarClienteOuFalhar($id);

        $this->clienteRepositorio->deletar($id);
    }

    private function buscarClienteOuFalhar(int $id): Cliente
    {
        $cliente = $this->clienteRepositorio->buscarPorId($id);

        if ($cliente === null) {
            throw new RuntimeException('Cliente não encontrado.');
        }

        return $cliente;
    }

    private function obterCampoObrigatorio(array $dados, string $campo): string
    {
        if (!array_key_exists($campo, $dados) || !is_string($dados[$campo]) || trim($dados[$campo]) === '') {
            throw new InvalidArgumentException(
                "O campo '{$campo}' é obrigatório."
            );
        }

        return $dados[$campo];
    }

    private function obterCampoOpcional(array $dados, string $campo): ?string
    {
        if (!array_key_exists($campo, $dados) || $dados[$campo] === null) {
            return null;
        }

        if (!is_string($dados[$campo])) {
            throw new InvalidArgumentException(
                "O campo '{$campo}' deve ser um texto."
            );
        }

        return $dados[$campo];
    }
}

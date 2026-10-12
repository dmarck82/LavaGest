<?php

declare(strict_types=1);

namespace LavaGest\Controladores;

use InvalidArgumentException;
use LavaGest\Modelos\Veiculo;
use LavaGest\Repositorios\ClienteRepositorio;
use LavaGest\Repositorios\VeiculoRepositorio;
use RuntimeException;

class VeiculoControlador
{
    private VeiculoRepositorio $veiculos;

    private ClienteRepositorio $clientes;

    public function __construct(VeiculoRepositorio $veiculos, ClienteRepositorio $clientes)
    {
        $this->veiculos = $veiculos;
        $this->clientes = $clientes;
    }

    public function criarVeiculo(array $d): array
    {
        $v = $this->montar($d);
        return $this->veiculos->criar($v)->paraArray();
    }

    public function atualizarVeiculo(int $id, array $d): array
    {
        $v = $this->buscar($id);
        $d = array_merge($v->paraArray(), $d);
        $v = $this->montar($d, $id);
        $this->veiculos->atualizar($v);
        return $v->paraArray();
    }

    public function buscarVeiculo(int $id): array
    {
        return $this->buscar($id)->paraArray();
    }

    public function listarVeiculos(): array
    {
        return array_map(fn(Veiculo $v) => $v->paraArray(), $this->veiculos->listarTodos());
    }

    public function deletarVeiculo(int $id): void
    {
        $this->buscar($id);
        $this->veiculos->deletar($id);
    }

    private function montar(array $d, ?int $id = null): Veiculo
    {
        $cliente = $this->inteiro($d, 'cliente_id');

        if ($this->clientes->buscarPorId($cliente) === null) {
            throw new RuntimeException('Cliente não encontrado.');
        }

        return new Veiculo(
            $cliente,
            $this->texto($d, 'placa'),
            $this->texto($d, 'modelo'),
            $this->texto($d, 'categoria'),
            $this->opcional($d, 'marca'),
            $this->opcional($d, 'cor'),
            $this->inteiroOpcional($d, 'ano_fabricacao'),
            $this->opcional($d, 'observacoes'),
            $id
        );
    }

    private function buscar(int $id): Veiculo
    {
        $v = $this->veiculos->buscarPorId($id);

        if ($v === null) {
            throw new RuntimeException('Veículo não encontrado.');
        }

        return $v;
    }

    private function texto(array $d, string $c): string
    {
        if (!isset($d[$c]) || !is_string($d[$c]) || trim($d[$c]) === '') {
            throw new InvalidArgumentException("O campo '{$c}' é obrigatório.");
        }

        return $d[$c];
    }

    private function opcional(array $d, string $c): ?string
    {
        if (!array_key_exists($c, $d) || $d[$c] === null) {
            return null;
        }

        if (!is_string($d[$c])) {
            throw new InvalidArgumentException("O campo '{$c}' deve ser um texto.");
        }

        return $d[$c];
    }

    private function inteiro(array $d, string $c): int
    {
        if (!isset($d[$c]) || !is_int($d[$c]) || $d[$c] < 1) {
            throw new InvalidArgumentException("O campo '{$c}' deve ser um número inteiro positivo.");
        }

        return $d[$c];
    }

    private function inteiroOpcional(array $d, string $c): ?int
    {
        if (!array_key_exists($c, $d) || $d[$c] === null) {
            return null;
        }

        if (!is_int($d[$c])) {
            throw new InvalidArgumentException("O campo '{$c}' deve ser um número inteiro.");
        }

        return $d[$c];
    }
}

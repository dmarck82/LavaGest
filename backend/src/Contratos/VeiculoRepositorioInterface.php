<?php

declare(strict_types=1);

namespace LavaGest\Contratos;

use LavaGest\Modelos\Veiculo;

// O contrato diz o que o repositório precisa fazer, sem dizer como acessar o banco.
interface VeiculoRepositorioInterface
{
    public function criar(Veiculo $veiculo): Veiculo;

    public function atualizar(Veiculo $veiculo): void;

    public function buscarPorId(int $id): ?Veiculo;

    /** @return list<Veiculo> */
    public function listarTodos(): array;

    public function existePorCliente(int $clienteId): bool;

    public function deletar(int $id): void;
}

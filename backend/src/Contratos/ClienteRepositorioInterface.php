<?php

declare(strict_types=1);

namespace LavaGest\Contratos;

use LavaGest\Modelos\Cliente;

// O contrato diz o que o repositório precisa fazer, sem dizer como acessar o banco.
interface ClienteRepositorioInterface
{
    public function criar(Cliente $cliente): Cliente;

    public function atualizar(Cliente $cliente): void;

    public function buscarPorId(int $id): ?Cliente;

    /** @return list<Cliente> */
    public function listarTodos(): array;

    public function deletar(int $id): void;
}

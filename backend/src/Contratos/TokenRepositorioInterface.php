<?php

declare(strict_types=1);

namespace LavaGest\Contratos;

// O contrato diz o que o repositório precisa fazer, sem dizer como acessar o banco.
interface TokenRepositorioInterface
{
    public function salvar(int $usuarioId, string $hash, string $expiraEm): void;

    public function buscarUsuarioId(string $hash, string $agora): ?int;
}

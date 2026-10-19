<?php

declare(strict_types=1);

namespace LavaGest\Contratos;

use LavaGest\Modelos\Usuario;

// O contrato diz o que o repositório precisa fazer, sem dizer como acessar o banco.
interface UsuarioRepositorioInterface
{
    public function criar(Usuario $usuario): Usuario;

    public function atualizar(Usuario $usuario): void;

    public function atualizarSenha(int $id, string $novaSenha): void;

    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    /** @return list<Usuario> */
    public function listarTodos(): array;

    public function deletar(int $id): void;

    public function atualizarUltimoLogin(int $id): void;
}

<?php

declare(strict_types=1);

class UsuarioControlador
{
    private UsuarioRepositorio $usuarioRepositorio;

    public function __construct(UsuarioRepositorio $usuarioRepositorio)
    {
        $this->usuarioRepositorio = $usuarioRepositorio;
    }

    public function criarUsuario(array $dados): array
    {
        $usuario = new Usuario(
            $dados['nome'],
            $dados['email'],
            $dados['senha'],
            $dados['telefone'] ?? '',
            array_key_exists('status', $dados) ? $dados['status'] : 'ativo'
        );

        $usuarioCriado = $this->usuarioRepositorio->criar($usuario);

        return $usuarioCriado->paraArray();
    }

    public function atualizarUsuario(int $id, array $dados): array
    {
        $usuarioExistente = $this->buscarUsuarioOuFalhar($id);

        $usuarioAtualizado = new Usuario(
            $dados['nome'] ?? $usuarioExistente->getNome(),
            $dados['email'] ?? $usuarioExistente->getEmail(),
            $usuarioExistente->getSenha(),
            $dados['telefone'] ?? $usuarioExistente->getTelefone(),
            array_key_exists('status', $dados) ? $dados['status'] : $usuarioExistente->getStatus(),
            $id,
            $usuarioExistente->getUltimoLogin()
        );

        $this->usuarioRepositorio->atualizar($usuarioAtualizado);

        return $usuarioAtualizado->paraArray();
    }

    public function deletarUsuario(int $id): void
    {
        $this->buscarUsuarioOuFalhar($id);

        $this->usuarioRepositorio->deletar($id);
    }

    public function buscarUsuario(int $id): array
    {
        $usuario = $this->buscarUsuarioOuFalhar($id);

        return $usuario->paraArray();
    }

    public function listarUsuarios(): array
    {
        $usuarios = $this->usuarioRepositorio->listarTodos();

        return array_map(fn($usuario) => $usuario->paraArray(), $usuarios);
    }

    public function buscarUsuarioPorEmail(string $email): array
    {
        $usuario = $this->usuarioRepositorio->buscarPorEmail($email);

        if (!$usuario) {
            throw new RuntimeException('Usuário não encontrado.');
        }

        return $usuario->paraArray();
    }

    public function atualizarSenha(int $id, string $novaSenha): void
    {
        $usuarioExistente = $this->buscarUsuarioOuFalhar($id);

        $usuarioExistente->alterarSenha($novaSenha);

        $this->usuarioRepositorio->atualizarSenha($id, $usuarioExistente->getSenha());
    }

    public function buscarUsuariosPorNome(string $nome): array
    {
        $usuarios = $this->usuarioRepositorio->buscarPorNome($nome);

        return array_map(fn($usuario) => $usuario->paraArray(), $usuarios);
    }

    public function atualizarUltimoLogin(int $id): void
    {
        $this->buscarUsuarioOuFalhar($id);

        $this->usuarioRepositorio->atualizarUltimoLogin($id);
    }

    private function buscarUsuarioOuFalhar(int $id): Usuario
    {
        $usuario = $this->usuarioRepositorio->buscarPorId($id);

        if (!$usuario) {
            throw new RuntimeException('Usuário não encontrado.');
        }

        return $usuario;
    }
}

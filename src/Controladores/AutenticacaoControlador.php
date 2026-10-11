<?php

declare(strict_types=1);

class AutenticacaoControlador
{
    private UsuarioRepositorio $usuarioRepositorio;
    private TokenRepositorio $tokenRepositorio;

    public function __construct(
        UsuarioRepositorio $usuarioRepositorio,
        TokenRepositorio $tokenRepositorio
    ) {
        $this->usuarioRepositorio = $usuarioRepositorio;
        $this->tokenRepositorio = $tokenRepositorio;
    }

    public function autenticar(array $dados): array
    {
        $email = $dados['email'] ?? null;
        $senha = $dados['senha'] ?? null;

        if (!is_string($email) || !is_string($senha)) {
            throw new InvalidArgumentException(
                'E-mail e senha são obrigatórios.'
            );
        }

        $usuario = $this->usuarioRepositorio->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario->getSenha())) {
            throw new CredenciaisInvalidasException(
                'E-mail ou senha inválidos.'
            );
        }

        $usuarioId = $usuario->getId();

        if ($usuarioId === null) {
            throw new LogicException(
                'Usuário autenticado sem ID.'
            );
        }

        $this->usuarioRepositorio->atualizarUltimoLogin($usuarioId);

        return $this->tokenRepositorio->criar($usuarioId);
    }
}

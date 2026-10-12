<?php

declare(strict_types=1);

namespace LavaGest\Seguranca;

use LavaGest\Excecoes\TokenInvalidoException;
use LavaGest\Repositorios\TokenRepositorio;

class Autenticador
{
    private TokenRepositorio $tokenRepositorio;

    public function __construct(
        TokenRepositorio $tokenRepositorio
    ) {
        $this->tokenRepositorio = $tokenRepositorio;
    }

    public function autenticar(): int
    {
        $cabecalho = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (
            !preg_match(
                '/^Bearer\s+(.+)$/i',
                $cabecalho,
                $correspondencias
            )
        ) {
            throw new TokenInvalidoException(
                'Token de autenticação não informado.'
            );
        }

        $token = trim($correspondencias[1]);

        $usuarioId = $this->tokenRepositorio
            ->buscarUsuarioIdPorToken($token);

        if ($usuarioId === null) {
            throw new TokenInvalidoException(
                'Token inválido ou expirado.'
            );
        }

        return $usuarioId;
    }
}

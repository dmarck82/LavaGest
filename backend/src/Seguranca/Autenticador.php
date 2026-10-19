<?php

declare(strict_types=1);

namespace LavaGest\Seguranca;

use LavaGest\Contratos\TokenRepositorioInterface;
use LavaGest\Excecoes\TokenInvalidoException;

// Confere um token recebido. A leitura do cabeçalho HTTP fica em Requisicao.
class Autenticador
{
    private TokenRepositorioInterface $tokenRepositorio;

    public function __construct(TokenRepositorioInterface $tokenRepositorio)
    {
        $this->tokenRepositorio = $tokenRepositorio;
    }

    public function autenticar(string $token): int
    {
        $tokenHash = hash('sha256', $token);
        $agora = date('Y-m-d H:i:s');
        $usuarioId = $this->tokenRepositorio->buscarUsuarioId($tokenHash, $agora);

        if ($usuarioId === null) {
            throw new TokenInvalidoException('Token inválido ou expirado.');
        }

        return $usuarioId;
    }
}

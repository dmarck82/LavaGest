<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Contratos\TokenRepositorioInterface;
use PDO;

// Persiste hashes de tokens. Quem gera o token é o serviço de autenticação.
class TokenRepositorio implements TokenRepositorioInterface
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function salvar(int $usuarioId, string $hash, string $expiraEm): void
    {
        $sql = '
            INSERT INTO aut_token (usu_id, aut_token_hash, aut_expira_em)
            VALUES (:usuario_id, :hash, :expira_em)
        ';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $comando->bindValue(':hash', $hash);
        $comando->bindValue(':expira_em', $expiraEm);
        $comando->execute();
    }

    public function buscarUsuarioId(string $hash, string $agora): ?int
    {
        $sql = '
            SELECT usu_id FROM aut_token
            WHERE aut_token_hash = :hash AND aut_expira_em > :agora
            LIMIT 1
        ';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':hash', $hash);
        $comando->bindValue(':agora', $agora);
        $comando->execute();

        $usuarioId = $comando->fetchColumn();

        if ($usuarioId === false) {
            return null;
        }

        return (int) $usuarioId;
    }
}

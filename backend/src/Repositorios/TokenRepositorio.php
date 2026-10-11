<?php

declare(strict_types=1);

class TokenRepositorio
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function criar(int $usuarioId): array
    {
        $token = bin2hex(random_bytes(32));

        $tokenHash = hash('sha256', $token);

        $expiraEm = date(
            'Y-m-d H:i:s',
            strtotime('+1 hour')
        );

        $sql = '
            INSERT INTO aut_token (
                usu_id,
                aut_token_hash,
                aut_expira_em
            ) VALUES (
                :usuario_id,
                :token_hash,
                :expira_em
            )
        ';

        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':token_hash', $tokenHash);
        $stmt->bindValue(':expira_em', $expiraEm);

        $stmt->execute();

        return [
            'token' => $token,
            'tipo' => 'Bearer',
            'expira_em' => $expiraEm,
        ];
    }
}

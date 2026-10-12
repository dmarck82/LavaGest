<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Excecoes\EmailJaCadastradoException;
use LavaGest\Modelos\Usuario;
use PDO;
use PDOException;

class UsuarioRepositorio
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }


    public function criar(Usuario $usuario): Usuario
    {
        $sql = '
            INSERT INTO usu_usuario (
                usu_nome,
                usu_email,
                usu_senha,
                usu_telefone,
                usu_status
            ) VALUES (
                :nome,
                :email,
                :senha,
                :telefone,
                :status
            )
        ';

        $senhaHash = password_hash(
            $usuario->getSenha(),
            PASSWORD_DEFAULT
        );


        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':nome', $usuario->getNome());
        $stmt->bindValue(':email', $usuario->getEmail());
        $stmt->bindValue(':senha', $senhaHash);
        $stmt->bindValue(':telefone', $usuario->getTelefone());
        $stmt->bindValue(':status', $usuario->getStatus());

        try {
            $stmt->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro);
        }

        $id = (int) $this->conexao->lastInsertId();

        return new Usuario(
            $usuario->getNome(),
            $usuario->getEmail(),
            $senhaHash,
            $usuario->getTelefone(),
            $usuario->getStatus(),
            $id
        );
    }

    public function atualizar(Usuario $usuario): void
    {
        $sql = '
            UPDATE usu_usuario
            SET
                usu_nome = :nome,
                usu_email = :email,
                usu_telefone = :telefone,
                usu_status = :status
            WHERE
                usu_id = :id
        ';

        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':nome', $usuario->getNome());
        $stmt->bindValue(':email', $usuario->getEmail());
        $stmt->bindValue(':telefone', $usuario->getTelefone());
        $stmt->bindValue(':status', $usuario->getStatus());
        $stmt->bindValue(':id', $usuario->getId(), PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro);
        }
    }

    public function atualizarSenha(int $id, string $novaSenha): void
    {
        $sql = '
            UPDATE usu_usuario
            SET
                usu_senha = :senha
            WHERE
                usu_id = :id
        ';

        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':senha', password_hash($novaSenha, PASSWORD_DEFAULT));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $sql = 'SELECT * FROM usu_usuario WHERE usu_email = :email';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return new Usuario(
            $resultado['usu_nome'],
            $resultado['usu_email'],
            $resultado['usu_senha'],
            $resultado['usu_telefone'],
            $resultado['usu_status'],
            (int) $resultado['usu_id'],
            $resultado['usu_ultimo_login']
        );
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $sql = 'SELECT * FROM usu_usuario WHERE usu_id = :id';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return new Usuario(
            $resultado['usu_nome'],
            $resultado['usu_email'],
            $resultado['usu_senha'],
            $resultado['usu_telefone'],
            $resultado['usu_status'],
            (int) $resultado['usu_id'],
            $resultado['usu_ultimo_login']
        );
    }

    public function buscarPorNome(string $nome): array
    {
        $sql = 'SELECT * FROM usu_usuario WHERE usu_nome LIKE :nome';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':nome', '%' . $nome . '%');
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $usuarios = [];
        foreach ($resultados as $resultado) {
            $usuarios[] = new Usuario(
                $resultado['usu_nome'],
                $resultado['usu_email'],
                $resultado['usu_senha'],
                $resultado['usu_telefone'],
                $resultado['usu_status'],
                (int) $resultado['usu_id'],
                $resultado['usu_ultimo_login']
            );
        }

        return $usuarios;
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM usu_usuario';

        $stmt = $this->conexao->query($sql);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $usuarios = [];
        foreach ($resultados as $resultado) {
            $usuarios[] = new Usuario(
                $resultado['usu_nome'],
                $resultado['usu_email'],
                $resultado['usu_senha'],
                $resultado['usu_telefone'],
                $resultado['usu_status'],
                (int) $resultado['usu_id'],
                $resultado['usu_ultimo_login']
            );
        }

        return $usuarios;
    }

    public function deletar(int $id): void
    {
        $sql = 'DELETE FROM usu_usuario WHERE usu_id = :id';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function atualizarUltimoLogin(int $id): void
    {
        $sql = 'UPDATE usu_usuario SET usu_ultimo_login = :ultimo_login WHERE usu_id = :id';

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':ultimo_login', date('Y-m-d H:i:s'));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function tratarErroPersistencia(
        PDOException $erro
    ): void {
        if (
            str_contains(
                $erro->getMessage(),
                'usu_usuario.usu_email'
            )
        ) {
            throw new EmailJaCadastradoException(
                'Já existe um usuário com este e-mail.',
                0,
                $erro
            );
        }

        throw $erro;
    }
}

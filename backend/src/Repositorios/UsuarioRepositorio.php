<?php

declare(strict_types=1);

namespace LavaGest\Repositorios;

use LavaGest\Contratos\UsuarioRepositorioInterface;
use LavaGest\Excecoes\EmailJaCadastradoException;
use LavaGest\Modelos\Usuario;
use PDO;
use PDOException;

// Implementa o contrato de usuários usando SQL e uma conexão PDO.
class UsuarioRepositorio implements UsuarioRepositorioInterface
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

        $comando = $this->conexao->prepare($sql);

        $comando->bindValue(':nome', $usuario->getNome());
        $comando->bindValue(':email', $usuario->getEmail());
        $comando->bindValue(':senha', $senhaHash);
        $comando->bindValue(':telefone', $usuario->getTelefone());
        $comando->bindValue(':status', $usuario->getStatus());

        try {
            $comando->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro, $usuario);
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

        $comando = $this->conexao->prepare($sql);

        $comando->bindValue(':nome', $usuario->getNome());
        $comando->bindValue(':email', $usuario->getEmail());
        $comando->bindValue(':telefone', $usuario->getTelefone());
        $comando->bindValue(':status', $usuario->getStatus());
        $comando->bindValue(':id', $usuario->getId(), PDO::PARAM_INT);
        try {
            $comando->execute();
        } catch (PDOException $erro) {
            $this->tratarErroPersistencia($erro, $usuario);
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

        $comando = $this->conexao->prepare($sql);

        $comando->bindValue(':senha', password_hash($novaSenha, PASSWORD_DEFAULT));
        $comando->bindValue(':id', $id, PDO::PARAM_INT);

        $comando->execute();
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $sql = 'SELECT * FROM usu_usuario WHERE usu_email = :email';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':email', $email);
        $comando->execute();

        $resultado = $comando->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return $this->transformarEmUsuario($resultado);
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $sql = 'SELECT * FROM usu_usuario WHERE usu_id = :id';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();

        $resultado = $comando->fetch(PDO::FETCH_ASSOC);

        if ($resultado === false) {
            return null;
        }

        return $this->transformarEmUsuario($resultado);
    }

    public function buscarPorNome(string $nome): array
    {
        $sql = 'SELECT * FROM usu_usuario WHERE usu_nome LIKE :nome';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':nome', '%' . $nome . '%');
        $comando->execute();

        $resultados = $comando->fetchAll(PDO::FETCH_ASSOC);

        $usuarios = [];

        foreach ($resultados as $resultado) {
            $usuario = $this->transformarEmUsuario($resultado);
            $usuarios[] = $usuario;
        }

        return $usuarios;
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT * FROM usu_usuario';

        $comando = $this->conexao->query($sql);
        $resultados = $comando->fetchAll(PDO::FETCH_ASSOC);

        $usuarios = [];

        foreach ($resultados as $resultado) {
            $usuario = $this->transformarEmUsuario($resultado);
            $usuarios[] = $usuario;
        }

        return $usuarios;
    }

    public function deletar(int $id): void
    {
        $sql = 'DELETE FROM usu_usuario WHERE usu_id = :id';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    public function atualizarUltimoLogin(int $id): void
    {
        $sql = 'UPDATE usu_usuario SET usu_ultimo_login = :ultimo_login WHERE usu_id = :id';

        $comando = $this->conexao->prepare($sql);
        $comando->bindValue(':ultimo_login', date('Y-m-d H:i:s'));
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    // Converte os nomes das colunas do banco em um objeto do nosso sistema.
    private function transformarEmUsuario(array $resultado): Usuario
    {
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

    private function tratarErroPersistencia(PDOException $erro, Usuario $usuario): void
    {
        // Consultamos o e-mail para traduzir somente o conflito que conhecemos.
        if ($erro->getCode() !== '23000') {
            throw $erro;
        }

        $existente = $this->buscarPorEmail($usuario->getEmail());

        if ($existente !== null && $existente->getId() !== $usuario->getId()) {
            throw new EmailJaCadastradoException(
                'Já existe um usuário com este e-mail.',
                0,
                $erro
            );
        }

        throw $erro;
    }
}

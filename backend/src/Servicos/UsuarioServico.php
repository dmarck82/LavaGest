<?php

declare(strict_types=1);

namespace LavaGest\Servicos;

use LavaGest\Contratos\UsuarioRepositorioInterface;
use LavaGest\Excecoes\RecursoNaoEncontradoException;
use LavaGest\Modelos\Usuario;

// Coordena as operações de usuarios. O HTTP fica no controlador e o SQL no repositório.
class UsuarioServico
{
    private UsuarioRepositorioInterface $usuarioRepositorio;

    public function __construct(
        UsuarioRepositorioInterface $usuarioRepositorio
    ) {
        // Recebemos os repositórios prontos. O serviço conhece os contratos, não o PDO.
        $this->usuarioRepositorio = $usuarioRepositorio;
    }

    public function criar(array $dados): Usuario
    {
        $usuario = new Usuario(
            $dados['nome'],
            $dados['email'],
            $dados['senha'],
            $dados['telefone'],
            $dados['status'] ?? 'ativo'
        );

        $usuarioCriado = $this->usuarioRepositorio->criar($usuario);

        return $usuarioCriado;
    }

    public function atualizar(int $id, array $dados): Usuario
    {
        $usuario = $this->buscar($id);

        // Começamos com os valores atuais e trocamos apenas os campos enviados.
        $nome = $usuario->getNome();
        $email = $usuario->getEmail();
        $senha = $usuario->getSenha();
        $telefone = $usuario->getTelefone();
        $status = $usuario->getStatus();

        if (array_key_exists('nome', $dados)) {
            $nome = $dados['nome'];
        }

        if (array_key_exists('email', $dados)) {
            $email = $dados['email'];
        }

        if (array_key_exists('telefone', $dados)) {
            $telefone = $dados['telefone'];
        }

        if (array_key_exists('status', $dados)) {
            $status = $dados['status'];
        }

        $usuario->atualizar(
            $nome,
            $email,
            $senha,
            $telefone,
            $status
        );

        $this->usuarioRepositorio->atualizar($usuario);

        return $usuario;
    }

    public function buscar(int $id): Usuario
    {
        $usuario = $this->usuarioRepositorio->buscarPorId($id);

        if ($usuario === null) {
            throw new RecursoNaoEncontradoException('Usuário não encontrado.');
        }

        return $usuario;
    }

    /** @return list<Usuario> */
    public function listar(): array
    {
        $usuarios = $this->usuarioRepositorio->listarTodos();

        return $usuarios;
    }

    public function deletar(int $id): void
    {
        $this->buscar($id);

        $this->usuarioRepositorio->deletar($id);
    }

    public function atualizarSenha(int $id, string $novaSenha): void
    {
        $usuario = $this->buscar($id);
        $usuario->alterarSenha($novaSenha);

        $senha = $usuario->getSenha();
        $this->usuarioRepositorio->atualizarSenha($id, $senha);
    }
}

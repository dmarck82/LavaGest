<?php

declare(strict_types=1);

namespace LavaGest\Servicos;

use LavaGest\Contratos\TokenRepositorioInterface;
use LavaGest\Contratos\UsuarioRepositorioInterface;
use LavaGest\Excecoes\CredenciaisInvalidasException;
use LogicException;

// Confere as credenciais e cria uma sessão com duração de uma hora.
class AutenticacaoServico
{
    private UsuarioRepositorioInterface $usuarioRepositorio;
    private TokenRepositorioInterface $tokenRepositorio;

    public function __construct(
        UsuarioRepositorioInterface $usuarioRepositorio,
        TokenRepositorioInterface $tokenRepositorio
    ) {
        $this->usuarioRepositorio = $usuarioRepositorio;
        $this->tokenRepositorio = $tokenRepositorio;
    }

    public function autenticar(string $email, string $senha): array
    {
        $usuario = $this->usuarioRepositorio->buscarPorEmail($email);

        if ($usuario === null) {
            throw new CredenciaisInvalidasException('E-mail ou senha inválidos.');
        }

        $senhaCorreta = password_verify($senha, $usuario->getSenha());

        if (!$senhaCorreta) {
            throw new CredenciaisInvalidasException('E-mail ou senha inválidos.');
        }

        $usuarioId = $usuario->getId();

        if ($usuarioId === null) {
            throw new LogicException('Usuário autenticado sem ID.');
        }

        $bytesAleatorios = random_bytes(32);
        $token = bin2hex($bytesAleatorios);
        $tokenHash = hash('sha256', $token);
        $horarioExpiracao = strtotime('+1 hour');
        $expiraEm = date('Y-m-d H:i:s', $horarioExpiracao);

        // O cliente recebe o token, mas o banco guarda somente seu hash.
        $this->tokenRepositorio->salvar($usuarioId, $tokenHash, $expiraEm);
        $this->usuarioRepositorio->atualizarUltimoLogin($usuarioId);

        return [
            'token' => $token,
            'tipo' => 'Bearer',
            'expira_em' => $expiraEm,
        ];
    }
}

<?php

declare(strict_types=1);

namespace LavaGest\Modelos;

use InvalidArgumentException;

class Usuario
{
    private ?int $id;
    private string $nome;
    private string $email;
    private string $senha;
    private string $telefone;
    private string $status;
    private ?string $ultimo_login;

    public function __construct(
        string $nome,
        string $email,
        string $senha,
        string $telefone,
        mixed $status = 'ativo',
        ?int $id = null,
        ?string $ultimo_login = null
    ) {

        $this->validaEmail($email);
        $this->validarSenha($senha);
        $this->validarStatus($status);

        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->senha = $senha;
        $this->telefone = $this->normalizarTelefone($telefone);
        $this->status = $status;
        $this->ultimo_login = $ultimo_login;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSenha(): string
    {
        return $this->senha;
    }

    public function getTelefone(): string
    {
        return $this->telefone;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUltimoLogin(): ?string
    {
        return $this->ultimo_login;
    }

    public function atualizar(
        string $nome,
        string $email,
        string $senha,
        string $telefone,
        mixed $status
    ): void {
        $this->validaEmail($email);
        $this->validarSenha($senha);
        $this->validarStatus($status);

        $this->nome = $nome;
        $this->email = $email;
        $this->senha = $senha;
        $this->telefone = $telefone;
        $this->status = $status;
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'status' => $this->status,
            'ultimo_login' => $this->ultimo_login,
        ];
    }

    public function alterarSenha(string $novaSenha): void
    {
        $this->validarSenha($novaSenha);

        $this->senha = $novaSenha;
    }

    private function validarStatus(mixed $status): void
    {
        if (!in_array($status, ['ativo', 'inativo'], true)) {
            throw new InvalidArgumentException('Status deve ser ativo ou inativo.');
        }
    }

    private function validaEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }
    }

    private function validarSenha(string $senha): void
    {
        if (strlen($senha) < 8) {
            throw new InvalidArgumentException(
                'A senha deve ter pelo menos 8 caracteres.'
            );
        }
    }

    private function normalizarTelefone(string $telefone): string
    {
        $telefone = preg_replace('/\D/', '', $telefone);

        if (
            strlen($telefone) !== 10
            && strlen($telefone) !== 11
        ) {
            throw new InvalidArgumentException(
                'Telefone deve ter 10 ou 11 dígitos.'
            );
        }

        return $telefone;
    }
}

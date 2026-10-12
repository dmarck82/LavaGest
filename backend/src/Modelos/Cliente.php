<?php

declare(strict_types=1);

namespace LavaGest\Modelos;

use InvalidArgumentException;

class Cliente
{
    private ?int $id;
    private string $nome;
    private ?string $telefone;
    private string $whatsapp;
    private ?string $email;
    private ?string $uf;
    private ?string $cidade;
    private ?string $endereco;
    private ?string $bairro;
    private ?string $cep;
    private ?string $criado_em;

    public function __construct(
        string $nome,
        string $whatsapp,
        ?string $telefone = null,
        ?string $email = null,
        ?string $uf = null,
        ?string $cidade = null,
        ?string $endereco = null,
        ?string $bairro = null,
        ?string $cep = null,
        ?int $id = null,
        ?string $criado_em = null
    ) {
        $this->id = $id;
        $this->nome = $this->normalizarNome($nome);
        $this->whatsapp = $this->normalizarTelefoneObrigatorio($whatsapp);
        $this->telefone = $this->normalizarTelefoneOpcional($telefone);
        $this->email = $this->normalizarEmail($email);
        $this->uf = $this->normalizarUf($uf);
        $this->cidade = $this->normalizarTextoOpcional($cidade);
        $this->endereco = $this->normalizarTextoOpcional($endereco);
        $this->bairro = $this->normalizarTextoOpcional($bairro);
        $this->cep = $this->normalizarCep($cep);
        $this->criado_em = $criado_em;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getTelefone(): ?string
    {
        return $this->telefone;
    }

    public function getWhatsapp(): string
    {
        return $this->whatsapp;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUf(): ?string
    {
        return $this->uf;
    }

    public function getCidade(): ?string
    {
        return $this->cidade;
    }

    public function getEndereco(): ?string
    {
        return $this->endereco;
    }

    public function getBairro(): ?string
    {
        return $this->bairro;
    }

    public function getCep(): ?string
    {
        return $this->cep;
    }

    public function getCriadoEm(): ?string
    {
        return $this->criado_em;
    }

    public function atualizar(
        string $nome,
        string $whatsapp,
        ?string $telefone = null,
        ?string $email = null,
        ?string $uf = null,
        ?string $cidade = null,
        ?string $endereco = null,
        ?string $bairro = null,
        ?string $cep = null
    ): void {
        $this->nome = $this->normalizarNome($nome);
        $this->whatsapp = $this->normalizarTelefoneObrigatorio($whatsapp);
        $this->telefone = $this->normalizarTelefoneOpcional($telefone);
        $this->email = $this->normalizarEmail($email);
        $this->uf = $this->normalizarUf($uf);
        $this->cidade = $this->normalizarTextoOpcional($cidade);
        $this->endereco = $this->normalizarTextoOpcional($endereco);
        $this->bairro = $this->normalizarTextoOpcional($bairro);
        $this->cep = $this->normalizarCep($cep);
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'uf' => $this->uf,
            'cidade' => $this->cidade,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'cep' => $this->cep,
            'criado_em' => $this->criado_em,
        ];
    }

    private function normalizarNome(string $nome): string
    {
        $nome = trim($nome);

        if ($nome === '') {
            throw new InvalidArgumentException('Nome é obrigatório.');
        }

        return $nome;
    }

    private function normalizarTelefoneObrigatorio(string $telefone): string
    {
        $telefone = $this->apenasNumeros($telefone);

        if (strlen($telefone) !== 10 && strlen($telefone) !== 11) {
            throw new InvalidArgumentException(
                'WhatsApp deve ter 10 ou 11 dígitos.'
            );
        }

        return $telefone;
    }

    private function normalizarTelefoneOpcional(?string $telefone): ?string
    {
        if ($telefone === null || trim($telefone) === '') {
            return null;
        }

        return $this->normalizarTelefoneObrigatorio($telefone);
    }

    private function normalizarEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }

        return $email;
    }

    private function normalizarUf(?string $uf): ?string
    {
        if ($uf === null || trim($uf) === '') {
            return null;
        }

        $uf = strtoupper(trim($uf));

        if (strlen($uf) !== 2) {
            throw new InvalidArgumentException(
                'UF deve ter duas letras.'
            );
        }

        return $uf;
    }

    private function normalizarCep(?string $cep): ?string
    {
        if ($cep === null || trim($cep) === '') {
            return null;
        }

        $cep = $this->apenasNumeros($cep);

        if (strlen($cep) !== 8) {
            throw new InvalidArgumentException(
                'CEP deve ter 8 dígitos.'
            );
        }

        return $cep;
    }

    private function normalizarTextoOpcional(?string $texto): ?string
    {
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        return trim($texto);
    }

    private function apenasNumeros(string $valor): string
    {
        return preg_replace('/\D/', '', $valor);
    }
}

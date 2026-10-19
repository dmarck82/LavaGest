<?php

declare(strict_types=1);

namespace LavaGest\Http;

use InvalidArgumentException;
use LavaGest\Excecoes\TokenInvalidoException;
use stdClass;

// Guarda os dados recebidos do navegador em um objeto.
class Requisicao
{
    private string $metodo;
    private string $caminho;
    private string $corpo;
    private string $autorizacao;

    public function __construct(
        string $metodo,
        string $caminho,
        string $corpo = '',
        string $autorizacao = ''
    ) {
        $this->metodo = $metodo;
        $this->caminho = $caminho;
        $this->corpo = $corpo;
        $this->autorizacao = $autorizacao;
    }

    public static function capturar(): self
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $endereco = $_SERVER['REQUEST_URI'] ?? '/';
        $caminho = parse_url($endereco, PHP_URL_PATH);

        if ($caminho === false || $caminho === null || $caminho === '') {
            $caminho = '/';
        }

        $corpo = file_get_contents('php://input');

        if ($corpo === false) {
            $corpo = '';
        }

        $autorizacao = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        return new self($metodo, $caminho, $corpo, $autorizacao);
    }

    public function getMetodo(): string
    {
        return $this->metodo;
    }

    public function getCaminho(): string
    {
        return $this->caminho;
    }

    public function json(): array
    {
        // JSON_THROW_ON_ERROR faz um JSON malformado lançar uma exceção.
        $dados = json_decode($this->corpo, false, 512, JSON_THROW_ON_ERROR);

        // Esperamos um objeto, como {"nome": "Ana"}, e não uma lista ou um número.
        if (!$dados instanceof stdClass) {
            throw new InvalidArgumentException('O corpo da requisição deve ser um objeto JSON.');
        }

        return (array) $dados;
    }

    public function token(): string
    {
        $cabecalho = trim($this->autorizacao);

        // A expressão reconhece "Bearer", um espaço e um token sem espaços.
        $formatoValido = preg_match('/^Bearer\s+(\S+)$/i', $cabecalho, $partes);

        if ($formatoValido !== 1) {
            throw new TokenInvalidoException('Token de autenticação não informado.');
        }

        return $partes[1];
    }
}

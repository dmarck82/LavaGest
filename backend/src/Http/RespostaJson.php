<?php

declare(strict_types=1);

namespace LavaGest\Http;

// Guarda os dados e o status até o momento de enviar a resposta ao navegador.
class RespostaJson
{
    private mixed $dados;
    private int $status;

    public function __construct(mixed $dados = null, int $status = 200)
    {
        $this->dados = $dados;
        $this->status = $status;
    }

    public function getDados(): mixed
    {
        return $this->dados;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function enviar(): void
    {
        $corpo = '';

        if ($this->status !== 204) {
            $opcoesJson = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE;
            $corpo = json_encode($this->dados, $opcoesJson);
        }

        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');

        echo $corpo;
    }
}

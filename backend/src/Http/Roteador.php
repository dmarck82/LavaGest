<?php

declare(strict_types=1);

namespace LavaGest\Http;

use LavaGest\Seguranca\Autenticador;

// Decide qual ação executar usando o método e o caminho da requisição.
class Roteador
{
    private array $rotas = [];
    private Autenticador $autenticador;

    public function __construct(Autenticador $autenticador)
    {
        $this->autenticador = $autenticador;
    }

    public function adicionar(string $metodo, string $caminho, callable $acao, bool $protegida = true): void
    {
        $rota = [
            'metodo' => $metodo,
            'caminho' => $caminho,
            'acao' => $acao,
            'protegida' => $protegida,
        ];

        $this->rotas[] = $rota;
    }

    public function despachar(Requisicao $requisicao): RespostaJson
    {
        $caminhoExiste = false;

        foreach ($this->rotas as $rota) {
            $parametros = $this->obterParametros($rota['caminho'], $requisicao->getCaminho());

            if ($parametros === null) {
                continue;
            }

            $caminhoExiste = true;

            if ($rota['metodo'] !== $requisicao->getMetodo()) {
                continue;
            }

            if ($rota['protegida']) {
                $token = $requisicao->token();
                $this->autenticador->autenticar($token);
            }

            // "acao" é a função registrada no arquivo de rotas.
            $acao = $rota['acao'];
            $resposta = $acao($requisicao, $parametros);

            return $resposta;
        }

        if ($caminhoExiste) {
            return new RespostaJson(['erro' => 'Método não permitido.'], 405);
        }

        return new RespostaJson(['erro' => 'Rota não encontrada.'], 404);
    }

    private function obterParametros(string $caminhoRota, string $caminhoRecebido): ?array
    {
        // Exemplo: /clientes/{id} e /clientes/12 são comparados parte por parte.
        $partesRota = explode('/', $caminhoRota);
        $partesRecebidas = explode('/', $caminhoRecebido);

        if (count($partesRota) !== count($partesRecebidas)) {
            return null;
        }

        $parametros = [];

        foreach ($partesRota as $indice => $parteRota) {
            $parteRecebida = $partesRecebidas[$indice];

            if ($parteRota === '{id}') {
                if (!ctype_digit($parteRecebida)) {
                    return null;
                }

                $parametros['id'] = $parteRecebida;
                continue;
            }

            if ($parteRota !== $parteRecebida) {
                return null;
            }
        }

        // Um array vazio significa que a rota corresponde, mas não tem parâmetros.
        // null significa que o caminho não corresponde.
        return $parametros;
    }
}

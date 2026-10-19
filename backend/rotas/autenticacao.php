<?php

declare(strict_types=1);

use LavaGest\Controladores\AutenticacaoControlador;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Http\Roteador;

function registrarRotasAutenticacao(Roteador $roteador, AutenticacaoControlador $controlador): void
{
    // O login precisa ser público para permitir obter o primeiro token.
    $roteador->adicionar('POST', '/autenticacao', function (Requisicao $requisicao) use ($controlador): RespostaJson {
        return $controlador->autenticar($requisicao);
    }, false);
}

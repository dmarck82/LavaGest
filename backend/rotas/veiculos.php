<?php

declare(strict_types=1);

use LavaGest\Controladores\VeiculoControlador;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Http\Roteador;

function registrarRotasVeiculos(Roteador $roteador, VeiculoControlador $controlador): void
{
    // A função de cada rota só será executada quando chegar uma requisição correspondente.
    $roteador->adicionar('GET', '/veiculos', function () use ($controlador): RespostaJson {
        return $controlador->listar();
    });

    $roteador->adicionar('POST', '/veiculos', function (Requisicao $requisicao) use ($controlador): RespostaJson {
        return $controlador->criar($requisicao);
    });

    $roteador->adicionar('GET', '/veiculos/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->buscar($id);
    });

    $roteador->adicionar('PUT', '/veiculos/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->atualizar($requisicao, $id);
    });

    $roteador->adicionar('DELETE', '/veiculos/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->deletar($id);
    });
}

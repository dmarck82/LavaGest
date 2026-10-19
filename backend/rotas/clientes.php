<?php

declare(strict_types=1);

use LavaGest\Controladores\ClienteControlador;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Http\Roteador;

function registrarRotasClientes(Roteador $roteador, ClienteControlador $controlador): void
{
    // A função de cada rota só será executada quando chegar uma requisição correspondente.
    $roteador->adicionar('GET', '/clientes', function () use ($controlador): RespostaJson {
        return $controlador->listar();
    });

    $roteador->adicionar('POST', '/clientes', function (Requisicao $requisicao) use ($controlador): RespostaJson {
        return $controlador->criar($requisicao);
    });

    $roteador->adicionar('GET', '/clientes/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->buscar($id);
    });

    $roteador->adicionar('PUT', '/clientes/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->atualizar($requisicao, $id);
    });

    $roteador->adicionar('DELETE', '/clientes/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->deletar($id);
    });
}

<?php

declare(strict_types=1);

use LavaGest\Controladores\UsuarioControlador;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Http\Roteador;

function registrarRotasUsuarios(Roteador $roteador, UsuarioControlador $controlador): void
{
    // A função de cada rota só será executada quando chegar uma requisição correspondente.
    $roteador->adicionar('GET', '/usuarios', function () use ($controlador): RespostaJson {
        return $controlador->listar();
    });

    // false mantém o cadastro público: esta rota não exige token.
    $roteador->adicionar('POST', '/usuarios', function (Requisicao $requisicao) use ($controlador): RespostaJson {
        return $controlador->criar($requisicao);
    }, false);

    $roteador->adicionar('GET', '/usuarios/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->buscar($id);
    });

    $roteador->adicionar('PUT', '/usuarios/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->atualizar($requisicao, $id);
    });

    $roteador->adicionar('DELETE', '/usuarios/{id}', function (Requisicao $requisicao, array $parametros) use ($controlador): RespostaJson {
        $id = (int) $parametros['id'];

        return $controlador->deletar($id);
    });
}

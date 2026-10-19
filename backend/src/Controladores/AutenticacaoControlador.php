<?php

declare(strict_types=1);

namespace LavaGest\Controladores;

use LavaGest\Http\Campos;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Servicos\AutenticacaoServico;

class AutenticacaoControlador
{
    private AutenticacaoServico $servico;

    public function __construct(AutenticacaoServico $servico)
    {
        $this->servico = $servico;
    }

    public function autenticar(Requisicao $requisicao): RespostaJson
    {
        $dadosRecebidos = $requisicao->json();
        $tiposDosCampos = [
            'email' => 'texto',
            'senha' => 'texto',
        ];
        $camposObrigatorios = ['email', 'senha'];
        $dadosValidados = Campos::validar($dadosRecebidos, $tiposDosCampos, $camposObrigatorios);

        $email = $dadosValidados['email'];
        $senha = $dadosValidados['senha'];
        $autenticacao = $this->servico->autenticar($email, $senha);

        return new RespostaJson($autenticacao);
    }
}

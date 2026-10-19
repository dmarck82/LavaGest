import { URL_API } from './configuracao.js';
import { criarSessao } from './infraestrutura/sessao.js';
import { criarClienteHttp } from './infraestrutura/clienteHttp.js';
import { criarUsuarioServico } from './servicos/usuarioServico.js';
import { criarUsuarioTela } from './telas/usuarioTela.js';
import { iniciarUsuarios } from './paginas/usuariosPagina.js';

// Aqui montamos os objetos da página, como fazemos em Aplicacao.php no backend.
const sessao = criarSessao(sessionStorage, window.location);

if (sessao.exigirAutenticacao()) {
    const requisicao = criarClienteHttp(URL_API, sessao.obterToken);
    const usuarioServico = criarUsuarioServico(requisicao);
    const tela = criarUsuarioTela(document, bootstrap);

    function confirmar(texto) {
        return window.confirm(texto);
    }

    iniciarUsuarios(usuarioServico, tela, confirmar);
}

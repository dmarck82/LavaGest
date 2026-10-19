import { URL_API, URL_CEP } from './configuracao.js';
import { criarSessao } from './infraestrutura/sessao.js';
import { criarClienteHttp } from './infraestrutura/clienteHttp.js';
import { criarClienteServico } from './servicos/clienteServico.js';
import { criarClienteTela } from './telas/clienteTela.js';
import { iniciarClientes } from './paginas/clientesPagina.js';
import { criarCepServico } from './servicos/cepServico.js';

// Aqui montamos os objetos da página, como fazemos em Aplicacao.php no backend.
const sessao = criarSessao(sessionStorage, window.location);

if (sessao.exigirAutenticacao()) {
    const requisicao = criarClienteHttp(URL_API, sessao.obterToken);
    const clienteServico = criarClienteServico(requisicao);
    const cepServico = criarCepServico(URL_CEP);
    const tela = criarClienteTela(document, bootstrap);

    function confirmar(texto) {
        return window.confirm(texto);
    }

    iniciarClientes(clienteServico, cepServico, tela, confirmar);
}

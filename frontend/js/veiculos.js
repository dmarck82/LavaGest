import { URL_API, URL_FIPE } from './configuracao.js';
import { criarSessao } from './infraestrutura/sessao.js';
import { criarClienteHttp } from './infraestrutura/clienteHttp.js';
import { criarVeiculoServico } from './servicos/veiculoServico.js';
import { criarVeiculoTela } from './telas/veiculoTela.js';
import { iniciarVeiculos } from './paginas/veiculosPagina.js';
import { criarClienteServico } from './servicos/clienteServico.js';
import { criarFipeServico } from './servicos/fipeServico.js';

// Aqui montamos os objetos da página, como fazemos em Aplicacao.php no backend.
const sessao = criarSessao(sessionStorage, window.location);

if (sessao.exigirAutenticacao()) {
    const requisicao = criarClienteHttp(URL_API, sessao.obterToken);
    const veiculoServico = criarVeiculoServico(requisicao);
    const clienteServico = criarClienteServico(requisicao);
    const fipeServico = criarFipeServico(URL_FIPE);
    const tela = criarVeiculoTela(document, bootstrap);

    function confirmar(texto) {
        return window.confirm(texto);
    }

    iniciarVeiculos(veiculoServico, clienteServico, fipeServico, tela, confirmar);
}

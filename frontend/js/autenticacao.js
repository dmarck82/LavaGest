import { URL_API } from './configuracao.js';
import { criarSessao } from './infraestrutura/sessao.js';
import { criarClienteHttp } from './infraestrutura/clienteHttp.js';
import { criarAutenticacaoServico } from './servicos/autenticacaoServico.js';
import { criarAutenticacaoTela } from './telas/autenticacaoTela.js';
import { iniciarAutenticacao } from './paginas/autenticacaoPagina.js';

const sessao = criarSessao(sessionStorage, window.location);
const requisicao = criarClienteHttp(URL_API, sessao.obterToken);
const autenticacaoServico = criarAutenticacaoServico(requisicao);
const tela = criarAutenticacaoTela(document);

iniciarAutenticacao(autenticacaoServico, sessao, tela, window.location);

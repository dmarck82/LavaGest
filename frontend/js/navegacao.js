import { criarSessao } from './infraestrutura/sessao.js';

const sessao = criarSessao(sessionStorage, window.location);
const paginaAtual = document.body.dataset.pagina;
const itemAtivo = document.querySelector('[data-menu="' + paginaAtual + '"]');
const botaoSair = document.querySelector('#botao-sair');

if (itemAtivo) {
    itemAtivo.classList.add('active');
    itemAtivo.setAttribute('aria-current', 'page');
}

if (botaoSair) {
    botaoSair.addEventListener('click', sair);
}

function sair() {
    sessao.encerrar();
}

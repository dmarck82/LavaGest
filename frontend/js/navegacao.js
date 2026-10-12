const paginaAtual = document.body.dataset.pagina;
const itemAtivo = document.querySelector(
    '[data-menu="' + paginaAtual + '"]'
);
const botaoSair = document.querySelector('#botao-sair');

if (itemAtivo) {
    itemAtivo.classList.add('active');
    itemAtivo.setAttribute('aria-current', 'page');
}

if (botaoSair) {
    botaoSair.addEventListener('click', () => {
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('token_expira_em');

        window.location.replace('index.html');
    });
}

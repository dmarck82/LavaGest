import { requisicao } from './api.js';

const formulario = document.querySelector('#formulario-login');
const campoEmail = document.querySelector('#email');
const campoSenha = document.querySelector('#senha');
const botaoEntrar = document.querySelector('#botao-entrar');
const mensagem = document.querySelector('#mensagem');

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    ocultarMensagem();
    alterarBotao(true);

    try {
        const autenticacao = await requisicao(
            '/autenticacao',
            {
                method: 'POST',
                body: JSON.stringify({
                    email: campoEmail.value.trim(),
                    senha: campoSenha.value,
                }),
            }
        );

        sessionStorage.setItem('token', autenticacao.token);
        sessionStorage.setItem(
            'token_expira_em',
            autenticacao.expira_em
        );

        window.location.href = 'usuarios.html';
    } catch (erro) {
        mostrarMensagem(erro.message, 'danger');
    } finally {
        alterarBotao(false);
    }
});

function mostrarMensagem(texto, tipo) {
    mensagem.textContent = texto;
    mensagem.className = `alert alert-${tipo}`;
}

function ocultarMensagem() {
    mensagem.className = 'alert d-none';
    mensagem.textContent = '';
}

function alterarBotao(carregando) {
    botaoEntrar.disabled = carregando;

    botaoEntrar.innerHTML = carregando
        ? `
            <span
                class="spinner-border spinner-border-sm me-2"
                aria-hidden="true"
            ></span>
            Entrando...
        `
        : `
            <i class="bi bi-box-arrow-in-right me-1"></i>
            Entrar
        `;
}
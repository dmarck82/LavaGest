import { mostrarMensagem, ocultarMensagem } from '../compartilhado/mensagens.js';

export function criarAutenticacaoTela(documento) {
    const formulario = documento.querySelector('#formulario-login');
    const campoEmail = documento.querySelector('#email');
    const campoSenha = documento.querySelector('#senha');
    const botaoEntrar = documento.querySelector('#botao-entrar');
    const mensagem = documento.querySelector('#mensagem');

    function aoEntrar(acao) {
        formulario.addEventListener('submit', acao);
    }

    function lerDados() {
        return { email: campoEmail.value, senha: campoSenha.value };
    }

    function mostrarErro(texto) {
        mostrarMensagem(mensagem, texto);
    }

    function alterarCarregamento(carregando) {
        botaoEntrar.disabled = carregando;

        if (carregando) {
            ocultarMensagem(mensagem);
            botaoEntrar.textContent = 'Entrando...';
        } else {
            botaoEntrar.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i> Entrar';
        }
    }

    return {
        aoEntrar: aoEntrar,
        lerDados: lerDados,
        mostrarErro: mostrarErro,
        alterarCarregamento: alterarCarregamento,
    };
}

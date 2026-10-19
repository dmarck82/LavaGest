import { formatarTelefone, formatarData } from '../compartilhado/formatadores.js';
import { aplicarMascaraTelefone } from '../compartilhado/mascaras.js';
import { mostrarMensagem as mostrarAlerta, ocultarMensagem as ocultarAlerta } from '../compartilhado/mensagens.js';
import { criarCelula, criarAcoes, mostrarAvisoTabela } from '../compartilhado/tabelas.js';

export function criarUsuarioTela(documento, bootstrap) {
    const lista = documento.querySelector('#lista-usuarios');
    const mensagem = documento.querySelector('#mensagem');
    const mensagemModal = documento.querySelector('#mensagem-modal');
    const formulario = documento.querySelector('#formulario-usuario');
    const modal = new bootstrap.Modal(documento.querySelector('#modal-usuario'));
    const titulo = documento.querySelector('#titulo-modal-usuario');
    const botaoNovo = documento.querySelector('#botao-novo-usuario');
    const botaoSalvar = formulario.querySelector('[type="submit"]');
    const grupoSenha = documento.querySelector('#campo-senha');
    const campoNome = documento.querySelector('#nome');
    const campoEmail = documento.querySelector('#email');
    const campoTelefone = documento.querySelector('#telefone');
    const campoSenha = documento.querySelector('#senha');

    aplicarMascaraTelefone(campoTelefone);

    function aoNovo(acao) {
        botaoNovo.addEventListener('click', acao);
    }

    function aoSalvar(acao) {
        formulario.addEventListener('submit', acao);
    }

    function limparMensagens() {
        ocultarAlerta(mensagem);
        ocultarAlerta(mensagemModal);
    }

    function abrirCadastro() {
        formulario.reset();
        limparMensagens();
        titulo.textContent = 'Novo usuário';
        grupoSenha.classList.remove('d-none');
        campoSenha.required = true;
        modal.show();
    }

    function abrirEdicao(usuario) {
        limparMensagens();
        campoNome.value = usuario.nome;
        campoEmail.value = usuario.email;
        campoTelefone.value = formatarTelefone(usuario.telefone);
        campoSenha.value = '';
        campoSenha.required = false;
        grupoSenha.classList.add('d-none');
        titulo.textContent = 'Editar usuário';
        modal.show();
    }

    function lerDados() {
        return {
            nome: campoNome.value,
            email: campoEmail.value,
            telefone: campoTelefone.value,
            senha: campoSenha.value,
        };
    }

    function mostrarUsuarios(usuarios, aoEditar, aoExcluir) {
        lista.replaceChildren();

        if (usuarios.length === 0) {
            mostrarAvisoTabela(lista, 6, 'Nenhum usuário cadastrado.');
            return;
        }

        for (const usuario of usuarios) {
            const linha = documento.createElement('tr');
            let telefone = '-';

            if (usuario.telefone) {
                telefone = formatarTelefone(usuario.telefone);
            }

            linha.appendChild(criarCelula(documento, usuario.id));
            linha.appendChild(criarCelula(documento, usuario.nome));
            linha.appendChild(criarCelula(documento, usuario.email));
            linha.appendChild(criarCelula(documento, telefone));
            linha.appendChild(criarCelula(documento, formatarData(usuario.ultimo_login, 'Nunca')));
            linha.appendChild(criarAcoes(documento, usuario, aoEditar, aoExcluir));
            lista.appendChild(linha);
        }
    }

    function mostrarErroCarregamento() {
        mostrarAvisoTabela(lista, 6, 'Não foi possível carregar os usuários.', true);
    }

    function mostrarMensagem(texto, tipo) {
        mostrarAlerta(mensagem, texto, tipo);
    }

    function mostrarErroFormulario(texto) {
        mostrarAlerta(mensagemModal, texto);
    }

    function alterarSalvamento(salvando) {
        botaoSalvar.disabled = salvando;
    }

    function fecharFormulario() {
        modal.hide();
    }

    return {
        aoNovo: aoNovo, aoSalvar: aoSalvar,
        abrirCadastro: abrirCadastro, abrirEdicao: abrirEdicao, lerDados: lerDados,
        mostrarUsuarios: mostrarUsuarios, mostrarErroCarregamento: mostrarErroCarregamento,
        mostrarMensagem: mostrarMensagem, mostrarErroFormulario: mostrarErroFormulario,
        alterarSalvamento: alterarSalvamento, fecharFormulario: fecharFormulario,
    };
}

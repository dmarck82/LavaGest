import { requisicao } from './api.js';
import { apenasNumeros, aplicarMascaraTelefone, formatarTelefone, telefoneValido, } from './utilitarios.js';

const listaUsuarios = document.querySelector('#lista-usuarios');
const mensagem = document.querySelector('#mensagem');
const botaoSair = document.querySelector('#botao-sair');

const botaoNovoUsuario = document.querySelector('#botao-novo-usuario');
const formularioUsuario = document.querySelector('#formulario-usuario');
const modalUsuario = new bootstrap.Modal(document.querySelector('#modal-usuario'));

const tituloModalUsuario = document.querySelector('#titulo-modal-usuario');
const campoSenha = document.querySelector('#campo-senha');
const campoNome = document.querySelector('#nome');
const campoEmail = document.querySelector('#email');
const campoTelefone = document.querySelector('#telefone');
const campoSenhaInput = document.querySelector('#senha');

aplicarMascaraTelefone(campoTelefone);

let usuarioEmEdicaoId = null;

if (!sessionStorage.getItem('token')) {
    window.location.replace('index.html');
}

botaoSair.addEventListener('click', () => {
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('token_expira_em');

    window.location.replace('index.html');
});

botaoNovoUsuario.addEventListener('click', () => {
    usuarioEmEdicaoId = null;

    formularioUsuario.reset();

    tituloModalUsuario.textContent = 'Novo usuário';

    campoSenha.classList.remove('d-none');
    campoSenhaInput.required = true;

    modalUsuario.show();
});

formularioUsuario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const dados = {
        nome: campoNome.value.trim(),
        email: campoEmail.value.trim(),
        telefone: apenasNumeros(campoTelefone.value),
    };

    if (!telefoneValido(dados.telefone)) {
        mostrarMensagem(
            'Informe um telefone fixo ou celular válido.',
            'danger'
        );

        return;
    }

    const editando = usuarioEmEdicaoId !== null;

    if (!editando) {
        dados.senha = campoSenhaInput.value;
    }

    try {
        await requisicao(
            editando ? `/usuarios/${usuarioEmEdicaoId}` : '/usuarios',
            {
                method: editando ? 'PUT' : 'POST',
                body: JSON.stringify(dados),
            }
        );

        modalUsuario.hide();

        mostrarMensagem(editando ? 'Usuário atualizado com sucesso.' : 'Usuário criado com sucesso.', 'success');

        carregarUsuarios();
    } catch (erro) {
        mostrarMensagem(erro.message, 'danger');
    }
});

async function carregarUsuarios() {
    try {
        const usuarios = await requisicao('/usuarios');

        listaUsuarios.replaceChildren();

        if (usuarios.length === 0) {
            mostrarListaVazia();
            return;
        }

        usuarios.forEach((usuario) => {
            listaUsuarios.appendChild(criarLinhaUsuario(usuario));
        });
    } catch (erro) {
        listaUsuarios.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger py-4">
                    Não foi possível carregar os usuários.
                </td>
            </tr>
        `;

        mostrarMensagem(erro.message, 'danger');
    }
}

function criarLinhaUsuario(usuario) {
    const linha = document.createElement('tr');

    linha.appendChild(criarCelula(usuario.id));
    linha.appendChild(criarCelula(usuario.nome));
    linha.appendChild(criarCelula(usuario.email));
    linha.appendChild(criarCelula(usuario.telefone ? formatarTelefone(usuario.telefone) : '-'));
    linha.appendChild(criarCelula(formatarData(usuario.ultimo_login)));

    const colunaAcoes = document.createElement('td');
    colunaAcoes.classList.add('text-end');

    const botaoEditar = document.createElement('button');

    botaoEditar.type = 'button';
    botaoEditar.className = 'btn btn-outline-primary btn-sm me-1';
    botaoEditar.title = 'Editar usuário';
    botaoEditar.innerHTML = `<i class="bi bi-pencil" aria-hidden="true"></i>`;

    botaoEditar.addEventListener('click', () => {
        editarUsuario(usuario);
    });

    colunaAcoes.appendChild(botaoEditar);

    const botaoExcluir = document.createElement('button');

    botaoExcluir.type = 'button';
    botaoExcluir.className = 'btn btn-outline-danger btn-sm';
    botaoExcluir.title = 'Excluir usuário';
    botaoExcluir.innerHTML = `<i class="bi bi-trash" aria-hidden="true"></i>`;

    botaoExcluir.addEventListener('click', () => {
        excluirUsuario(usuario.id, usuario.nome);
    });

    colunaAcoes.appendChild(botaoExcluir);
    linha.appendChild(colunaAcoes);

    return linha;
}

function criarCelula(texto) {
    const coluna = document.createElement('td');

    coluna.textContent = texto;

    return coluna;
}

function mostrarListaVazia() {
    listaUsuarios.innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-secondary py-4">
                Nenhum usuário cadastrado.
            </td>
        </tr>
    `;
}

function formatarData(data) {
    if (!data) {
        return 'Nunca';
    }

    const dataFormatada = new Date(
        data.replace(' ', 'T')
    );

    return new Intl.DateTimeFormat(
        'pt-BR',
        {
            dateStyle: 'short',
            timeStyle: 'short',
        }
    ).format(dataFormatada);
}

function mostrarMensagem(texto, tipo) {
    mensagem.textContent = texto;
    mensagem.className = `alert alert-${tipo}`;
}

async function excluirUsuario(id, nome) {
    const confirmou = window.confirm(
        `Deseja excluir o usuário "${nome}"?`
    );

    if (!confirmou) {
        return;
    }

    try {
        await requisicao(`/usuarios/${id}`, {
            method: 'DELETE',
        });

        mostrarMensagem(
            'Usuário excluído com sucesso.',
            'success'
        );

        carregarUsuarios();
    } catch (erro) {
        mostrarMensagem(erro.message, 'danger');
    }
}

function editarUsuario(usuario) {
    usuarioEmEdicaoId = usuario.id;

    campoNome.value = usuario.nome;
    campoEmail.value = usuario.email;
    campoTelefone.value = formatarTelefone(usuario.telefone ?? '');
    campoSenhaInput.value = '';

    tituloModalUsuario.textContent = 'Editar usuário';

    campoSenha.classList.add('d-none');
    campoSenhaInput.required = false;

    modalUsuario.show();
}

carregarUsuarios();
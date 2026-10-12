import { requisicao } from './api.js';
import {
    apenasNumeros,
    aplicarMascaraCep,
    aplicarMascaraTelefone,
    formatarCep,
    formatarTelefone,
    telefoneValido,
} from './utilitarios.js';

const listaClientes = document.querySelector('#lista-clientes');
const mensagem = document.querySelector('#mensagem');
const mensagemModal = document.querySelector('#mensagem-modal');
const busca = document.querySelector('#busca');
const botaoNovoCliente = document.querySelector('#botao-novo-cliente');
const formularioCliente = document.querySelector('#formulario-cliente');
const modalCliente = new bootstrap.Modal(document.querySelector('#modal-cliente'));

const tituloModalCliente = document.querySelector('#titulo-modal-cliente');
const campoNome = document.querySelector('#nome');
const campoWhatsapp = document.querySelector('#whatsapp');
const campoTelefone = document.querySelector('#telefone');
const campoEmail = document.querySelector('#email');
const campoCep = document.querySelector('#cep');
const statusCep = document.querySelector('#status-cep');
const campoUf = document.querySelector('#uf');
const campoCidade = document.querySelector('#cidade');
const campoEndereco = document.querySelector('#endereco');
const campoBairro = document.querySelector('#bairro');

let clienteEmEdicaoId = null;
let clientesCarregados = [];
let ultimoCepConsultado = null;

if (!sessionStorage.getItem('token')) {
    window.location.replace('index.html');
}

aplicarMascaraTelefone(campoWhatsapp);
aplicarMascaraTelefone(campoTelefone);
aplicarMascaraCep(campoCep);

campoCep.addEventListener('input', () => {
    const cep = apenasNumeros(campoCep.value);

    if (cep.length < 8) {
        ultimoCepConsultado = null;
        ocultarStatusCep();
        return;
    }

    if (cep === ultimoCepConsultado) {
        return;
    }

    consultarCep(cep);
});

botaoNovoCliente.addEventListener('click', () => {
    clienteEmEdicaoId = null;
    formularioCliente.reset();
    ultimoCepConsultado = null;
    ocultarMensagem();
    ocultarMensagemModal();
    ocultarStatusCep();
    tituloModalCliente.textContent = 'Novo cliente';
    modalCliente.show();
});

busca.addEventListener('input', () => {
    renderizarClientes(clientesCarregados);
});

formularioCliente.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const dados = {
        nome: campoNome.value.trim(),
        whatsapp: apenasNumeros(campoWhatsapp.value),
        telefone: valorOpcional(campoTelefone, true),
        email: valorOpcional(campoEmail),
        uf: valorOpcional(campoUf)?.toUpperCase() ?? null,
        cidade: valorOpcional(campoCidade),
        endereco: valorOpcional(campoEndereco),
        bairro: valorOpcional(campoBairro),
        cep: valorOpcional(campoCep, true),
    };

    if (!telefoneValido(dados.whatsapp)) {
        mostrarMensagemModal(
            'Informe um WhatsApp fixo ou celular válido.'
        );
        return;
    }

    if (dados.telefone !== null && !telefoneValido(dados.telefone)) {
        mostrarMensagemModal(
            'Informe um telefone fixo ou celular válido.'
        );
        return;
    }

    if (dados.cep !== null && dados.cep.length !== 8) {
        mostrarMensagemModal('Informe um CEP válido.');
        return;
    }

    const editando = clienteEmEdicaoId !== null;

    try {
        await requisicao(
            editando ? '/clientes/' + clienteEmEdicaoId : '/clientes',
            {
                method: editando ? 'PUT' : 'POST',
                body: JSON.stringify(dados),
            }
        );

        modalCliente.hide();
        mostrarMensagem(
            editando ? 'Cliente atualizado com sucesso.' : 'Cliente criado com sucesso.',
            'success'
        );
        carregarClientes();
    } catch (erro) {
        mostrarMensagemModal(erro.message);
    }
});

async function carregarClientes() {
    try {
        clientesCarregados = await requisicao('/clientes');
        renderizarClientes(clientesCarregados);
    } catch (erro) {
        listaClientes.innerHTML =
            '<tr><td colspan="7" class="text-center text-danger py-4">' +
            'Não foi possível carregar os clientes.</td></tr>';
        mostrarMensagem(erro.message, 'danger');
    }
}

function renderizarClientes(clientes) {
    const termo = busca.value.trim().toLocaleLowerCase('pt-BR');
    const clientesFiltrados = clientes.filter((cliente) => {
        const texto = [
            cliente.nome,
            cliente.whatsapp,
            cliente.cidade,
        ].filter(Boolean).join(' ').toLocaleLowerCase('pt-BR');

        return texto.includes(termo);
    });

    listaClientes.replaceChildren();

    if (clientesFiltrados.length === 0) {
        mostrarListaVazia(termo !== '');
        return;
    }

    clientesFiltrados.forEach((cliente) => {
        listaClientes.appendChild(criarLinhaCliente(cliente));
    });
}

function criarLinhaCliente(cliente) {
    const linha = document.createElement('tr');

    linha.appendChild(criarCelula(cliente.id));
    linha.appendChild(criarCelula(cliente.nome));
    linha.appendChild(criarCelula(formatarTelefone(cliente.whatsapp)));
    linha.appendChild(criarCelula(
        cliente.telefone ? formatarTelefone(cliente.telefone) : '-'
    ));
    linha.appendChild(criarCelula(formatarLocal(cliente)));
    linha.appendChild(criarCelula(formatarData(cliente.criado_em)));

    const colunaAcoes = document.createElement('td');
    colunaAcoes.classList.add('text-end', 'text-nowrap');

    const botaoEditar = document.createElement('button');
    botaoEditar.type = 'button';
    botaoEditar.className = 'btn btn-outline-primary btn-sm me-1';
    botaoEditar.title = 'Editar cliente';
    botaoEditar.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';
    botaoEditar.addEventListener('click', () => editarCliente(cliente));

    const botaoExcluir = document.createElement('button');
    botaoExcluir.type = 'button';
    botaoExcluir.className = 'btn btn-outline-danger btn-sm';
    botaoExcluir.title = 'Excluir cliente';
    botaoExcluir.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
    botaoExcluir.addEventListener('click', () => excluirCliente(cliente.id, cliente.nome));

    colunaAcoes.append(botaoEditar, botaoExcluir);
    linha.appendChild(colunaAcoes);

    return linha;
}

function editarCliente(cliente) {
    clienteEmEdicaoId = cliente.id;
    ocultarMensagem();
    ocultarMensagemModal();
    campoNome.value = cliente.nome;
    campoWhatsapp.value = formatarTelefone(cliente.whatsapp);
    campoTelefone.value = formatarTelefone(cliente.telefone ?? '');
    campoEmail.value = cliente.email ?? '';
    campoCep.value = formatarCep(cliente.cep ?? '');
    ultimoCepConsultado = apenasNumeros(cliente.cep ?? '');
    campoUf.value = cliente.uf ?? '';
    campoCidade.value = cliente.cidade ?? '';
    campoEndereco.value = cliente.endereco ?? '';
    campoBairro.value = cliente.bairro ?? '';
    tituloModalCliente.textContent = 'Editar cliente';
    modalCliente.show();
}

async function excluirCliente(id, nome) {
    if (!window.confirm('Deseja excluir o cliente "' + nome + '"?')) {
        return;
    }

    try {
        await requisicao('/clientes/' + id, { method: 'DELETE' });
        mostrarMensagem('Cliente excluído com sucesso.', 'success');
        carregarClientes();
    } catch (erro) {
        mostrarMensagem(erro.message, 'danger');
    }
}

function valorOpcional(campo, somenteNumeros = false) {
    const valor = somenteNumeros ? apenasNumeros(campo.value) : campo.value.trim();
    return valor === '' ? null : valor;
}

async function consultarCep(cep) {
    ultimoCepConsultado = cep;
    mostrarStatusCep('Buscando endereço...', 'text-secondary');

    try {
        const resposta = await fetch(
            'https://viacep.com.br/ws/' + cep + '/json/'
        );

        if (!resposta.ok) {
            throw new Error('Não foi possível consultar o CEP.');
        }

        const endereco = await resposta.json();

        if (apenasNumeros(campoCep.value) !== cep) {
            return;
        }

        if (endereco.erro) {
            mostrarStatusCep('CEP não encontrado.', 'text-danger');
            return;
        }

        campoUf.value = endereco.uf ?? '';
        campoCidade.value = endereco.localidade ?? '';
        campoEndereco.value = endereco.logradouro ?? '';
        campoBairro.value = endereco.bairro ?? '';
        campoEndereco.focus();
        campoEndereco.setSelectionRange(
            campoEndereco.value.length,
            campoEndereco.value.length
        );

        mostrarStatusCep('Endereço preenchido.', 'text-success');

    } catch (erro) {
        mostrarStatusCep('Não foi possível consultar o CEP. Preencha o endereço manualmente.', 'text-danger');
    }
}

function mostrarStatusCep(texto, classe) {
    statusCep.textContent = texto;
    statusCep.className = 'form-text ' + classe;
}

function ocultarStatusCep() {
    statusCep.textContent = '';
    statusCep.className = 'form-text';
}

function criarCelula(texto) {
    const coluna = document.createElement('td');
    coluna.textContent = texto;
    return coluna;
}

function formatarLocal(cliente) {
    return [cliente.cidade, cliente.uf].filter(Boolean).join(' - ') || '-';
}

function formatarData(data) {
    if (!data) {
        return '-';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(data.replace(' ', 'T')));
}

function mostrarListaVazia(temBusca) {
    const texto = temBusca
        ? 'Nenhum cliente encontrado.'
        : 'Nenhum cliente cadastrado.';

    listaClientes.innerHTML =
        '<tr><td colspan="7" class="text-center text-secondary py-4">' +
        texto +
        '</td></tr>';
}

function mostrarMensagem(texto, tipo) {
    mensagem.textContent = texto;
    mensagem.className = 'alert alert-' + tipo;
}

function ocultarMensagem() {
    mensagem.textContent = '';
    mensagem.className = 'alert d-none';
}

function mostrarMensagemModal(texto) {
    mensagemModal.textContent = texto;
    mensagemModal.className = 'alert alert-danger';
}

function ocultarMensagemModal() {
    mensagemModal.textContent = '';
    mensagemModal.className = 'alert d-none';
}

carregarClientes();

import { requisicao } from './api.js';
import { aplicarMascaraPlaca, formatarPlaca } from './utilitarios.js';

const URL_FIPE = 'https://parallelum.com.br/fipe/api/v1';
const lista = document.querySelector('#lista-veiculos');
const mensagem = document.querySelector('#mensagem');
const mensagemModal = document.querySelector('#mensagem-modal');
const formulario = document.querySelector('#formulario-veiculo');
const modal = new bootstrap.Modal(document.querySelector('#modal-veiculo'));
const botaoNovo = document.querySelector('#botao-novo-veiculo');
const titulo = document.querySelector('#titulo-modal-veiculo');

const campos = {
    cliente_id: document.querySelector('#cliente-id'),
    placa: document.querySelector('#placa'),
    tipo_veiculo: document.querySelector('#tipo-veiculo'),
    marca: document.querySelector('#marca'),
    modelo: document.querySelector('#modelo'),
    categoria: document.querySelector('#categoria'),
    cor: document.querySelector('#cor'),
    ano_fabricacao: document.querySelector('#ano-fabricacao'),
    observacoes: document.querySelector('#observacoes'),
};

const listaModelos = document.querySelector('#lista-modelos');
const listaMarcas = document.querySelector('#lista-marcas');
let veiculoEmEdicaoId = null;
let clientes = [];
let marcasCarregadas = [];

if (!sessionStorage.getItem('token')) window.location.replace('index.html');

aplicarMascaraPlaca(campos.placa);

carregarMarcas();

campos.tipo_veiculo.addEventListener('change', () => carregarMarcas());

campos.marca.addEventListener('change', () => carregarModelos());

botaoNovo.addEventListener('click', () => {
    veiculoEmEdicaoId = null;
    formulario.reset();
    ocultarMensagens();
    titulo.textContent = 'Novo veículo';
    modal.show();
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const ano = campos.ano_fabricacao.value;
    const dados = {
        cliente_id: Number(campos.cliente_id.value),
        placa: campos.placa.value,
        marca: campos.marca.value,
        modelo: campos.modelo.value,
        categoria: campos.categoria.value,
        cor: opcional(campos.cor.value),
        ano_fabricacao: ano === '' ? null : Number(ano),
        observacoes: opcional(campos.observacoes.value),
    };
    const editando = veiculoEmEdicaoId !== null;
    try {
        await requisicao(editando ? '/veiculos/' + veiculoEmEdicaoId : '/veiculos', {
            method: editando ? 'PUT' : 'POST',
            body: JSON.stringify(dados),
        });
        modal.hide();
        mostrarMensagem(editando ? 'Veículo atualizado com sucesso.' : 'Veículo criado com sucesso.', 'success');
        carregarDados();
    } catch (erro) {
        mensagemModal.textContent = erro.message;
        mensagemModal.className = 'alert alert-danger';
    }
});

async function carregarDados() {
    try {
        const respostas = await Promise.all([requisicao('/clientes'), requisicao('/veiculos')]);
        clientes = respostas[0];
        preencherClientes();
        renderizar(respostas[1]);
    } catch (erro) {
        lista.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Não foi possível carregar os veículos.</td></tr>';
        mostrarMensagem(erro.message, 'danger');
    }
}

function preencherClientes() {
    const selecionado = campos.cliente_id.value;
    campos.cliente_id.replaceChildren(new Option('Selecione um cliente', ''));
    clientes.forEach((cliente) => campos.cliente_id.add(new Option(cliente.nome, cliente.id)));
    campos.cliente_id.value = selecionado;
}

function renderizar(veiculos) {
    lista.replaceChildren();
    if (veiculos.length === 0) {
        lista.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4">Nenhum veículo cadastrado.</td></tr>';
        return;
    }
    veiculos.forEach((veiculo) => lista.appendChild(criarLinha(veiculo)));
}

function criarLinha(veiculo) {
    const linha = document.createElement('tr');
    const cliente = clientes.find((item) => item.id === veiculo.cliente_id);
    [formatarPlaca(veiculo.placa), [veiculo.marca, veiculo.modelo].filter(Boolean).join(' '), capitalizar(veiculo.categoria), cliente?.nome ?? '-'].forEach((valor) => {
        const coluna = document.createElement('td');
        coluna.textContent = valor;
        linha.appendChild(coluna);
    });
    const acoes = document.createElement('td');
    acoes.className = 'text-end text-nowrap';
    acoes.innerHTML = '<button type="button" class="btn btn-outline-primary btn-sm me-1" title="Editar"><i class="bi bi-pencil"></i></button><button type="button" class="btn btn-outline-danger btn-sm" title="Excluir"><i class="bi bi-trash"></i></button>';
    const [editar, excluir] = acoes.querySelectorAll('button');
    editar.addEventListener('click', () => editarVeiculo(veiculo));
    excluir.addEventListener('click', () => excluirVeiculo(veiculo));
    linha.appendChild(acoes);
    return linha;
}

async function editarVeiculo(veiculo) {
    veiculoEmEdicaoId = veiculo.id;
    ocultarMensagens();
    Object.entries(campos).forEach(([nome, campo]) => campo.value = veiculo[nome] ?? '');
    campos.placa.value = formatarPlaca(veiculo.placa);
    campos.tipo_veiculo.value = veiculo.categoria === 'moto' ? 'motos' : 'carros';
    await carregarMarcas(veiculo.marca);
    await carregarModelos(veiculo.modelo);
    titulo.textContent = 'Editar veículo';
    modal.show();
}

async function excluirVeiculo(veiculo) {
    if (!window.confirm('Deseja excluir o veículo de placa ' + veiculo.placa + '?')) return;
    try {
        await requisicao('/veiculos/' + veiculo.id, { method: 'DELETE' });
        mostrarMensagem('Veículo excluído com sucesso.', 'success');
        carregarDados();
    } catch (erro) {
        mostrarMensagem(erro.message, 'danger');
    }
}

function opcional(valor) {
    const texto = valor.trim(); return texto === '' ? null : texto;
}

function nomePrincipalDoModelo(nome) {
    return nome.trim().split(/\s+/)[0];
}

async function carregarMarcas(nomeSelecionado = '') {
    listaMarcas.replaceChildren();
    listaModelos.replaceChildren();
    try {
        const resposta = await fetch(URL_FIPE + '/' + campos.tipo_veiculo.value + '/marcas');
        if (!resposta.ok) throw new Error('Não foi possível carregar as marcas.');
        marcasCarregadas = await resposta.json();
        marcasCarregadas.forEach((marca) => {
            listaMarcas.appendChild(new Option(marca.nome, marca.nome));
        });
        campos.marca.value = nomeSelecionado;
    } catch (erro) {
        mostrarMensagemModal(erro.message);
    }
}

async function carregarModelos(modeloSelecionado = '') {
    const marca = marcasCarregadas.find(
        (item) => item.nome === campos.marca.value
    );
    listaModelos.replaceChildren();
    if (!marca) {
        return;
    }
    try {
        const resposta = await fetch(URL_FIPE + '/' + campos.tipo_veiculo.value + '/marcas/' + marca.codigo + '/modelos');
        if (!resposta.ok) throw new Error('Não foi possível carregar os modelos.');
        const dados = await resposta.json();
        const modelos = [...new Set(
            dados.modelos.map((modelo) => nomePrincipalDoModelo(modelo.nome))
        )].sort();
        modelos.forEach((modelo) => listaModelos.appendChild(new Option(modelo, modelo)));
        if (modeloSelecionado) campos.modelo.value = modeloSelecionado;
    } catch (erro) {
        mostrarMensagemModal(erro.message);
    }
}

function capitalizar(valor) {
    return valor.charAt(0).toUpperCase() + valor.slice(1);
}

function mostrarMensagem(texto, tipo) {
    mensagem.textContent = texto; mensagem.className = 'alert alert-' + tipo;
}

function mostrarMensagemModal(texto) {
    mensagemModal.textContent = texto; mensagemModal.className = 'alert alert-danger';
}

function ocultarMensagens() {
    mensagem.className = 'alert d-none'; mensagemModal.className = 'alert d-none';
}

carregarDados();

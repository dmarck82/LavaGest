import { formatarTelefone, formatarCep, formatarData } from '../compartilhado/formatadores.js';
import { aplicarMascaraTelefone, aplicarMascaraCep } from '../compartilhado/mascaras.js';
import { mostrarMensagem as mostrarAlerta, ocultarMensagem as ocultarAlerta } from '../compartilhado/mensagens.js';
import { criarCelula, criarAcoes, mostrarAvisoTabela } from '../compartilhado/tabelas.js';

// Esta tela conhece o HTML, mas não sabe como salvar um cliente na API.
export function criarClienteTela(documento, bootstrap) {
    const lista = documento.querySelector('#lista-clientes');
    const mensagem = documento.querySelector('#mensagem');
    const mensagemModal = documento.querySelector('#mensagem-modal');
    const formulario = documento.querySelector('#formulario-cliente');
    const elementoModal = documento.querySelector('#modal-cliente');
    const modal = new bootstrap.Modal(elementoModal);
    const titulo = documento.querySelector('#titulo-modal-cliente');
    const botaoNovo = documento.querySelector('#botao-novo-cliente');
    const botaoSalvar = formulario.querySelector('[type="submit"]');
    const busca = documento.querySelector('#busca');
    const statusCep = documento.querySelector('#status-cep');
    const campos = {
        nome: documento.querySelector('#nome'),
        whatsapp: documento.querySelector('#whatsapp'),
        telefone: documento.querySelector('#telefone'),
        email: documento.querySelector('#email'),
        cep: documento.querySelector('#cep'),
        uf: documento.querySelector('#uf'),
        cidade: documento.querySelector('#cidade'),
        endereco: documento.querySelector('#endereco'),
        bairro: documento.querySelector('#bairro'),
    };

    aplicarMascaraTelefone(campos.whatsapp);
    aplicarMascaraTelefone(campos.telefone);
    aplicarMascaraCep(campos.cep);

    function aoNovo(acao) {
        botaoNovo.addEventListener('click', acao);
    }

    function aoSalvar(acao) {
        formulario.addEventListener('submit', acao);
    }

    function aoBuscar(acao) {
        busca.addEventListener('input', acao);
    }

    function aoAlterarCep(acao) {
        campos.cep.addEventListener('input', acao);
    }

    function aoFechar(acao) {
        elementoModal.addEventListener('hidden.bs.modal', acao);
    }

    function limparMensagens() {
        ocultarAlerta(mensagem);
        ocultarAlerta(mensagemModal);
        mostrarStatusCep('');
    }

    function abrirCadastro() {
        formulario.reset();
        limparMensagens();
        titulo.textContent = 'Novo cliente';
        modal.show();
    }

    function abrirEdicao(cliente) {
        limparMensagens();
        campos.nome.value = cliente.nome;
        campos.whatsapp.value = formatarTelefone(cliente.whatsapp);
        campos.telefone.value = formatarTelefone(cliente.telefone);
        campos.email.value = cliente.email ?? '';
        campos.cep.value = formatarCep(cliente.cep);
        campos.uf.value = cliente.uf ?? '';
        campos.cidade.value = cliente.cidade ?? '';
        campos.endereco.value = cliente.endereco ?? '';
        campos.bairro.value = cliente.bairro ?? '';
        titulo.textContent = 'Editar cliente';
        modal.show();
    }

    function lerDados() {
        return {
            nome: campos.nome.value,
            whatsapp: campos.whatsapp.value,
            telefone: campos.telefone.value,
            email: campos.email.value,
            cep: campos.cep.value,
            uf: campos.uf.value,
            cidade: campos.cidade.value,
            endereco: campos.endereco.value,
            bairro: campos.bairro.value,
        };
    }

    function obterBusca() {
        return busca.value;
    }

    function obterCep() {
        return campos.cep.value;
    }

    function preencherEndereco(endereco) {
        campos.uf.value = endereco.uf;
        campos.cidade.value = endereco.cidade;
        campos.endereco.value = endereco.endereco;
        campos.bairro.value = endereco.bairro;
    }

    function mostrarClientes(clientes, aoEditar, aoExcluir, temBusca) {
        lista.replaceChildren();

        if (clientes.length === 0) {
            let texto = 'Nenhum cliente cadastrado.';

            if (temBusca) {
                texto = 'Nenhum cliente encontrado.';
            }

            mostrarAvisoTabela(lista, 7, texto);
            return;
        }

        for (const cliente of clientes) {
            const linha = documento.createElement('tr');
            let telefone = '-';

            if (cliente.telefone) {
                telefone = formatarTelefone(cliente.telefone);
            }

            let local = cliente.cidade ?? '';

            if (cliente.uf) {
                if (local !== '') {
                    local += ' - ';
                }

                local += cliente.uf;
            }

            if (local === '') {
                local = '-';
            }

            linha.appendChild(criarCelula(documento, cliente.id));
            linha.appendChild(criarCelula(documento, cliente.nome));
            linha.appendChild(criarCelula(documento, formatarTelefone(cliente.whatsapp)));
            linha.appendChild(criarCelula(documento, telefone));
            linha.appendChild(criarCelula(documento, local));
            linha.appendChild(criarCelula(documento, formatarData(cliente.criado_em)));
            linha.appendChild(criarAcoes(documento, cliente, aoEditar, aoExcluir));
            lista.appendChild(linha);
        }
    }

    function mostrarErroCarregamento() {
        mostrarAvisoTabela(lista, 7, 'Não foi possível carregar os clientes.', true);
    }

    function mostrarMensagem(texto, tipo) {
        mostrarAlerta(mensagem, texto, tipo);
    }

    function mostrarErroFormulario(texto) {
        mostrarAlerta(mensagemModal, texto);
    }

    function mostrarStatusCep(texto, classe = 'text-secondary') {
        statusCep.textContent = texto;
        statusCep.className = 'form-text ' + classe;
    }

    function alterarSalvamento(salvando) {
        botaoSalvar.disabled = salvando;
    }

    function fecharFormulario() {
        modal.hide();
    }

    return {
        aoNovo: aoNovo, aoSalvar: aoSalvar, aoBuscar: aoBuscar,
        aoAlterarCep: aoAlterarCep, aoFechar: aoFechar,
        abrirCadastro: abrirCadastro, abrirEdicao: abrirEdicao,
        lerDados: lerDados, obterBusca: obterBusca, obterCep: obterCep,
        preencherEndereco: preencherEndereco, mostrarClientes: mostrarClientes,
        mostrarErroCarregamento: mostrarErroCarregamento, mostrarMensagem: mostrarMensagem,
        mostrarErroFormulario: mostrarErroFormulario, mostrarStatusCep: mostrarStatusCep,
        alterarSalvamento: alterarSalvamento, fecharFormulario: fecharFormulario,
    };
}

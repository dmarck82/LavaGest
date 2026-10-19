import { formatarPlaca, capitalizar } from '../compartilhado/formatadores.js';
import { aplicarMascaraPlaca } from '../compartilhado/mascaras.js';
import { mostrarMensagem as mostrarAlerta, ocultarMensagem as ocultarAlerta } from '../compartilhado/mensagens.js';
import { criarCelula, criarAcoes, mostrarAvisoTabela } from '../compartilhado/tabelas.js';

export function criarVeiculoTela(documento, bootstrap) {
    const lista = documento.querySelector('#lista-veiculos');
    const mensagem = documento.querySelector('#mensagem');
    const mensagemModal = documento.querySelector('#mensagem-modal');
    const formulario = documento.querySelector('#formulario-veiculo');
    const elementoModal = documento.querySelector('#modal-veiculo');
    const modal = new bootstrap.Modal(elementoModal);
    const titulo = documento.querySelector('#titulo-modal-veiculo');
    const botaoNovo = documento.querySelector('#botao-novo-veiculo');
    const botaoSalvar = formulario.querySelector('[type="submit"]');
    const listaMarcas = documento.querySelector('#lista-marcas');
    const listaModelos = documento.querySelector('#lista-modelos');
    const campos = {
        cliente_id: documento.querySelector('#cliente-id'),
        placa: documento.querySelector('#placa'),
        marca: documento.querySelector('#marca'),
        modelo: documento.querySelector('#modelo'),
        categoria: documento.querySelector('#categoria'),
        tipo_veiculo: documento.querySelector('#tipo-veiculo'),
        cor: documento.querySelector('#cor'),
        ano_fabricacao: documento.querySelector('#ano-fabricacao'),
        observacoes: documento.querySelector('#observacoes'),
    };

    aplicarMascaraPlaca(campos.placa);

    function aoNovo(acao) {
        botaoNovo.addEventListener('click', acao);
    }

    function aoSalvar(acao) {
        formulario.addEventListener('submit', acao);
    }

    function aoAlterarTipo(acao) {
        campos.tipo_veiculo.addEventListener('change', acao);
    }

    function aoAlterarMarca(acao) {
        campos.marca.addEventListener('change', acao);
    }

    function aoFechar(acao) {
        elementoModal.addEventListener('hidden.bs.modal', acao);
    }

    function limparMensagens() {
        ocultarAlerta(mensagem);
        ocultarAlerta(mensagemModal);
    }

    function abrirCadastro() {
        formulario.reset();
        limparMensagens();
        titulo.textContent = 'Novo veículo';
        modal.show();
    }

    function abrirEdicao(veiculo) {
        limparMensagens();
        campos.cliente_id.value = veiculo.cliente_id;
        campos.placa.value = formatarPlaca(veiculo.placa);
        campos.marca.value = veiculo.marca ?? '';
        campos.modelo.value = veiculo.modelo;
        campos.categoria.value = veiculo.categoria;
        campos.cor.value = veiculo.cor ?? '';
        campos.ano_fabricacao.value = veiculo.ano_fabricacao ?? '';
        campos.observacoes.value = veiculo.observacoes ?? '';
        campos.tipo_veiculo.value = 'carros';

        if (veiculo.categoria === 'moto') {
            campos.tipo_veiculo.value = 'motos';
        }

        titulo.textContent = 'Editar veículo';
        modal.show();
    }

    function lerDados() {
        return {
            cliente_id: campos.cliente_id.value,
            placa: campos.placa.value,
            marca: campos.marca.value,
            modelo: campos.modelo.value,
            categoria: campos.categoria.value,
            cor: campos.cor.value,
            ano_fabricacao: campos.ano_fabricacao.value,
            observacoes: campos.observacoes.value,
        };
    }

    function obterTipo() {
        return campos.tipo_veiculo.value;
    }

    function obterMarca() {
        return campos.marca.value;
    }

    function limparMarcaEModelo() {
        campos.marca.value = '';
        campos.modelo.value = '';
    }

    function criarOpcao(texto, valor) {
        const opcao = documento.createElement('option');
        opcao.textContent = texto;
        opcao.value = valor;
        return opcao;
    }

    function mostrarClientes(clientes) {
        const selecionado = campos.cliente_id.value;
        campos.cliente_id.replaceChildren(criarOpcao('Selecione um cliente', ''));

        for (const cliente of clientes) {
            campos.cliente_id.appendChild(criarOpcao(cliente.nome, cliente.id));
        }

        campos.cliente_id.value = selecionado;
    }

    function mostrarMarcas(marcas) {
        listaMarcas.replaceChildren();

        for (const marca of marcas) {
            listaMarcas.appendChild(criarOpcao(marca.nome, marca.nome));
        }
    }

    function mostrarModelos(modelos) {
        listaModelos.replaceChildren();

        for (const modelo of modelos) {
            listaModelos.appendChild(criarOpcao(modelo, modelo));
        }
    }

    function mostrarVeiculos(veiculos, clientes, aoEditar, aoExcluir) {
        lista.replaceChildren();

        if (veiculos.length === 0) {
            mostrarAvisoTabela(lista, 5, 'Nenhum veículo cadastrado.');
            return;
        }

        for (const veiculo of veiculos) {
            const linha = documento.createElement('tr');
            let nomeCliente = '-';

            for (const cliente of clientes) {
                if (cliente.id === veiculo.cliente_id) {
                    nomeCliente = cliente.nome;
                    break;
                }
            }

            let descricao = veiculo.modelo;

            if (veiculo.marca) {
                descricao = veiculo.marca + ' ' + veiculo.modelo;
            }

            linha.appendChild(criarCelula(documento, formatarPlaca(veiculo.placa)));
            linha.appendChild(criarCelula(documento, descricao));
            linha.appendChild(criarCelula(documento, capitalizar(veiculo.categoria)));
            linha.appendChild(criarCelula(documento, nomeCliente));
            linha.appendChild(criarAcoes(documento, veiculo, aoEditar, aoExcluir));
            lista.appendChild(linha);
        }
    }

    function mostrarErroCarregamento() {
        mostrarAvisoTabela(lista, 5, 'Não foi possível carregar os veículos.', true);
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
        aoNovo: aoNovo, aoSalvar: aoSalvar, aoAlterarTipo: aoAlterarTipo,
        aoAlterarMarca: aoAlterarMarca, aoFechar: aoFechar,
        abrirCadastro: abrirCadastro, abrirEdicao: abrirEdicao, lerDados: lerDados,
        obterTipo: obterTipo, obterMarca: obterMarca, limparMarcaEModelo: limparMarcaEModelo,
        mostrarClientes: mostrarClientes, mostrarMarcas: mostrarMarcas, mostrarModelos: mostrarModelos,
        mostrarVeiculos: mostrarVeiculos, mostrarErroCarregamento: mostrarErroCarregamento,
        mostrarMensagem: mostrarMensagem, mostrarErroFormulario: mostrarErroFormulario,
        alterarSalvamento: alterarSalvamento, fecharFormulario: fecharFormulario,
    };
}

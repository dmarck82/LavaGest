import { apenasNumeros } from '../compartilhado/formatadores.js';

// Liga eventos da tela às operações dos serviços. As dependências chegam prontas.
export async function iniciarClientes(clienteServico, cepServico, tela, confirmar) {
    let clienteEmEdicaoId = null;
    let clientesCarregados = [];
    let ultimoCepConsultado = null;
    let numeroConsultaCep = 0;
    let salvando = false;

    tela.aoNovo(abrirCadastro);
    tela.aoSalvar(salvarCliente);
    tela.aoBuscar(atualizarLista);
    tela.aoAlterarCep(consultarCep);
    tela.aoFechar(invalidarConsultaCep);

    function invalidarConsultaCep() {
        numeroConsultaCep++;
        ultimoCepConsultado = null;
    }

    function abrirCadastro() {
        if (salvando) {
            return;
        }

        clienteEmEdicaoId = null;
        invalidarConsultaCep();
        tela.abrirCadastro();
    }

    function editarCliente(cliente) {
        if (salvando) {
            return;
        }

        clienteEmEdicaoId = cliente.id;
        invalidarConsultaCep();
        ultimoCepConsultado = apenasNumeros(cliente.cep);
        tela.abrirEdicao(cliente);
    }

    async function salvarCliente(evento) {
        evento.preventDefault();

        if (salvando) {
            return;
        }

        salvando = true;
        tela.alterarSalvamento(true);

        try {
            const dados = tela.lerDados();
            let mensagem = 'Cliente criado com sucesso.';

            if (clienteEmEdicaoId === null) {
                await clienteServico.criar(dados);
            } else {
                await clienteServico.atualizar(clienteEmEdicaoId, dados);
                mensagem = 'Cliente atualizado com sucesso.';
            }

            tela.fecharFormulario();
            tela.mostrarMensagem(mensagem, 'success');
            await carregarClientes();
        } catch (erro) {
            tela.mostrarErroFormulario(erro.message);
        } finally {
            salvando = false;
            tela.alterarSalvamento(false);
        }
    }

    async function carregarClientes() {
        try {
            clientesCarregados = await clienteServico.listar();
            atualizarLista();
        } catch (erro) {
            tela.mostrarErroCarregamento();
            tela.mostrarMensagem(erro.message, 'danger');
        }
    }

    function atualizarLista() {
        const busca = tela.obterBusca();
        const clientes = clienteServico.filtrar(clientesCarregados, busca);
        const temBusca = busca.trim() !== '';

        tela.mostrarClientes(clientes, editarCliente, excluirCliente, temBusca);
    }

    async function excluirCliente(cliente) {
        const confirmou = confirmar('Deseja excluir o cliente "' + cliente.nome + '"?');

        if (!confirmou) {
            return;
        }

        try {
            await clienteServico.excluir(cliente.id);
            tela.mostrarMensagem('Cliente excluído com sucesso.', 'success');
            await carregarClientes();
        } catch (erro) {
            tela.mostrarMensagem(erro.message, 'danger');
        }
    }

    async function consultarCep() {
        const cep = apenasNumeros(tela.obterCep());

        if (cep.length !== 8) {
            invalidarConsultaCep();
            tela.mostrarStatusCep('');
            return;
        }

        if (cep === ultimoCepConsultado) {
            return;
        }

        ultimoCepConsultado = cep;
        numeroConsultaCep++;
        const consultaAtual = numeroConsultaCep;
        tela.mostrarStatusCep('Buscando endereço...');

        try {
            const endereco = await cepServico.consultar(cep);

            // Uma resposta antiga não deve preencher outro formulário ou outro CEP.
            if (consultaAtual !== numeroConsultaCep) {
                return;
            }

            tela.preencherEndereco(endereco);
            tela.mostrarStatusCep('Endereço preenchido.', 'text-success');
        } catch (erro) {
            if (consultaAtual !== numeroConsultaCep) {
                return;
            }

            ultimoCepConsultado = null;
            tela.mostrarStatusCep(erro.message + ' Preencha o endereço manualmente.', 'text-danger');
        }
    }

    await carregarClientes();
}

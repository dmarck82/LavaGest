export async function iniciarVeiculos(veiculoServico, clienteServico, fipeServico, tela, confirmar) {
    let veiculoEmEdicaoId = null;
    let marcasCarregadas = [];
    let numeroConsultaMarcas = 0;
    let numeroConsultaModelos = 0;
    let salvando = false;

    tela.aoNovo(abrirCadastro);
    tela.aoSalvar(salvarVeiculo);
    tela.aoAlterarTipo(alterarTipo);
    tela.aoAlterarMarca(carregarModelos);
    tela.aoFechar(invalidarConsultasFipe);

    function invalidarConsultasFipe() {
        numeroConsultaMarcas++;
        numeroConsultaModelos++;
    }

    async function abrirCadastro() {
        if (salvando) {
            return;
        }

        veiculoEmEdicaoId = null;
        tela.abrirCadastro();
        await carregarMarcas();
    }

    async function editarVeiculo(veiculo) {
        if (salvando) {
            return;
        }

        veiculoEmEdicaoId = veiculo.id;
        tela.abrirEdicao(veiculo);
        await carregarMarcas();
    }

    async function alterarTipo() {
        tela.limparMarcaEModelo();
        await carregarMarcas();
    }

    async function salvarVeiculo(evento) {
        evento.preventDefault();

        if (salvando) {
            return;
        }

        salvando = true;
        tela.alterarSalvamento(true);

        try {
            const dados = tela.lerDados();
            let mensagem = 'Veículo criado com sucesso.';

            if (veiculoEmEdicaoId === null) {
                await veiculoServico.criar(dados);
            } else {
                await veiculoServico.atualizar(veiculoEmEdicaoId, dados);
                mensagem = 'Veículo atualizado com sucesso.';
            }

            tela.fecharFormulario();
            tela.mostrarMensagem(mensagem, 'success');
            await carregarDados();
        } catch (erro) {
            tela.mostrarErroFormulario(erro.message);
        } finally {
            salvando = false;
            tela.alterarSalvamento(false);
        }
    }

    async function carregarDados() {
        try {
            const clientes = await clienteServico.listar();
            const veiculos = await veiculoServico.listar();

            tela.mostrarClientes(clientes);
            tela.mostrarVeiculos(veiculos, clientes, editarVeiculo, excluirVeiculo);
        } catch (erro) {
            tela.mostrarErroCarregamento();
            tela.mostrarMensagem(erro.message, 'danger');
        }
    }

    async function excluirVeiculo(veiculo) {
        const confirmou = confirmar('Deseja excluir o veículo de placa ' + veiculo.placa + '?');

        if (!confirmou) {
            return;
        }

        try {
            await veiculoServico.excluir(veiculo.id);
            tela.mostrarMensagem('Veículo excluído com sucesso.', 'success');
            await carregarDados();
        } catch (erro) {
            tela.mostrarMensagem(erro.message, 'danger');
        }
    }

    async function carregarMarcas() {
        invalidarConsultasFipe();
        const consultaAtual = numeroConsultaMarcas;
        const tipo = tela.obterTipo();
        marcasCarregadas = [];
        tela.mostrarMarcas([]);
        tela.mostrarModelos([]);

        try {
            const marcas = await fipeServico.listarMarcas(tipo);

            if (consultaAtual !== numeroConsultaMarcas) {
                return;
            }

            marcasCarregadas = marcas;
            tela.mostrarMarcas(marcas);
            await carregarModelos();
        } catch (erro) {
            if (consultaAtual === numeroConsultaMarcas) {
                tela.mostrarErroFormulario('Não foi possível consultar a FIPE. Você pode preencher a marca e o modelo manualmente.');
            }
        }
    }

    async function carregarModelos() {
        numeroConsultaModelos++;
        const consultaAtual = numeroConsultaModelos;
        const nomeMarca = tela.obterMarca();
        const tipo = tela.obterTipo();
        let marcaSelecionada = null;
        tela.mostrarModelos([]);

        for (const marca of marcasCarregadas) {
            if (marca.nome === nomeMarca) {
                marcaSelecionada = marca;
                break;
            }
        }

        if (marcaSelecionada === null) {
            return;
        }

        try {
            const modelos = await fipeServico.listarModelos(tipo, marcaSelecionada.codigo);

            if (consultaAtual !== numeroConsultaModelos) {
                return;
            }

            tela.mostrarModelos(modelos);
        } catch (erro) {
            if (consultaAtual === numeroConsultaModelos) {
                tela.mostrarErroFormulario('Não foi possível consultar a FIPE. Você pode informar o modelo manualmente.');
            }
        }
    }

    await carregarDados();
    await carregarMarcas();
}

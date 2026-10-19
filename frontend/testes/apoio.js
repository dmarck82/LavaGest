// Dublês pequenos para testar a coordenação sem rede, navegador ou Bootstrap.
export function promessaControlada() {
    let resolver;
    let rejeitar;
    const promessa = new Promise(function (resolve, reject) {
        resolver = resolve;
        rejeitar = reject;
    });

    return { promessa: promessa, resolver: resolver, rejeitar: rejeitar };
}

export function criarTelaTeste() {
    const eventos = {};
    const estado = {
        dados: {}, busca: '', cep: '', tipo: 'carros', marca: '',
        mensagens: [], erros: [], statusCep: '', endereco: null,
        salvando: false, fechou: false, marcas: [], modelos: [],
    };
    const tela = {
        eventos: eventos,
        estado: estado,
        aoNovo: function (acao) { eventos.novo = acao; },
        aoSalvar: function (acao) { eventos.salvar = acao; },
        aoBuscar: function (acao) { eventos.buscar = acao; },
        aoAlterarCep: function (acao) { eventos.cep = acao; },
        aoAlterarTipo: function (acao) { eventos.tipo = acao; },
        aoAlterarMarca: function (acao) { eventos.marca = acao; },
        aoFechar: function (acao) { eventos.fechar = acao; },
        aoEntrar: function (acao) { eventos.entrar = acao; },
        lerDados: function () { return estado.dados; },
        obterBusca: function () { return estado.busca; },
        obterCep: function () { return estado.cep; },
        obterTipo: function () { return estado.tipo; },
        obterMarca: function () { return estado.marca; },
        abrirCadastro: function () { estado.abriu = 'cadastro'; },
        abrirEdicao: function (registro) {
            estado.abriu = 'edicao';
            estado.registro = registro;
            estado.marca = registro.marca ?? '';
        },
        limparMarcaEModelo: function () { estado.marca = ''; },
        fecharFormulario: function () { estado.fechou = true; },
        alterarSalvamento: function (valor) { estado.salvando = valor; },
        alterarCarregamento: function (valor) { estado.salvando = valor; },
        mostrarMensagem: function (texto) { estado.mensagens.push(texto); },
        mostrarErroFormulario: function (texto) { estado.erros.push(texto); },
        mostrarErro: function (texto) { estado.erros.push(texto); },
        mostrarErroCarregamento: function () { estado.falhaCarregamento = true; },
        mostrarStatusCep: function (texto) { estado.statusCep = texto; },
        preencherEndereco: function (endereco) { estado.endereco = endereco; },
        mostrarClientes: function (clientes, editar, excluir) {
            estado.clientes = clientes;
            if (editar) {
                eventos.editar = editar;
                eventos.excluir = excluir;
            }
        },
        mostrarUsuarios: function (usuarios, editar, excluir) {
            estado.usuarios = usuarios;
            eventos.editar = editar;
            eventos.excluir = excluir;
        },
        mostrarVeiculos: function (veiculos, clientes, editar, excluir) {
            estado.veiculos = veiculos;
            eventos.editar = editar;
            eventos.excluir = excluir;
        },
        mostrarMarcas: function (marcas) { estado.marcas = marcas; },
        mostrarModelos: function (modelos) { estado.modelos = modelos; },
    };

    return tela;
}

export function eventoFormulario() {
    return { preventDefault: function () {} };
}

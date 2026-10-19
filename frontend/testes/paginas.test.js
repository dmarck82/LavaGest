import test from 'node:test';
import assert from 'node:assert/strict';
import { iniciarClientes } from '../js/paginas/clientesPagina.js';
import { iniciarUsuarios } from '../js/paginas/usuariosPagina.js';
import { iniciarVeiculos } from '../js/paginas/veiculosPagina.js';
import { iniciarAutenticacao } from '../js/paginas/autenticacaoPagina.js';
import { criarTelaTeste, promessaControlada, eventoFormulario } from './apoio.js';

function clienteServicoTeste() {
    const chamadas = [];
    const servico = {
        chamadas: chamadas,
        listar: async function () { return [{ id: 1, nome: 'Ana' }]; },
        filtrar: function (clientes, busca) {
            return clientes.filter(function (cliente) {
                return cliente.nome.includes(busca);
            });
        },
        criar: async function (dados) { chamadas.push(['criar', dados]); },
        atualizar: async function (id, dados) { chamadas.push(['atualizar', id, dados]); },
        excluir: async function (id) { chamadas.push(['excluir', id]); },
    };

    return servico;
}

test('página de clientes coordena filtro, cadastro, edição e exclusão', async function () {
    const tela = criarTelaTeste();
    const servico = clienteServicoTeste();
    const cep = { consultar: async function () {} };
    await iniciarClientes(servico, cep, tela, function () { return true; });
    assert.equal(tela.estado.clientes.length, 1);

    tela.estado.busca = 'Não existe';
    tela.eventos.buscar();
    assert.equal(tela.estado.clientes.length, 0);
    tela.estado.busca = '';
    tela.eventos.buscar();

    tela.estado.dados = { nome: 'Novo' };
    tela.eventos.novo();
    await tela.eventos.salvar(eventoFormulario());
    assert.equal(servico.chamadas[0][0], 'criar');
    assert.equal(tela.estado.fechou, true);
    assert.equal(tela.estado.salvando, false);

    tela.eventos.editar({ id: 1, nome: 'Ana' });
    await tela.eventos.salvar(eventoFormulario());
    assert.equal(servico.chamadas[1][0], 'atualizar');
    assert.equal(servico.chamadas[1][1], 1);

    await tela.eventos.excluir({ id: 1, nome: 'Ana' });
    assert.deepEqual(servico.chamadas[2], ['excluir', 1]);
});

test('cancelar exclusão não chama a API', async function () {
    const tela = criarTelaTeste();
    const servico = clienteServicoTeste();
    await iniciarClientes(servico, {}, tela, function () { return false; });
    await tela.eventos.excluir({ id: 1, nome: 'Ana' });
    assert.equal(servico.chamadas.length, 0);
});

test('envio duplicado é bloqueado e erro de cadastro mantém o formulário aberto', async function () {
    const tela = criarTelaTeste();
    const servico = clienteServicoTeste();
    const pendente = promessaControlada();
    let quantidade = 0;
    servico.criar = async function () {
        quantidade++;
        await pendente.promessa;
    };
    await iniciarClientes(servico, {}, tela, function () {});
    const primeiroEnvio = tela.eventos.salvar(eventoFormulario());
    await tela.eventos.salvar(eventoFormulario());
    assert.equal(quantidade, 1);
    assert.equal(tela.estado.salvando, true);

    pendente.rejeitar(new Error('WhatsApp já cadastrado.'));
    await primeiroEnvio;
    assert.equal(tela.estado.fechou, false);
    assert.equal(tela.estado.salvando, false);
    assert.deepEqual(tela.estado.erros, ['WhatsApp já cadastrado.']);
});

test('CEP antigo não sobrescreve o novo endereço nem outro modal', async function () {
    const tela = criarTelaTeste();
    const primeira = promessaControlada();
    const segunda = promessaControlada();
    const terceira = promessaControlada();
    const consultas = [primeira, segunda, terceira];
    let indice = 0;
    const cep = {
        consultar: function () { return consultas[indice++].promessa; },
    };
    await iniciarClientes(clienteServicoTeste(), cep, tela, function () {});

    tela.estado.cep = '01001000';
    const buscaAntiga = tela.eventos.cep();
    tela.estado.cep = '20040002';
    const buscaNova = tela.eventos.cep();
    segunda.resolver({ cidade: 'Rio de Janeiro' });
    await buscaNova;
    primeira.resolver({ cidade: 'São Paulo' });
    await buscaAntiga;
    assert.equal(tela.estado.endereco.cidade, 'Rio de Janeiro');

    tela.estado.cep = '30000000';
    const buscaModal = tela.eventos.cep();
    tela.eventos.fechar();
    terceira.resolver({ cidade: 'Outro endereço' });
    await buscaModal;
    assert.equal(tela.estado.endereco.cidade, 'Rio de Janeiro');
});

test('falha de CEP permite preenchimento manual e nova tentativa', async function () {
    const tela = criarTelaTeste();
    let tentativas = 0;
    const cep = { consultar: async function () {
        tentativas++;
        throw new Error('CEP não encontrado.');
    } };
    await iniciarClientes(clienteServicoTeste(), cep, tela, function () {});
    tela.estado.cep = '01001000';
    await tela.eventos.cep();
    await tela.eventos.cep();
    assert.equal(tentativas, 2);
    assert.match(tela.estado.statusCep, /manualmente/);
    assert.equal(tela.estado.endereco, null);
});

test('página de usuários separa criação, atualização e exclusão', async function () {
    const tela = criarTelaTeste();
    const servico = clienteServicoTeste();
    await iniciarUsuarios(servico, tela, function () { return true; });
    assert.equal(tela.estado.usuarios[0].nome, 'Ana');
    tela.eventos.novo();
    await tela.eventos.salvar(eventoFormulario());
    tela.eventos.editar({ id: 2 });
    await tela.eventos.salvar(eventoFormulario());
    await tela.eventos.excluir({ id: 2, nome: 'Ana' });
    assert.deepEqual(servico.chamadas.map(function (item) { return item[0]; }), ['criar', 'atualizar', 'excluir']);
});

test('erro ao carregar uma lista aparece na tabela e na mensagem', async function () {
    const tela = criarTelaTeste();
    const servico = { listar: async function () { throw new Error('Servidor indisponível.'); } };
    await iniciarUsuarios(servico, tela, function () {});
    assert.equal(tela.estado.falhaCarregamento, true);
    assert.deepEqual(tela.estado.mensagens, ['Servidor indisponível.']);
});

test('FIPE indisponível preserva a edição manual e não impede salvar veículo', async function () {
    const tela = criarTelaTeste();
    const veiculos = clienteServicoTeste();
    const clientes = clienteServicoTeste();
    const fipe = { listarMarcas: async function () { throw new Error('Sem rede'); } };
    await iniciarVeiculos(veiculos, clientes, fipe, tela, function () { return true; });
    await tela.eventos.editar({ id: 8, placa: 'ABC1234', marca: 'Fiat', modelo: 'Uno' });
    assert.equal(tela.estado.registro.modelo, 'Uno');
    assert.match(tela.estado.erros[0], /manualmente/);

    tela.estado.dados = { modelo: 'Uno' };
    await tela.eventos.salvar(eventoFormulario());
    assert.equal(veiculos.chamadas[0][0], 'atualizar');
    assert.equal(veiculos.chamadas[0][1], 8);
    await tela.eventos.excluir({ id: 8, placa: 'ABC1234' });
    assert.deepEqual(veiculos.chamadas[1], ['excluir', 8]);
});

test('resposta de marcas antiga é ignorada depois de mudar o tipo', async function () {
    const tela = criarTelaTeste();
    const antiga = promessaControlada();
    const atual = promessaControlada();
    let quantidade = 0;
    const fipe = {
        listarMarcas: function () {
            quantidade++;
            if (quantidade === 1) {
                return antiga.promessa;
            }
            return atual.promessa;
        },
    };
    const inicio = iniciarVeiculos(clienteServicoTeste(), clienteServicoTeste(), fipe, tela, function () {});
    // A página primeiro carrega clientes e veículos e depois consulta a FIPE.
    await new Promise(function (resolve) { setImmediate(resolve); });
    tela.estado.tipo = 'motos';
    const mudanca = tela.eventos.tipo();
    atual.resolver([{ codigo: '2', nome: 'Honda' }]);
    await mudanca;
    antiga.resolver([{ codigo: '1', nome: 'Fiat' }]);
    await inicio;
    assert.equal(tela.estado.marcas[0].nome, 'Honda');
});

test('sugestão de modelo antiga é ignorada quando a marca muda', async function () {
    const tela = criarTelaTeste();
    const pendente = promessaControlada();
    const fipe = {
        listarMarcas: async function () { return [{ codigo: '1', nome: 'Fiat' }]; },
        listarModelos: function () { return pendente.promessa; },
    };
    await iniciarVeiculos(clienteServicoTeste(), clienteServicoTeste(), fipe, tela, function () {});
    tela.estado.marca = 'Fiat';
    const consulta = tela.eventos.marca();
    tela.estado.marca = 'Marca manual';
    await tela.eventos.marca();
    pendente.resolver(['Uno']);
    await consulta;
    assert.deepEqual(tela.estado.modelos, []);
});

test('login salva a sessão e encaminha para a URL sem extensão', async function () {
    const tela = criarTelaTeste();
    tela.estado.dados = { email: 'ana@example.com', senha: 'senha1234' };
    const autenticacao = { token: 'abc', expira_em: '2030-01-01' };
    let sessaoSalva;
    const sessao = { salvar: function (dados) { sessaoSalva = dados; } };
    const localizacao = { href: '/' };
    iniciarAutenticacao({ autenticar: async function () { return autenticacao; } }, sessao, tela, localizacao);
    await tela.eventos.entrar(eventoFormulario());
    assert.equal(sessaoSalva, autenticacao);
    assert.equal(localizacao.href, '/usuarios');
    assert.equal(tela.estado.salvando, false);
});

test('erro de login não salva sessão nem redireciona', async function () {
    const tela = criarTelaTeste();
    tela.estado.dados = { email: 'ana@example.com', senha: 'incorreta' };
    let salvou = false;
    const sessao = { salvar: function () { salvou = true; } };
    const localizacao = { href: '/' };
    iniciarAutenticacao({ autenticar: async function () { throw new Error('Credenciais inválidas.'); } }, sessao, tela, localizacao);
    await tela.eventos.entrar(eventoFormulario());
    assert.equal(salvou, false);
    assert.equal(localizacao.href, '/');
    assert.equal(tela.estado.salvando, false);
    assert.deepEqual(tela.estado.erros, ['Credenciais inválidas.']);
});

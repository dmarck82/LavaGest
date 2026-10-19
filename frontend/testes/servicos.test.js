import test from 'node:test';
import assert from 'node:assert/strict';
import { criarClienteHttp } from '../js/infraestrutura/clienteHttp.js';
import { criarSessao } from '../js/infraestrutura/sessao.js';
import { criarClienteServico } from '../js/servicos/clienteServico.js';
import { criarUsuarioServico } from '../js/servicos/usuarioServico.js';
import { criarVeiculoServico } from '../js/servicos/veiculoServico.js';
import { criarAutenticacaoServico } from '../js/servicos/autenticacaoServico.js';
import { criarCepServico } from '../js/servicos/cepServico.js';
import { criarFipeServico } from '../js/servicos/fipeServico.js';
import { apenasNumeros, formatarTelefone, formatarCep, formatarPlaca, formatarData } from '../js/compartilhado/formatadores.js';

function criarRequisicaoTeste(resposta = []) {
    const chamadas = [];

    async function requisicao(caminho, opcoes = {}) {
        chamadas.push({ caminho: caminho, opcoes: opcoes });
        return resposta;
    }

    return { requisicao: requisicao, chamadas: chamadas };
}

function dadosCliente() {
    return {
        nome: ' Ana ', whatsapp: '(11) 99999-8888', telefone: '',
        email: ' ana@example.com ', uf: ' sp ', cidade: ' São Paulo ',
        endereco: '', bairro: '', cep: '01001-000',
    };
}

function dadosVeiculo() {
    return {
        cliente_id: '12', placa: 'ABC-1234', marca: 'Fiat', modelo: 'Uno',
        categoria: 'hatch', cor: '', ano_fabricacao: '', observacoes: '',
    };
}

test('formatadores lidam com máscaras, ausência de valor e datas inválidas', function () {
    assert.equal(apenasNumeros(null), '');
    assert.equal(apenasNumeros('(11) 99999-8888'), '11999998888');
    assert.equal(formatarTelefone('11999998888'), '(11) 99999-8888');
    assert.equal(formatarTelefone('1133334444'), '(11) 3333-4444');
    assert.equal(formatarTelefone(''), '');
    assert.equal(formatarCep('01001000'), '01001-000');
    assert.equal(formatarPlaca('abc1d23'), 'ABC-1D23');
    assert.equal(formatarData(null, 'Nunca'), 'Nunca');
    assert.equal(formatarData('não é data'), '-');
});

test('HTTP envia autenticação e JSON sem alterar as opções recebidas', async function () {
    let tokenAtual = 'primeiro';
    const chamadas = [];

    async function fetchTeste(url, opcoes) {
        chamadas.push({ url: url, opcoes: opcoes });
        return new Response('{"id": 1}', { status: 201 });
    }

    const requisicao = criarClienteHttp('http://api.teste', function () {
        return tokenAtual;
    }, fetchTeste);
    const opcoes = { method: 'POST', body: '{"nome":"Ana"}' };
    const dados = await requisicao('/clientes', opcoes);
    assert.equal(dados.id, 1);
    assert.equal(chamadas[0].url, 'http://api.teste/clientes');
    assert.equal(chamadas[0].opcoes.headers.get('Authorization'), 'Bearer primeiro');
    assert.equal(chamadas[0].opcoes.headers.get('Content-Type'), 'application/json');
    assert.equal(opcoes.headers, undefined);

    tokenAtual = 'segundo';
    await requisicao('/clientes');
    assert.equal(chamadas[1].opcoes.headers.get('Authorization'), 'Bearer segundo');
});

test('HTTP aceita DELETE 204, ausência de token e propaga erros de negócio', async function () {
    const requisicao = criarClienteHttp('', function () { return null; }, async function (url, opcoes) {
        assert.equal(opcoes.headers.has('Authorization'), false);

        if (opcoes.method === 'DELETE') {
            return new Response(null, { status: 204 });
        }

        return new Response('{"erro":"Cliente possui veículos cadastrados."}', { status: 409 });
    });

    assert.equal(await requisicao('/clientes/1', { method: 'DELETE' }), null);
    await assert.rejects(requisicao('/clientes'), /Cliente possui veículos/);
});

test('HTTP informa falha de conexão e resposta que não é JSON', async function () {
    const semRede = criarClienteHttp('', function () { return null; }, async function () {
        throw new TypeError('falha de rede');
    });
    const semJson = criarClienteHttp('', function () { return null; }, async function () {
        return new Response('<html>erro</html>', { status: 500 });
    });

    await assert.rejects(semRede('/clientes'), /conectar ao servidor/);
    await assert.rejects(semJson('/clientes'), /resposta inválida/);
});

test('sessão guarda e remove os dados; exige login sem continuar a página', function () {
    const valores = new Map();
    const destinos = [];
    const armazenamento = {
        getItem: function (chave) { return valores.get(chave) ?? null; },
        setItem: function (chave, valor) { valores.set(chave, valor); },
        removeItem: function (chave) { valores.delete(chave); },
    };
    const localizacao = { replace: function (destino) { destinos.push(destino); } };
    const sessao = criarSessao(armazenamento, localizacao);

    assert.equal(sessao.exigirAutenticacao(), false);
    sessao.salvar({ token: 'abc', expira_em: '2030-01-01' });
    assert.equal(sessao.obterToken(), 'abc');
    assert.equal(sessao.exigirAutenticacao(), true);
    sessao.encerrar();
    assert.equal(valores.size, 0);
    assert.deepEqual(destinos, ['/', '/']);
});

test('cliente é normalizado antes do POST e campos vazios viram null', async function () {
    const transporte = criarRequisicaoTeste({ id: 1 });
    const servico = criarClienteServico(transporte.requisicao);
    await servico.criar(dadosCliente());

    const chamada = transporte.chamadas[0];
    const dados = JSON.parse(chamada.opcoes.body);
    assert.equal(chamada.caminho, '/clientes');
    assert.equal(chamada.opcoes.method, 'POST');
    assert.equal(dados.nome, 'Ana');
    assert.equal(dados.whatsapp, '11999998888');
    assert.equal(dados.cep, '01001000');
    assert.equal(dados.uf, 'SP');
    assert.equal(dados.email, 'ana@example.com');
    assert.equal(dados.telefone, null);
    assert.equal(dados.endereco, null);
});

test('cliente lista, atualiza e exclui pelos endpoints existentes', async function () {
    const transporte = criarRequisicaoTeste([]);
    const servico = criarClienteServico(transporte.requisicao);
    await servico.listar();
    await servico.atualizar(2, dadosCliente());
    await servico.excluir(2);
    assert.deepEqual(transporte.chamadas.map(function (item) { return item.caminho; }), ['/clientes', '/clientes/2', '/clientes/2']);
    assert.equal(transporte.chamadas[1].opcoes.method, 'PUT');
    assert.equal(transporte.chamadas[2].opcoes.method, 'DELETE');
});

test('cliente inválido não dispara requisição', async function () {
    const transporte = criarRequisicaoTeste();
    const servico = criarClienteServico(transporte.requisicao);
    const casos = [
        { nome: ' ' }, { whatsapp: '123' }, { telefone: '123' }, { cep: '123' },
    ];

    for (const alteracao of casos) {
        const dados = Object.assign(dadosCliente(), alteracao);
        await assert.rejects(servico.criar(dados));
    }

    assert.equal(transporte.chamadas.length, 0);
});

test('busca de clientes ignora caixa e considera nome, WhatsApp e cidade', function () {
    const servico = criarClienteServico(async function () {});
    const clientes = [
        { nome: 'Ana', whatsapp: '11999998888', cidade: 'São Paulo' },
        { nome: 'Bruno', whatsapp: '21999998888', cidade: null },
    ];
    assert.equal(servico.filtrar(clientes, ' ANA ')[0].nome, 'Ana');
    assert.equal(servico.filtrar(clientes, 'paulo')[0].nome, 'Ana');
    assert.equal(servico.filtrar(clientes, '219')[0].nome, 'Bruno');
    assert.equal(servico.filtrar(clientes, '').length, 2);
    assert.equal(servico.filtrar(clientes, 'inexistente').length, 0);
});

test('usuário exige senha no cadastro e não envia senha na edição', async function () {
    const transporte = criarRequisicaoTeste();
    const servico = criarUsuarioServico(transporte.requisicao);
    const dados = { nome: ' Ana ', email: ' ana@example.com ', telefone: '(11) 99999-8888', senha: 'senha1234' };

    await servico.criar(dados);
    await servico.atualizar(3, dados);
    await servico.excluir(3);
    const criacao = JSON.parse(transporte.chamadas[0].opcoes.body);
    const edicao = JSON.parse(transporte.chamadas[1].opcoes.body);
    assert.equal(criacao.senha, 'senha1234');
    assert.equal(criacao.telefone, '11999998888');
    assert.equal('senha' in edicao, false);
    assert.equal(transporte.chamadas[2].opcoes.method, 'DELETE');

    await assert.rejects(servico.criar(Object.assign({}, dados, { senha: 'curta' })), /8 caracteres/);
});

test('veículo converte IDs e anos e permite limpar campos opcionais', async function () {
    const transporte = criarRequisicaoTeste();
    const servico = criarVeiculoServico(transporte.requisicao);
    await servico.criar(dadosVeiculo());
    await servico.atualizar(4, Object.assign(dadosVeiculo(), { ano_fabricacao: '2020' }));
    await servico.excluir(4);

    const criacao = JSON.parse(transporte.chamadas[0].opcoes.body);
    const edicao = JSON.parse(transporte.chamadas[1].opcoes.body);
    assert.equal(criacao.cliente_id, 12);
    assert.equal(criacao.ano_fabricacao, null);
    assert.equal(criacao.cor, null);
    assert.equal(edicao.ano_fabricacao, 2020);
    assert.equal(transporte.chamadas[2].caminho, '/veiculos/4');

    await assert.rejects(servico.criar(Object.assign(dadosVeiculo(), { cliente_id: '' })), /Selecione/);
    await assert.rejects(servico.criar(Object.assign(dadosVeiculo(), { ano_fabricacao: '2020.5' })), /inteiro/);
});

test('autenticação preserva a senha e normaliza somente o e-mail', async function () {
    const transporte = criarRequisicaoTeste({ token: 'abc' });
    const servico = criarAutenticacaoServico(transporte.requisicao);
    assert.equal((await servico.autenticar(' ana@example.com ', ' senha ')).token, 'abc');
    assert.deepEqual(JSON.parse(transporte.chamadas[0].opcoes.body), { email: 'ana@example.com', senha: ' senha ' });
    await assert.rejects(servico.autenticar('', 'senha'), /Informe/);
});

test('CEP normaliza a resposta externa sem ler campos do HTML', async function () {
    let urlRecebida;
    const servico = criarCepServico('https://cep.teste', async function (url) {
        urlRecebida = url;
        return new Response(JSON.stringify({ uf: 'SP', localidade: 'São Paulo', logradouro: 'Praça da Sé', bairro: 'Sé' }));
    });

    assert.deepEqual(await servico.consultar('01001-000'), {
        uf: 'SP', cidade: 'São Paulo', endereco: 'Praça da Sé', bairro: 'Sé',
    });
    assert.equal(urlRecebida, 'https://cep.teste/01001000/json/');
    await assert.rejects(servico.consultar('123'), /CEP válido/);

    const naoEncontrado = criarCepServico('', async function () { return new Response('{"erro": true}'); });
    await assert.rejects(naoEncontrado.consultar('01001000'), /não encontrado/);
});

test('falha de rede ou JSON inválido no CEP vira mensagem compreensível', async function () {
    const semRede = criarCepServico('', async function () {
        throw new TypeError('Failed to fetch');
    });
    const semJson = criarCepServico('', async function () {
        return new Response('<html>indisponível</html>');
    });

    await assert.rejects(semRede.consultar('01001000'), /Não foi possível consultar o CEP/);
    await assert.rejects(semJson.consultar('01001000'), /Não foi possível consultar o CEP/);
});

test('FIPE lista marcas e mantém sugestões curtas sem nomes repetidos', async function () {
    const urls = [];
    const servico = criarFipeServico('https://fipe.teste', async function (url) {
        urls.push(url);

        if (url.endsWith('/marcas')) {
            return new Response('[{"codigo":"21","nome":"Fiat"}]');
        }

        return new Response('{"modelos":[{"nome":"Uno 1.0"},{"nome":"Uno 1.4"},{"nome":"Argo 1.0"}]}');
    });

    assert.equal((await servico.listarMarcas('carros'))[0].nome, 'Fiat');
    assert.deepEqual(await servico.listarModelos('carros', '21'), ['Argo', 'Uno']);
    assert.deepEqual(urls, ['https://fipe.teste/carros/marcas', 'https://fipe.teste/carros/marcas/21/modelos']);
});

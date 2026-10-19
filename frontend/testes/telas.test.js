import test from 'node:test';
import assert from 'node:assert/strict';
import { criarClienteTela } from '../js/telas/clienteTela.js';
import { criarUsuarioTela } from '../js/telas/usuarioTela.js';
import { criarVeiculoTela } from '../js/telas/veiculoTela.js';
import { criarAutenticacaoTela } from '../js/telas/autenticacaoTela.js';
import { criarDocumentoTeste, bootstrapTeste } from './documentoTeste.js';

test('tela de cliente usa IDs reais, máscaras, valores opcionais e endereço', async function () {
    const documento = criarDocumentoTeste('clientes');
    const tela = criarClienteTela(documento, bootstrapTeste);
    tela.abrirEdicao({ id: 1, nome: 'Ana', whatsapp: '11999998888', telefone: null, cep: '01001000' });
    assert.equal(documento.querySelector('#whatsapp').value, '(11) 99999-8888');
    assert.equal(documento.querySelector('#telefone').value, '');
    assert.equal(tela.obterCep(), '01001-000');
    documento.querySelector('#cep').value = '20040002';
    await documento.querySelector('#cep').emitir('input');
    assert.equal(tela.obterCep(), '20040-002');
    tela.preencherEndereco({ uf: 'RJ', cidade: 'Rio', endereco: 'Rua A', bairro: 'Centro' });
    assert.equal(tela.lerDados().cidade, 'Rio');
    tela.alterarSalvamento(true);
    assert.equal(documento.querySelector('#formulario-cliente').querySelector('[type="submit"]').disabled, true);
});

test('dados de clientes são texto e ações chamam os callbacks da página', async function () {
    const documento = criarDocumentoTeste('clientes');
    const tela = criarClienteTela(documento, bootstrapTeste);
    const cliente = { id: 1, nome: '<img src=x onerror=alert(1)>', whatsapp: '11999998888', telefone: null, cidade: null };
    let editado;
    let excluido;
    tela.mostrarClientes([cliente], function (registro) { editado = registro; }, function (registro) { excluido = registro; }, false);
    const linha = documento.querySelector('#lista-clientes').children[0];
    assert.equal(linha.children.length, 7);
    assert.equal(linha.children[1].textContent, cliente.nome);
    assert.equal(linha.children[1].innerHTML, '');
    await linha.children[6].children[0].emitir('click');
    await linha.children[6].children[1].emitir('click');
    assert.equal(editado, cliente);
    assert.equal(excluido, cliente);

    tela.mostrarClientes([], function () {}, function () {}, true);
    assert.equal(documento.querySelector('#lista-clientes').children[0].children[0].textContent, 'Nenhum cliente encontrado.');
});

test('tela de usuário alterna senha obrigatória e mostra erro dentro do modal', function () {
    const documento = criarDocumentoTeste('usuarios');
    const tela = criarUsuarioTela(documento, bootstrapTeste);
    tela.abrirCadastro();
    assert.equal(documento.querySelector('#senha').required, true);
    tela.abrirEdicao({ nome: 'Ana', email: 'ana@example.com', telefone: '11999998888' });
    assert.equal(documento.querySelector('#senha').required, false);
    assert.equal(documento.querySelector('#senha').value, '');
    tela.mostrarErroFormulario('E-mail já cadastrado.');
    assert.equal(documento.querySelector('#mensagem-modal').textContent, 'E-mail já cadastrado.');
    tela.mostrarUsuarios([{ id: 1, nome: 'Ana', email: 'ana@example.com', telefone: null, ultimo_login: null }], function () {}, function () {});
    assert.equal(documento.querySelector('#lista-usuarios').children[0].children[4].textContent, 'Nunca');
});

test('tela de veículo mantém dados salvos e muda apenas as sugestões da FIPE', function () {
    const documento = criarDocumentoTeste('veiculos');
    const tela = criarVeiculoTela(documento, bootstrapTeste);
    tela.abrirEdicao({ id: 3, cliente_id: 1, placa: 'ABC1D23', marca: 'Honda', modelo: 'CG', categoria: 'moto' });
    assert.equal(tela.obterTipo(), 'motos');
    tela.mostrarMarcas([]);
    tela.mostrarModelos([]);
    assert.equal(tela.lerDados().marca, 'Honda');
    assert.equal(tela.lerDados().modelo, 'CG');
    assert.equal(tela.lerDados().ano_fabricacao, '');
    tela.mostrarMarcas([{ nome: 'Honda', codigo: '1' }]);
    tela.mostrarModelos(['CG']);
    assert.equal(documento.querySelector('#lista-marcas').children[0].value, 'Honda');
    tela.mostrarVeiculos([{ cliente_id: 1, placa: 'ABC1234', marca: 'Fiat', modelo: 'Uno', categoria: 'hatch' }], [{ id: 1, nome: 'Ana' }], function () {}, function () {});
    assert.equal(documento.querySelector('#lista-veiculos').children[0].children[3].textContent, 'Ana');
});

test('tela de login lê credenciais e restaura o botão depois do carregamento', function () {
    const documento = criarDocumentoTeste('index');
    const tela = criarAutenticacaoTela(documento);
    documento.querySelector('#email').value = 'ana@example.com';
    documento.querySelector('#senha').value = ' senha ';
    assert.deepEqual(tela.lerDados(), { email: 'ana@example.com', senha: ' senha ' });
    tela.alterarCarregamento(true);
    assert.equal(documento.querySelector('#botao-entrar').disabled, true);
    tela.mostrarErro('Senha inválida.');
    tela.alterarCarregamento(false);
    assert.equal(documento.querySelector('#botao-entrar').disabled, false);
    assert.equal(documento.querySelector('#mensagem').textContent, 'Senha inválida.');
});

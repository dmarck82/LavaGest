import { valorOpcional } from '../compartilhado/formatadores.js';

export function criarVeiculoServico(requisicao) {
    function prepararDados(formulario) {
        let anoFabricacao = null;

        if (formulario.ano_fabricacao !== '') {
            anoFabricacao = Number(formulario.ano_fabricacao);

            if (!Number.isInteger(anoFabricacao)) {
                throw new Error('Informe um ano de fabricação inteiro.');
            }
        }

        const dados = {
            cliente_id: Number(formulario.cliente_id),
            placa: formulario.placa.trim(),
            marca: valorOpcional(formulario.marca),
            modelo: formulario.modelo.trim(),
            categoria: formulario.categoria,
            cor: valorOpcional(formulario.cor),
            ano_fabricacao: anoFabricacao,
            observacoes: valorOpcional(formulario.observacoes),
        };

        if (!Number.isInteger(dados.cliente_id) || dados.cliente_id < 1) {
            throw new Error('Selecione um cliente.');
        }

        if (dados.placa === '' || dados.modelo === '' || dados.categoria === '') {
            throw new Error('Informe a placa, o modelo e a categoria.');
        }

        return dados;
    }

    async function listar() {
        return await requisicao('/veiculos');
    }

    async function criar(formulario) {
        const dados = prepararDados(formulario);

        return await requisicao('/veiculos', {
            method: 'POST',
            body: JSON.stringify(dados),
        });
    }

    async function atualizar(id, formulario) {
        const dados = prepararDados(formulario);

        return await requisicao('/veiculos/' + id, {
            method: 'PUT',
            body: JSON.stringify(dados),
        });
    }

    async function excluir(id) {
        await requisicao('/veiculos/' + id, { method: 'DELETE' });
    }

    return { listar: listar, criar: criar, atualizar: atualizar, excluir: excluir };
}

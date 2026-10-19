import { apenasNumeros, telefoneValido, valorOpcional } from '../compartilhado/formatadores.js';

// Recebe uma função de requisição, em vez de chamar fetch diretamente.
export function criarClienteServico(requisicao) {
    function prepararDados(formulario) {
        const dados = {
            nome: formulario.nome.trim(),
            whatsapp: apenasNumeros(formulario.whatsapp),
            telefone: valorOpcional(apenasNumeros(formulario.telefone)),
            email: valorOpcional(formulario.email),
            uf: valorOpcional(formulario.uf),
            cidade: valorOpcional(formulario.cidade),
            endereco: valorOpcional(formulario.endereco),
            bairro: valorOpcional(formulario.bairro),
            cep: valorOpcional(apenasNumeros(formulario.cep)),
        };

        if (dados.uf !== null) {
            dados.uf = dados.uf.toUpperCase();
        }

        if (dados.nome === '') {
            throw new Error('Informe o nome do cliente.');
        }

        if (!telefoneValido(dados.whatsapp)) {
            throw new Error('Informe um WhatsApp fixo ou celular válido.');
        }

        if (dados.telefone !== null && !telefoneValido(dados.telefone)) {
            throw new Error('Informe um telefone fixo ou celular válido.');
        }

        if (dados.cep !== null && dados.cep.length !== 8) {
            throw new Error('Informe um CEP válido.');
        }

        return dados;
    }

    async function listar() {
        return await requisicao('/clientes');
    }

    async function criar(formulario) {
        const dados = prepararDados(formulario);

        return await requisicao('/clientes', {
            method: 'POST',
            body: JSON.stringify(dados),
        });
    }

    async function atualizar(id, formulario) {
        const dados = prepararDados(formulario);

        return await requisicao('/clientes/' + id, {
            method: 'PUT',
            body: JSON.stringify(dados),
        });
    }

    async function excluir(id) {
        await requisicao('/clientes/' + id, { method: 'DELETE' });
    }

    function filtrar(clientes, busca) {
        const termo = busca.trim().toLocaleLowerCase('pt-BR');
        const encontrados = [];

        for (const cliente of clientes) {
            const nome = cliente.nome ?? '';
            const whatsapp = cliente.whatsapp ?? '';
            const cidade = cliente.cidade ?? '';
            const texto = (nome + ' ' + whatsapp + ' ' + cidade).toLocaleLowerCase('pt-BR');

            if (texto.includes(termo)) {
                encontrados.push(cliente);
            }
        }

        return encontrados;
    }

    return {
        listar: listar,
        criar: criar,
        atualizar: atualizar,
        excluir: excluir,
        filtrar: filtrar,
    };
}

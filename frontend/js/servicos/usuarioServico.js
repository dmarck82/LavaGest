import { apenasNumeros, telefoneValido } from '../compartilhado/formatadores.js';

export function criarUsuarioServico(requisicao) {
    function prepararDados(formulario) {
        const dados = {
            nome: formulario.nome.trim(),
            email: formulario.email.trim(),
            telefone: apenasNumeros(formulario.telefone),
        };

        if (dados.nome === '' || dados.email === '') {
            throw new Error('Informe o nome e o e-mail.');
        }

        if (!telefoneValido(dados.telefone)) {
            throw new Error('Informe um telefone fixo ou celular válido.');
        }

        return dados;
    }

    async function listar() {
        return await requisicao('/usuarios');
    }

    async function criar(formulario) {
        const dados = prepararDados(formulario);

        if (formulario.senha.length < 8) {
            throw new Error('A senha deve ter pelo menos 8 caracteres.');
        }

        dados.senha = formulario.senha;

        return await requisicao('/usuarios', {
            method: 'POST',
            body: JSON.stringify(dados),
        });
    }

    async function atualizar(id, formulario) {
        const dados = prepararDados(formulario);

        // A edição do cadastro não altera a senha.
        return await requisicao('/usuarios/' + id, {
            method: 'PUT',
            body: JSON.stringify(dados),
        });
    }

    async function excluir(id) {
        await requisicao('/usuarios/' + id, { method: 'DELETE' });
    }

    return { listar: listar, criar: criar, atualizar: atualizar, excluir: excluir };
}

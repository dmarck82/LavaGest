import { apenasNumeros } from '../compartilhado/formatadores.js';

export function criarCepServico(urlCep, fazerFetch = fetch) {
    async function consultar(valor) {
        const cep = apenasNumeros(valor);

        if (cep.length !== 8) {
            throw new Error('Informe um CEP válido.');
        }

        let dados;

        try {
            const resposta = await fazerFetch(urlCep + '/' + cep + '/json/');

            if (!resposta.ok) {
                throw new Error('Falha na consulta.');
            }

            dados = await resposta.json();
        } catch (erro) {
            throw new Error('Não foi possível consultar o CEP.');
        }

        if (dados.erro) {
            throw new Error('CEP não encontrado.');
        }

        return {
            uf: dados.uf ?? '',
            cidade: dados.localidade ?? '',
            endereco: dados.logradouro ?? '',
            bairro: dados.bairro ?? '',
        };
    }

    return { consultar: consultar };
}

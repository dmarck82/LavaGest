export function criarFipeServico(urlFipe, fazerFetch = fetch) {
    async function listarMarcas(tipoVeiculo) {
        const resposta = await fazerFetch(urlFipe + '/' + tipoVeiculo + '/marcas');

        if (!resposta.ok) {
            throw new Error('Não foi possível carregar as marcas. Você pode informar a marca manualmente.');
        }

        return await resposta.json();
    }

    async function listarModelos(tipoVeiculo, codigoMarca) {
        const caminho = '/' + tipoVeiculo + '/marcas/' + codigoMarca + '/modelos';
        const resposta = await fazerFetch(urlFipe + caminho);

        if (!resposta.ok) {
            throw new Error('Não foi possível carregar os modelos. Você pode informar o modelo manualmente.');
        }

        const dados = await resposta.json();
        const nomes = [];

        // Mantém a sugestão curta já usada no projeto e remove nomes repetidos.
        for (const modelo of dados.modelos) {
            const nome = modelo.nome.trim().split(/\s+/)[0];

            if (!nomes.includes(nome)) {
                nomes.push(nome);
            }
        }

        nomes.sort();

        return nomes;
    }

    return { listarMarcas: listarMarcas, listarModelos: listarModelos };
}

// Cuida somente do transporte HTTP. Não conhece formulários ou tabelas.
export function criarClienteHttp(urlApi, obterToken, fazerFetch = fetch) {
    async function requisicao(caminho, opcoes = {}) {
        const cabecalhos = new Headers(opcoes.headers);
        const token = obterToken();

        cabecalhos.set('Accept', 'application/json');

        if (opcoes.body) {
            cabecalhos.set('Content-Type', 'application/json');
        }

        if (token) {
            cabecalhos.set('Authorization', 'Bearer ' + token);
        }

        const configuracao = Object.assign({}, opcoes);
        configuracao.headers = cabecalhos;

        let resposta;

        try {
            resposta = await fazerFetch(urlApi + caminho, configuracao);
        } catch (erro) {
            throw new Error('Não foi possível conectar ao servidor. Tente novamente.');
        }

        if (resposta.status === 204) {
            return null;
        }

        let dados;

        try {
            dados = await resposta.json();
        } catch (erro) {
            throw new Error('O servidor enviou uma resposta inválida.');
        }

        if (!resposta.ok) {
            let mensagem = 'Não foi possível concluir a requisição.';

            if (dados && typeof dados.erro === 'string') {
                mensagem = dados.erro;
            }

            throw new Error(mensagem);
        }

        return dados;
    }

    return requisicao;
}

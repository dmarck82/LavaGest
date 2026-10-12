const URL_API = 'http://localhost:8000';

export async function requisicao(caminho, opcoes = {}) {
    const token = sessionStorage.getItem('token');

    const cabecalhos = new Headers(opcoes.headers ?? {});

    cabecalhos.set('Accept', 'application/json');

    if (opcoes.body) {
        cabecalhos.set('Content-Type', 'application/json');
    }

    if (token) {
        cabecalhos.set(
            'Authorization',
            `Bearer ${token}`
        );
    }

    const resposta = await fetch(
        `${URL_API}${caminho}`,
        {
            ...opcoes,
            headers: cabecalhos,
        }
    );

    if (resposta.status === 204) {
        return null;
    }

    const dados = await resposta.json();

    if (!resposta.ok) {
        throw new Error(
            dados.erro ?? 'Não foi possível concluir a requisição.'
        );
    }

    return dados;
}
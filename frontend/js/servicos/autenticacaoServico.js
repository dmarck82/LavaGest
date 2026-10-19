export function criarAutenticacaoServico(requisicao) {
    async function autenticar(email, senha) {
        const dados = {
            email: email.trim(),
            senha: senha,
        };

        if (dados.email === '' || dados.senha === '') {
            throw new Error('Informe o e-mail e a senha.');
        }

        const autenticacao = await requisicao('/autenticacao', {
            method: 'POST',
            body: JSON.stringify(dados),
        });

        return autenticacao;
    }

    return { autenticar: autenticar };
}

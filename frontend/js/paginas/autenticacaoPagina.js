export function iniciarAutenticacao(autenticacaoServico, sessao, tela, localizacao) {
    let entrando = false;

    tela.aoEntrar(entrar);

    async function entrar(evento) {
        evento.preventDefault();

        if (entrando) {
            return;
        }

        entrando = true;
        tela.alterarCarregamento(true);

        try {
            const dados = tela.lerDados();
            const autenticacao = await autenticacaoServico.autenticar(dados.email, dados.senha);

            sessao.salvar(autenticacao);
            localizacao.href = '/usuarios';
        } catch (erro) {
            tela.mostrarErro(erro.message);
        } finally {
            entrando = false;
            tela.alterarCarregamento(false);
        }
    }
}

// Recebe o armazenamento e a localização para poder ser testada sem navegador.
export function criarSessao(armazenamento, localizacao) {
    function obterToken() {
        return armazenamento.getItem('token');
    }

    function salvar(autenticacao) {
        armazenamento.setItem('token', autenticacao.token);
        armazenamento.setItem('token_expira_em', autenticacao.expira_em);
    }

    function encerrar() {
        armazenamento.removeItem('token');
        armazenamento.removeItem('token_expira_em');
        localizacao.replace('/');
    }

    function exigirAutenticacao() {
        if (!obterToken()) {
            localizacao.replace('/');
            return false;
        }

        // A validade do token continua sendo conferida pelo backend.
        return true;
    }

    return {
        obterToken: obterToken,
        salvar: salvar,
        encerrar: encerrar,
        exigirAutenticacao: exigirAutenticacao,
    };
}

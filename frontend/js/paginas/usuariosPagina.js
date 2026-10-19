export async function iniciarUsuarios(usuarioServico, tela, confirmar) {
    let usuarioEmEdicaoId = null;
    let salvando = false;

    tela.aoNovo(abrirCadastro);
    tela.aoSalvar(salvarUsuario);

    function abrirCadastro() {
        if (salvando) {
            return;
        }

        usuarioEmEdicaoId = null;
        tela.abrirCadastro();
    }

    function editarUsuario(usuario) {
        if (salvando) {
            return;
        }

        usuarioEmEdicaoId = usuario.id;
        tela.abrirEdicao(usuario);
    }

    async function salvarUsuario(evento) {
        evento.preventDefault();

        if (salvando) {
            return;
        }

        salvando = true;
        tela.alterarSalvamento(true);

        try {
            const dados = tela.lerDados();
            let mensagem = 'Usuário criado com sucesso.';

            if (usuarioEmEdicaoId === null) {
                await usuarioServico.criar(dados);
            } else {
                await usuarioServico.atualizar(usuarioEmEdicaoId, dados);
                mensagem = 'Usuário atualizado com sucesso.';
            }

            tela.fecharFormulario();
            tela.mostrarMensagem(mensagem, 'success');
            await carregarUsuarios();
        } catch (erro) {
            tela.mostrarErroFormulario(erro.message);
        } finally {
            salvando = false;
            tela.alterarSalvamento(false);
        }
    }

    async function carregarUsuarios() {
        try {
            const usuarios = await usuarioServico.listar();
            tela.mostrarUsuarios(usuarios, editarUsuario, excluirUsuario);
        } catch (erro) {
            tela.mostrarErroCarregamento();
            tela.mostrarMensagem(erro.message, 'danger');
        }
    }

    async function excluirUsuario(usuario) {
        const confirmou = confirmar('Deseja excluir o usuário "' + usuario.nome + '"?');

        if (!confirmou) {
            return;
        }

        try {
            await usuarioServico.excluir(usuario.id);
            tela.mostrarMensagem('Usuário excluído com sucesso.', 'success');
            await carregarUsuarios();
        } catch (erro) {
            tela.mostrarMensagem(erro.message, 'danger');
        }
    }

    await carregarUsuarios();
}

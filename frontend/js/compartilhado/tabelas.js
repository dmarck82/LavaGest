export function criarCelula(documento, texto) {
    const celula = documento.createElement('td');
    celula.textContent = texto;
    return celula;
}

export function mostrarAvisoTabela(tabela, quantidadeColunas, texto, erro = false) {
    const documento = tabela.ownerDocument;
    const linha = documento.createElement('tr');
    const celula = criarCelula(documento, texto);

    celula.colSpan = quantidadeColunas;
    celula.className = 'text-center text-secondary py-4';

    if (erro) {
        celula.className = 'text-center text-danger py-4';
    }

    linha.appendChild(celula);
    tabela.replaceChildren(linha);
}

export function criarAcoes(documento, registro, aoEditar, aoExcluir) {
    const celula = documento.createElement('td');
    celula.className = 'text-end text-nowrap';

    const botaoEditar = documento.createElement('button');
    botaoEditar.type = 'button';
    botaoEditar.className = 'btn btn-outline-primary btn-sm me-1';
    botaoEditar.title = 'Editar';
    botaoEditar.setAttribute('aria-label', 'Editar');
    botaoEditar.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';
    botaoEditar.addEventListener('click', function () {
        aoEditar(registro);
    });

    const botaoExcluir = documento.createElement('button');
    botaoExcluir.type = 'button';
    botaoExcluir.className = 'btn btn-outline-danger btn-sm';
    botaoExcluir.title = 'Excluir';
    botaoExcluir.setAttribute('aria-label', 'Excluir');
    botaoExcluir.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
    botaoExcluir.addEventListener('click', function () {
        aoExcluir(registro);
    });

    // Os ícones acima são fixos. Dados recebidos da API usam textContent.
    celula.appendChild(botaoEditar);
    celula.appendChild(botaoExcluir);

    return celula;
}

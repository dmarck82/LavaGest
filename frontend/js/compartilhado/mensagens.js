export function mostrarMensagem(elemento, texto, tipo = 'danger') {
    elemento.textContent = texto;
    elemento.className = 'alert alert-' + tipo;
}

export function ocultarMensagem(elemento) {
    elemento.textContent = '';
    elemento.className = 'alert d-none';
}

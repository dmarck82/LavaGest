import { formatarTelefone, formatarCep, formatarPlaca } from './formatadores.js';

// As máscaras mudam o que aparece no campo; não validam o cadastro.
export function aplicarMascaraTelefone(campo) {
    campo.addEventListener('input', function () {
        campo.value = formatarTelefone(campo.value);
    });
}

export function aplicarMascaraCep(campo) {
    campo.addEventListener('input', function () {
        campo.value = formatarCep(campo.value);
    });
}

export function aplicarMascaraPlaca(campo) {
    campo.addEventListener('input', function () {
        campo.value = formatarPlaca(campo.value);
    });
}

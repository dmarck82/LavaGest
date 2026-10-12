export function apenasNumeros(valor) {
    return String(valor).replace(/\D/g, '');
}

export function formatarTelefone(valor) {
    const numeros = apenasNumeros(valor).slice(0, 11);

    if (numeros.length <= 2) {
        return numeros ? `(${numeros}` : '';
    }

    const ddd = numeros.slice(0, 2);
    const numero = numeros.slice(2);

    if (numeros.length <= 6) {
        return `(${ddd}) ${numero}`;
    }

    if (numeros.length <= 10) {
        return `
            (${ddd}) ${numero.slice(0, 4)}-${numero.slice(4, 8)}
        `.trim();
    }

    return `
        (${ddd}) ${numero.slice(0, 5)}-${numero.slice(5, 9)}
    `.trim();
}

export function telefoneValido(valor) {
    const quantidade = apenasNumeros(valor).length;

    return quantidade === 10 || quantidade === 11;
}

export function aplicarMascaraTelefone(campo) {
    campo.addEventListener('input', () => {
        campo.value = formatarTelefone(campo.value);
    });
}

export function formatarCep(valor) {
    const numeros = apenasNumeros(valor).slice(0, 8);

    if (numeros.length <= 5) {
        return numeros;
    }

    return numeros.slice(0, 5) + '-' + numeros.slice(5);
}

export function aplicarMascaraCep(campo) {
    campo.addEventListener('input', () => {
        campo.value = formatarCep(campo.value);
    });
}

export function formatarPlaca(valor) {
    const placa = String(valor)
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '')
        .slice(0, 7);

    if (placa.length <= 3) {
        return placa;
    }

    return placa.slice(0, 3) + '-' + placa.slice(3);
}

export function aplicarMascaraPlaca(campo) {
    campo.addEventListener('input', () => {
        campo.value = formatarPlaca(campo.value);
    });
}

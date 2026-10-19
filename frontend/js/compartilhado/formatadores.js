// Funções que transformam valores sem acessar o HTML ou a rede.
export function apenasNumeros(valor) {
    return String(valor ?? '').replace(/\D/g, '');
}

export function valorOpcional(valor) {
    const texto = String(valor ?? '').trim();

    if (texto === '') {
        return null;
    }

    return texto;
}

export function telefoneValido(valor) {
    const quantidade = apenasNumeros(valor).length;
    return quantidade === 10 || quantidade === 11;
}

export function formatarTelefone(valor) {
    const numeros = apenasNumeros(valor).slice(0, 11);

    if (numeros === '') {
        return '';
    }

    if (numeros.length <= 2) {
        return '(' + numeros;
    }

    const ddd = numeros.slice(0, 2);
    const numero = numeros.slice(2);

    if (numeros.length <= 6) {
        return '(' + ddd + ') ' + numero;
    }

    if (numeros.length <= 10) {
        return '(' + ddd + ') ' + numero.slice(0, 4) + '-' + numero.slice(4, 8);
    }

    return '(' + ddd + ') ' + numero.slice(0, 5) + '-' + numero.slice(5, 9);
}

export function formatarCep(valor) {
    const numeros = apenasNumeros(valor).slice(0, 8);

    if (numeros.length <= 5) {
        return numeros;
    }

    return numeros.slice(0, 5) + '-' + numeros.slice(5);
}

export function formatarPlaca(valor) {
    const texto = String(valor ?? '').toUpperCase();
    const placa = texto.replace(/[^A-Z0-9]/g, '').slice(0, 7);

    if (placa.length <= 3) {
        return placa;
    }

    return placa.slice(0, 3) + '-' + placa.slice(3);
}

export function formatarData(valor, textoVazio = '-') {
    if (!valor) {
        return textoVazio;
    }

    const data = new Date(valor.replace(' ', 'T'));

    if (Number.isNaN(data.getTime())) {
        return textoVazio;
    }

    const formatador = new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    });

    return formatador.format(data);
}

export function capitalizar(valor) {
    const texto = String(valor ?? '');
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

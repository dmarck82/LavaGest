import { readFileSync } from 'node:fs';

// Este documento reduzido testa nosso uso do DOM. Não substitui um navegador.
export function criarDocumentoTeste(pagina) {
    const html = readFileSync(new URL('../paginas/' + pagina + '.html', import.meta.url), 'utf8');
    const elementos = new Map();
    const documento = {
        createElement: function (tag) {
            return new ElementoTeste(tag, documento);
        },
        querySelector: function (seletor) {
            if (!seletor.startsWith('#')) {
                throw new Error('Seletor não previsto no teste: ' + seletor);
            }

            const id = seletor.slice(1);

            if (!html.includes('id="' + id + '"')) {
                throw new Error('ID não existe no HTML real: ' + id);
            }

            if (!elementos.has(id)) {
                elementos.set(id, new ElementoTeste('div', documento));
            }

            return elementos.get(id);
        },
    };

    return documento;
}

class ElementoTeste {
    constructor(tag, documento) {
        this.tagName = tag;
        this.ownerDocument = documento;
        this.children = [];
        this.textContent = '';
        this.innerHTML = '';
        this.className = '';
        this.value = '';
        this.eventos = {};
        this.atributos = {};
        this.disabled = false;
        this.required = false;
        this.classList = {
            add: (...classes) => {
                const atuais = this.className.split(' ');
                this.className = atuais.concat(classes).join(' ').trim();
            },
            remove: (classe) => {
                this.className = this.className.split(' ').filter(function (item) {
                    return item !== classe;
                }).join(' ');
            },
        };
    }

    appendChild(elemento) {
        this.children.push(elemento);
        return elemento;
    }

    replaceChildren(...elementos) {
        this.children = elementos;
    }

    addEventListener(nome, acao) {
        if (!this.eventos[nome]) {
            this.eventos[nome] = [];
        }

        this.eventos[nome].push(acao);
    }

    async emitir(nome) {
        for (const acao of this.eventos[nome] ?? []) {
            await acao();
        }
    }

    setAttribute(nome, valor) {
        this.atributos[nome] = valor;
    }

    querySelector(seletor) {
        if (seletor !== '[type="submit"]') {
            throw new Error('Seletor de formulário inesperado: ' + seletor);
        }

        if (!this.botaoSalvar) {
            this.botaoSalvar = new ElementoTeste('button', this.ownerDocument);
        }

        return this.botaoSalvar;
    }

    reset() {
        this.foiLimpo = true;
    }
}

export const bootstrapTeste = {
    Modal: class {
        constructor(elemento) {
            this.elemento = elemento;
        }

        show() {
            this.elemento.visivel = true;
        }

        hide() {
            this.elemento.visivel = false;
        }
    },
};

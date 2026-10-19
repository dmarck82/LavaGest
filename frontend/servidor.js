import { createServer } from 'node:http';
import { readFile, realpath } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

// Este servidor é uma ferramenta de desenvolvimento. O navegador recebe só HTML, CSS e JS.
const pastaFrontend = fileURLToPath(new URL('.', import.meta.url));
const paginas = {
    '/': 'index.html',
    '/clientes': 'clientes.html',
    '/usuarios': 'usuarios.html',
    '/veiculos': 'veiculos.html',
};
const tiposDeArquivo = {
    '.js': 'text/javascript; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
};

function enviar(requisicao, resposta, status, conteudo, tipo = 'text/plain; charset=utf-8') {
    resposta.writeHead(status, {
        'Content-Type': tipo,
        'Content-Length': Buffer.byteLength(conteudo),
        'Cache-Control': 'no-store',
    });

    if (requisicao.method === 'HEAD') {
        resposta.end();
        return;
    }

    resposta.end(conteudo);
}

async function atenderRequisicao(requisicao, resposta) {
    if (requisicao.method !== 'GET' && requisicao.method !== 'HEAD') {
        resposta.setHeader('Allow', 'GET, HEAD');
        enviar(requisicao, resposta, 405, 'Método não permitido.');
        return;
    }

    let endereco;
    let caminho;

    try {
        endereco = new URL(requisicao.url, 'http://localhost');
        caminho = decodeURIComponent(endereco.pathname);

        if (caminho.includes('\\') || caminho.includes('\0')) {
            enviar(requisicao, resposta, 400, 'Endereço inválido.');
            return;
        }
    } catch (erro) {
        enviar(requisicao, resposta, 400, 'Endereço inválido.');
        return;
    }

    // Mantém os links antigos e padroniza endereços com uma barra no final.
    for (const rota of Object.keys(paginas)) {
        const arquivoHtml = paginas[rota];
        const ehLinkAntigo = caminho === '/' + arquivoHtml;
        const temBarraNoFinal = rota !== '/' && caminho === rota + '/';

        if (ehLinkAntigo || temBarraNoFinal) {
            resposta.setHeader('Location', rota + endereco.search);
            enviar(requisicao, resposta, 301, '');
            return;
        }
    }

    try {
        if (Object.hasOwn(paginas, caminho)) {
            const arquivoHtml = path.join(pastaFrontend, 'paginas', paginas[caminho]);
            const conteudo = await readFile(arquivoHtml);

            enviar(requisicao, resposta, 200, conteudo, 'text/html; charset=utf-8');
            return;
        }

        // Somente as pastas de recursos públicos podem entregar arquivos diretamente.
        const ehArquivoPublico = caminho.startsWith('/js/') || caminho.startsWith('/css/');
        const extensao = path.extname(caminho);
        const tipo = tiposDeArquivo[extensao];

        if (ehArquivoPublico && tipo) {
            const solicitado = path.resolve(pastaFrontend, '.' + caminho);
            const arquivoReal = await realpath(solicitado);
            const caminhoRelativo = path.relative(pastaFrontend, arquivoReal);

            // Impede que um caminho ou link simbólico saia da pasta do frontend.
            if (caminhoRelativo.startsWith('..') || path.isAbsolute(caminhoRelativo)) {
                enviar(requisicao, resposta, 404, 'Página não encontrada.');
                return;
            }

            const conteudo = await readFile(arquivoReal);
            enviar(requisicao, resposta, 200, conteudo, tipo);
            return;
        }

        enviar(requisicao, resposta, 404, 'Página não encontrada.');
    } catch (erro) {
        if (erro.code === 'ENOENT' || erro.code === 'ENOTDIR' || erro.code === 'EISDIR') {
            enviar(requisicao, resposta, 404, 'Página não encontrada.');
            return;
        }

        console.error(erro);
        enviar(requisicao, resposta, 500, 'Não foi possível carregar a página.');
    }
}

const host = process.env.LAVAGEST_FRONT_HOST || 'localhost';
const porta = Number(process.env.LAVAGEST_FRONT_PORT || 5500);

if (!Number.isInteger(porta) || porta < 1 || porta > 65535) {
    throw new Error('LAVAGEST_FRONT_PORT deve ser uma porta entre 1 e 65535.');
}

const servidor = createServer(atenderRequisicao);

servidor.on('error', function (erro) {
    console.error('Não foi possível iniciar o frontend: ' + erro.message);
    process.exitCode = 1;
});

servidor.listen(porta, host, function () {
    console.log('Frontend disponível em http://' + host + ':' + porta);
});

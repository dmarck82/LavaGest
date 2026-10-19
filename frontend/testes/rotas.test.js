import test from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { createServer } from 'node:net';
import { readFile, readdir, access } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const pastaFrontend = fileURLToPath(new URL('../', import.meta.url));

async function escolherPorta() {
    const servidor = createServer();
    servidor.listen(0, '127.0.0.1');
    await once(servidor, 'listening');
    const porta = servidor.address().port;
    await new Promise(function (resolve) { servidor.close(resolve); });
    return porta;
}

async function listarJavaScript(pasta) {
    const arquivos = [];

    for (const item of await readdir(pasta, { withFileTypes: true })) {
        const caminho = path.join(pasta, item.name);

        if (item.isDirectory()) {
            arquivos.push(...await listarJavaScript(caminho));
        } else if (item.name.endsWith('.js')) {
            arquivos.push(caminho);
        }
    }

    return arquivos;
}

test('servidor entrega páginas e todos os módulos com imports válidos', { timeout: 15000 }, async function () {
    const porta = await escolherPorta();
    const base = 'http://127.0.0.1:' + porta;
    const processo = spawn(process.execPath, ['servidor.js'], {
        cwd: pastaFrontend,
        env: Object.assign({}, process.env, {
            LAVAGEST_FRONT_HOST: '127.0.0.1',
            LAVAGEST_FRONT_PORT: String(porta),
        }),
        windowsHide: true,
        stdio: ['ignore', 'ignore', 'pipe'],
    });
    let log = '';
    let erroProcesso;
    processo.stderr.on('data', function (dados) { log += dados.toString(); });
    processo.on('error', function (erro) { erroProcesso = erro; });

    try {
        let iniciou = false;

        for (let tentativa = 0; tentativa < 50; tentativa++) {
            if (erroProcesso) {
                throw erroProcesso;
            }

            try {
                const resposta = await fetch(base, { signal: AbortSignal.timeout(500) });
                await resposta.text();
                iniciou = true;
                break;
            } catch (erro) {
                await new Promise(function (resolve) { setTimeout(resolve, 30); });
            }
        }

        assert.equal(iniciou, true, log);
        const paginas = {
            '/': 'index.html', '/clientes': 'clientes.html',
            '/usuarios': 'usuarios.html', '/veiculos': 'veiculos.html',
        };

        for (const [rota, arquivo] of Object.entries(paginas)) {
            const resposta = await fetch(base + rota);
            const html = await resposta.text();
            assert.equal(resposta.status, 200);
            assert.equal(html, await readFile(path.join(pastaFrontend, 'paginas', arquivo), 'utf8'));

            for (const resultado of html.matchAll(/src="(\/js\/[^"]+)"/g)) {
                const modulo = await fetch(base + resultado[1]);
                assert.equal(modulo.status, 200);
                await modulo.text();
            }
        }

        const arquivos = await listarJavaScript(path.join(pastaFrontend, 'js'));

        for (const arquivo of arquivos) {
            const caminho = '/' + path.relative(pastaFrontend, arquivo).split(path.sep).join('/');
            const resposta = await fetch(base + caminho);
            const fonte = await readFile(arquivo, 'utf8');
            assert.equal(resposta.status, 200, caminho);
            assert.match(resposta.headers.get('content-type'), /javascript/);
            assert.equal(await resposta.text(), fonte);

            for (const resultado of fonte.matchAll(/from\s+['"](\.[^'"]+)['"]/g)) {
                await access(path.resolve(path.dirname(arquivo), resultado[1]));
            }
        }

        const css = await fetch(base + '/css/estilos.css');
        assert.equal(css.status, 200);
        await css.text();

        const antigo = await fetch(base + '/clientes.html?busca=Ana', { redirect: 'manual' });
        assert.equal(antigo.status, 301);
        assert.equal(antigo.headers.get('location'), '/clientes?busca=Ana');
        await antigo.text();

        const ausente = await fetch(base + '/js/inexistente.js');
        assert.equal(ausente.status, 404);
        await ausente.text();

        const cabecalhos = await fetch(base + '/clientes', { method: 'HEAD' });
        assert.equal(cabecalhos.status, 200);
        assert.match(cabecalhos.headers.get('content-type'), /text\/html/);
        assert.equal(await cabecalhos.text(), '');

        const envio = await fetch(base + '/clientes', { method: 'POST' });
        assert.equal(envio.status, 405);
        assert.equal(envio.headers.get('allow'), 'GET, HEAD');
        await envio.text();

        for (const caminho of ['/servidor.js', '/package.json', '/paginas/clientes.html', '/backend/composer.json']) {
            const privado = await fetch(base + caminho);
            assert.equal(privado.status, 404, caminho);
            await privado.text();
        }

        for (const caminho of ['/%E0%A4%A', '/js/%00.js', '/js/..%5cservidor.js']) {
            const invalido = await fetch(base + caminho);
            assert.equal(invalido.status, 400, caminho);
            await invalido.text();
        }

        for (const [caminho, destino] of Object.entries({
            '/index.html': '/', '/usuarios.html': '/usuarios',
            '/veiculos.html': '/veiculos', '/clientes/': '/clientes',
        })) {
            const redirecionamento = await fetch(base + caminho, { redirect: 'manual' });
            assert.equal(redirecionamento.status, 301);
            assert.equal(redirecionamento.headers.get('location'), destino);
            await redirecionamento.text();
        }
    } finally {
        if (processo.pid && processo.exitCode === null) {
            const encerramento = once(processo, 'exit');
            processo.kill();
            await encerramento;
        }
    }
});

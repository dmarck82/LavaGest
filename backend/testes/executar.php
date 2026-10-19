<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use LavaGest\Banco\Conexao;
use LavaGest\Banco\InicializadorBanco;
use LavaGest\Contratos\TokenRepositorioInterface;
use LavaGest\Excecoes\ConflitoException;
use LavaGest\Excecoes\RecursoNaoEncontradoException;
use LavaGest\Http\Campos;
use LavaGest\Http\Requisicao;
use LavaGest\Http\RespostaJson;
use LavaGest\Http\Roteador;
use LavaGest\Http\TratadorDeErros;
use LavaGest\Modelos\Cliente;
use LavaGest\Modelos\Usuario;
use LavaGest\Repositorios\ClienteRepositorio;
use LavaGest\Repositorios\TokenRepositorio;
use LavaGest\Repositorios\UsuarioRepositorio;
use LavaGest\Repositorios\VeiculoRepositorio;
use LavaGest\Seguranca\Autenticador;
use LavaGest\Servicos\AutenticacaoServico;
use LavaGest\Servicos\ClienteServico;
use LavaGest\Servicos\UsuarioServico;
use LavaGest\Servicos\VeiculoServico;

$verificacoes = 0;

function conferir(bool $condicao, string $descricao): void
{
    global $verificacoes;
    $verificacoes++;

    if (!$condicao) {
        throw new RuntimeException('Falhou: ' . $descricao);
    }
}

function deveFalhar(callable $acao, string $tipo): void
{
    try {
        $acao();
    } catch (Throwable $erro) {
        conferir($erro instanceof $tipo, 'Exceção esperada: ' . $tipo . '; recebida: ' . $erro::class);
        return;
    }

    throw new RuntimeException('Esperava exceção: ' . $tipo);
}

// Esta segunda implementação do contrato permite testar sem usar o banco.
class TokenRepositorioEmMemoria implements TokenRepositorioInterface
{
    private array $registros = [];

    public function salvar(int $usuarioId, string $hash, string $expiraEm): void
    {
        $this->registros[$hash] = [
            'usuario_id' => $usuarioId,
            'expira_em' => $expiraEm,
        ];
    }

    public function buscarUsuarioId(string $hash, string $agora): ?int
    {
        $registro = $this->registros[$hash] ?? null;

        if ($registro === null) {
            return null;
        }

        if ($registro['expira_em'] <= $agora) {
            return null;
        }

        return $registro['usuario_id'];
    }
}

$tokensMemoria = new TokenRepositorioEmMemoria();

$temporario = sys_get_temp_dir() . '/lavagest-testes-' . bin2hex(random_bytes(8));
mkdir($temporario);
$servidor = null;
$pdo = null;
$codigo = 0;
$logAnterior = ini_get('error_log');
ini_set('error_log', $temporario . '/erros.log');

try {
    $usuario = new Usuario(' Ana ', 'ana@example.com', 'senha1234', '(11) 99999-8888');
    conferir($usuario->getNome() === 'Ana', 'Nome normalizado no modelo');
    $usuario->atualizar('Ana', 'ana@example.com', 'senha1234', '(21) 98888-7777', 'ativo');
    conferir($usuario->getTelefone() === '21988887777', 'Telefone normalizado também na atualização');
    deveFalhar(fn() => $usuario->atualizar('Ana', 'ana@example.com', 'senha1234', '123', 'ativo'), InvalidArgumentException::class);
    conferir($usuario->getTelefone() === '21988887777', 'Atualização inválida preserva estado');
    deveFalhar(fn() => new Usuario(' ', 'ana@example.com', 'senha1234', '11999998888'), InvalidArgumentException::class);
    deveFalhar(fn() => new Cliente('Ana', '123'), InvalidArgumentException::class);
    deveFalhar(fn() => Campos::validar(['nome' => 42], ['nome' => 'texto']), InvalidArgumentException::class);
    deveFalhar(fn() => Campos::validar([], ['nome' => 'texto'], ['nome']), InvalidArgumentException::class);

    $tratador = new TratadorDeErros();
    foreach (['[]', 'null', '42', '"texto"'] as $json) {
        $resposta = $tratador->executar(fn() => new RespostaJson((new Requisicao('POST', '/', $json))->json()));
        conferir($resposta->getStatus() === 422, 'JSON não objeto rejeitado: ' . $json);
    }
    conferir(
        $tratador->executar(fn() => new RespostaJson((new Requisicao('POST', '/', '{'))->json()))->getStatus() === 400,
        'JSON malformado é 400'
    );
    conferir(
        $tratador->executar(fn() => throw new RecursoNaoEncontradoException('Ausente'))->getStatus() === 404,
        'Recurso ausente é 404'
    );
    conferir(
        $tratador->executar(fn() => throw new ConflitoException('Conflito'))->getStatus() === 409,
        'Nova exceção de conflito funciona sem editar o tratador'
    );
    $erroInterno = $tratador->executar(fn() => throw new PDOException('Detalhe privado do banco'));
    conferir($erroInterno->getStatus() === 500 && !str_contains($erroInterno->getDados()['erro'], 'privado'), 'PDOException não é 404 e não vaza detalhes');

    $tokensMemoria->salvar(7, hash('sha256', 'valido'), '2999-01-01 00:00:00');
    $tokensMemoria->salvar(7, hash('sha256', 'expirado'), '2000-01-01 00:00:00');
    $autenticador = new Autenticador($tokensMemoria);
    conferir($autenticador->autenticar('valido') === 7, 'Autenticação por interface, sem PDO');
    deveFalhar(fn() => $autenticador->autenticar('expirado'), LavaGest\Excecoes\TokenInvalidoException::class);
    $rotas = new Roteador($autenticador);
    $rotas->adicionar('GET', '/itens/{id}', fn(Requisicao $r, array $p) => new RespostaJson(['id' => (int) $p['id']]));
    conferir($rotas->despachar(new Requisicao('GET', '/itens/12', autorizacao: 'Bearer valido'))->getDados() === ['id' => 12], 'Rota parametrizada');
    conferir($rotas->despachar(new Requisicao('POST', '/itens/12'))->getStatus() === 405, 'Método incorreto é 405');
    conferir($rotas->despachar(new Requisicao('GET', '/itens/abc'))->getStatus() === 404, 'ID inválido não casa rota');

    // Estes caminhos não podem ser confundidos com /itens/{id}.
    $caminhosInvalidos = ['/itens/', '/itens/12/', '/itens//12', '/itens/12/extra', '/outros/12', '/itens/-1'];

    foreach ($caminhosInvalidos as $caminho) {
        $requisicaoInvalida = new Requisicao('GET', $caminho);
        $respostaInvalida = $rotas->despachar($requisicaoInvalida);

        conferir($respostaInvalida->getStatus() === 404, 'Caminho não corresponde: ' . $caminho);
    }

    $campos = ['nome' => 'Ana', 'email' => null, 'campo_desconhecido' => 'ignorado'];
    $tipos = ['nome' => 'texto', 'email' => '?texto'];
    $validados = Campos::validar($campos, $tipos, ['nome']);

    conferir($validados === ['nome' => 'Ana', 'email' => null], 'Validação preserva null opcional e ignora campos não declarados');


    // Simula um banco anterior ao controle de migrações, preservando dados.
    $legado = Conexao::criar(':memory:');
    $migracoes = glob(dirname(__DIR__) . '/migracoes/*.sql');
    sort($migracoes);
    foreach ($migracoes as $arquivo) {
        $legado->exec(file_get_contents($arquivo));
    }
    (new ClienteRepositorio($legado))->criar(new Cliente('Legado', '11977776666'));
    conferir(InicializadorBanco::executar($legado) === 5, 'Migrações compatíveis com banco existente');
    conferir((int) $legado->query('SELECT COUNT(*) FROM cli_cliente')->fetchColumn() === 1, 'Dados anteriores preservados');
    conferir(InicializadorBanco::executar($legado) === 0, 'Migrações não repetem execução');

    $migrar = function (int $quantidade) use ($temporario): void {
        $ambiente = getenv();
        $ambiente['LAVAGEST_DB_PATH'] = $temporario . '/comando.sqlite';
        $processo = proc_open(
            [PHP_BINARY, 'bin/migrar.php'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__),
            $ambiente
        );
        if (!is_resource($processo)) {
            throw new RuntimeException('Não foi possível testar o comando de migração.');
        }
        fclose($pipes[0]);
        $saida = stream_get_contents($pipes[1]);
        $erros = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        conferir(proc_close($processo) === 0, 'Comando de migração executou: ' . $erros);
        conferir(str_starts_with($saida, $quantidade . ' migração'), 'Comando respeita banco configurado e migrações pendentes');
    };
    $migrar(5);
    $migrar(0);

    $caminhoBanco = $temporario . '/banco.sqlite';
    $pdo = Conexao::criar($caminhoBanco);
    conferir(InicializadorBanco::executar($pdo) === 5, 'Banco novo recebe cinco migrações');
    $usuariosRepo = new UsuarioRepositorio($pdo);
    $clientesRepo = new ClienteRepositorio($pdo);
    $veiculosRepo = new VeiculoRepositorio($pdo);
    $usuarios = new UsuarioServico($usuariosRepo);
    $clientes = new ClienteServico($clientesRepo, $veiculosRepo);
    $veiculos = new VeiculoServico($veiculosRepo, $clientesRepo);
    $autenticacao = new AutenticacaoServico($usuariosRepo, new TokenRepositorio($pdo));

    $ana = $usuarios->criar(['nome' => 'Ana', 'email' => 'ana@example.com', 'senha' => 'senha1234', 'telefone' => '11999998888']);
    conferir(password_verify('senha1234', $usuariosRepo->buscarPorId($ana->getId())->getSenha()), 'Senha salva com hash');
    conferir(!array_key_exists('senha', $ana->paraArray()), 'Senha não aparece na resposta');
    deveFalhar(fn() => $usuarios->criar(['nome' => 'Outra', 'email' => 'ana@example.com', 'senha' => 'senha1234', 'telefone' => '11999998888']), LavaGest\Excecoes\EmailJaCadastradoException::class);
    $bob = $usuarios->criar(['nome' => 'Bob', 'email' => 'bob@example.com', 'senha' => 'senha1234', 'telefone' => '11999997777']);
    deveFalhar(fn() => $usuarios->atualizar($bob->getId(), ['email' => 'ana@example.com']), LavaGest\Excecoes\EmailJaCadastradoException::class);
    $usuarios->atualizar($ana->getId(), ['telefone' => '(21) 98888-7777']);
    $usuarios->atualizarSenha($ana->getId(), 'outrasenha123');
    deveFalhar(fn() => $autenticacao->autenticar('ana@example.com', 'senha1234'), LavaGest\Excecoes\CredenciaisInvalidasException::class);
    $login = $autenticacao->autenticar('ana@example.com', 'outrasenha123');
    conferir((new Autenticador(new TokenRepositorio($pdo)))->autenticar($login['token']) === $ana->getId(), 'Login e validação de token com PDO');
    conferir($usuariosRepo->buscarPorId($ana->getId())->getUltimoLogin() !== null, 'Último login atualizado');
    conferir($pdo->query('SELECT aut_token_hash FROM aut_token')->fetchColumn() === hash('sha256', $login['token']), 'Somente hash do token é persistido');

    $cliente = $clientes->criar(['nome' => 'Cliente', 'whatsapp' => '(11) 98888-7777', 'email' => 'cliente@example.com']);
    $idCliente = $cliente->getId();
    deveFalhar(fn() => $clientes->criar(['nome' => 'Duplicado', 'whatsapp' => '11988887777']), LavaGest\Excecoes\WhatsappJaCadastradoException::class);
    $outroCliente = $clientes->criar(['nome' => 'Outro', 'whatsapp' => '11966665555']);
    deveFalhar(fn() => $clientes->atualizar($outroCliente->getId(), ['whatsapp' => '11988887777']), LavaGest\Excecoes\WhatsappJaCadastradoException::class);
    conferir($clientes->atualizar($idCliente, ['email' => null])->getEmail() === null, 'Campo opcional pode ser limpo');
    conferir($clientes->buscar($idCliente)->getNome() === 'Cliente', 'Atualização parcial preserva campos');
    $dadosVeiculo = ['cliente_id' => $idCliente, 'placa' => 'abc-1234', 'modelo' => 'Uno', 'categoria' => 'hatch'];
    $veiculo = $veiculos->criar($dadosVeiculo);
    conferir($veiculo->getPlaca() === 'ABC1234', 'Placa normalizada');
    deveFalhar(fn() => $veiculos->criar($dadosVeiculo), LavaGest\Excecoes\PlacaJaCadastradaException::class);
    $segundo = $veiculos->criar(array_replace($dadosVeiculo, ['placa' => 'DEF1A23']));
    deveFalhar(fn() => $veiculos->atualizar($segundo->getId(), ['placa' => 'ABC1234']), LavaGest\Excecoes\PlacaJaCadastradaException::class);
    deveFalhar(fn() => $veiculos->criar(array_replace($dadosVeiculo, ['cliente_id' => 99999])), RecursoNaoEncontradoException::class);
    $atualizado = $veiculos->atualizar($veiculo->getId(), ['cor' => 'Azul']);
    conferir($atualizado->paraArray()['criado_em'] === $veiculo->paraArray()['criado_em'], 'Atualização preserva data de criação do veículo');
    deveFalhar(fn() => $clientes->deletar($idCliente), LavaGest\Excecoes\ClientePossuiVeiculosException::class);
    deveFalhar(fn() => $clientesRepo->deletar($idCliente), LavaGest\Excecoes\ClientePossuiVeiculosException::class);
    $veiculos->deletar($veiculo->getId());
    $veiculos->deletar($segundo->getId());
    $clientes->deletar($idCliente);
    deveFalhar(fn() => $clientes->buscar($idCliente), RecursoNaoEncontradoException::class);
    $usuarios->deletar($ana->getId());
    deveFalhar(fn() => (new Autenticador(new TokenRepositorio($pdo)))->autenticar($login['token']), LavaGest\Excecoes\TokenInvalidoException::class);

    // Exercita a aplicação real em porta livre e banco exclusivo dos testes.
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
        throw new RuntimeException($errstr);
    }
    $endereco = stream_socket_get_name($socket, false);
    fclose($socket);
    $ambiente = getenv();
    $ambiente['LAVAGEST_DB_PATH'] = $caminhoBanco;
    $ambiente['LAVAGEST_CORS_ORIGIN'] = 'http://teste.local';
    $servidor = proc_open(
        [PHP_BINARY, '-S', $endereco, 'public/index.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $temporario . '/servidor.log', 'a'], 2 => ['file', $temporario . '/servidor.log', 'a']],
        $pipes,
        dirname(__DIR__),
        $ambiente
    );
    if (!is_resource($servidor)) {
        throw new RuntimeException('Não foi possível iniciar o servidor de testes.');
    }
    fclose($pipes[0]);
    $pronto = false;
    for ($tentativa = 0; $tentativa < 100; $tentativa++) {
        $conexao = @stream_socket_client('tcp://' . $endereco, $errno, $errstr, 0.1);
        if ($conexao !== false) {
            fclose($conexao);
            $pronto = true;
            break;
        }
        usleep(50000);
    }
    conferir($pronto, 'Servidor HTTP iniciou');

    $http = function (string $metodo, string $caminho, int $status, mixed $dados = null, ?string $token = null, bool $cru = false) use ($endereco): mixed {
        $cabecalhos = ['Content-Type: application/json', 'Connection: close'];
        if ($token !== null) {
            $cabecalhos[] = 'Authorization: Bearer ' . $token;
        }
        $contexto = stream_context_create(['http' => [
            'method' => $metodo,
            'header' => implode("\r\n", $cabecalhos),
            'content' => $dados === null ? '' : ($cru ? $dados : json_encode($dados, JSON_THROW_ON_ERROR)),
            'ignore_errors' => true,
            'timeout' => 5,
        ]]);
        $corpo = file_get_contents('http://' . $endereco . $caminho, false, $contexto);
        conferir($corpo !== false, 'HTTP respondeu: ' . $metodo . ' ' . $caminho);
        conferir(str_contains($http_response_header[0], ' ' . $status . ' '), $metodo . ' ' . $caminho . ' esperado ' . $status . ', recebido ' . $http_response_header[0] . ' ' . $corpo);
        conferir(in_array('Access-Control-Allow-Origin: http://teste.local', $http_response_header, true), 'CORS configurado por ambiente');

        if ($status === 204) {
            conferir($corpo === '', 'Resposta 204 sem corpo');
            return null;
        }

        return json_decode($corpo, true, 512, JSON_THROW_ON_ERROR);
    };

    $http('OPTIONS', '/clientes', 204);
    $http('GET', '/clientes', 401);
    $http('GET', '/clientes', 401, token: 'invalido');
    $http('GET', '/ausente', 404);
    $http('PATCH', '/clientes', 405);
    $http('POST', '/usuarios', 400, '{', cru: true);
    foreach (['[]', 'null', '42', '"texto"'] as $corpoInvalido) {
        $http('POST', '/usuarios', 422, $corpoInvalido, cru: true);
    }
    $http('POST', '/usuarios', 422, ['nome' => []]);
    $dadosUsuario = ['nome' => 'HTTP', 'email' => 'http@example.com', 'senha' => 'senha1234', 'telefone' => '11999998888'];
    $novoUsuario = $http('POST', '/usuarios', 201, $dadosUsuario);
    conferir(!array_key_exists('senha', $novoUsuario), 'Resposta HTTP não expõe senha');
    $http('POST', '/usuarios', 409, $dadosUsuario);
    $http('POST', '/autenticacao', 401, ['email' => 'http@example.com', 'senha' => 'incorreta']);
    $loginHttp = $http('POST', '/autenticacao', 200, ['email' => 'http@example.com', 'senha' => 'senha1234']);
    $token = $loginHttp['token'];
    $http('GET', '/usuarios', 200, token: $token);
    $http('GET', '/usuarios/' . $novoUsuario['id'], 200, token: $token);
    $http('PUT', '/usuarios/' . $novoUsuario['id'], 422, ['telefone' => 123], $token);
    $http('PUT', '/usuarios/' . $novoUsuario['id'], 200, ['nome' => 'Atualizado'], $token);
    $clienteHttp = $http('POST', '/clientes', 201, ['nome' => 'HTTP', 'whatsapp' => '11933332222', 'email' => 'clientehttp@example.com'], $token);
    $http('GET', '/clientes/' . $clienteHttp['id'], 200, token: $token);
    $clienteMantido = $http('PUT', '/clientes/' . $clienteHttp['id'], 200, new stdClass(), $token);
    conferir($clienteMantido === $clienteHttp, 'Objeto JSON vazio preserva os campos na atualização');

    $clienteRenomeado = $http('PUT', '/clientes/' . $clienteHttp['id'], 200, ['nome' => 'Nome atualizado'], $token);
    conferir($clienteRenomeado['email'] === 'clientehttp@example.com', 'Campo ausente mantém o valor anterior');

    $http('PUT', '/clientes/' . $clienteHttp['id'], 422, ['nome' => null], $token);
    $clienteSemEmail = $http('PUT', '/clientes/' . $clienteHttp['id'], 200, ['email' => null], $token);
    conferir($clienteSemEmail['email'] === null, 'Null explícito limpa o campo opcional');
    $http('POST', '/veiculos', 422, ['cliente_id' => '1'], $token);
    $veiculoHttp = $http('POST', '/veiculos', 201, ['cliente_id' => $clienteHttp['id'], 'placa' => 'GHI1A23', 'modelo' => 'Uno', 'categoria' => 'hatch'], $token);
    $http('GET', '/veiculos', 200, token: $token);
    $http('GET', '/veiculos/' . $veiculoHttp['id'], 200, token: $token);
    $http('PUT', '/veiculos/' . $veiculoHttp['id'], 200, ['marca' => 'Fiat', 'ano_fabricacao' => null], $token);
    $http('DELETE', '/clientes/' . $clienteHttp['id'], 409, token: $token);
    $http('DELETE', '/veiculos/' . $veiculoHttp['id'], 204, token: $token);
    $http('DELETE', '/clientes/' . $clienteHttp['id'], 204, token: $token);
    $http('GET', '/clientes/' . $clienteHttp['id'], 404, token: $token);
    $http('DELETE', '/usuarios/' . $novoUsuario['id'], 204, token: $token);
    $http('GET', '/usuarios', 401, token: $token);

    $loginBob = $http('POST', '/autenticacao', 200, ['email' => 'bob@example.com', 'senha' => 'senha1234']);
    $pdo->exec('DROP TABLE cli_cliente');
    $falhaBanco = $http('GET', '/clientes', 500, token: $loginBob['token']);
    conferir($falhaBanco === ['erro' => 'Erro interno do servidor.'], 'Falha real de banco é 500 genérico; requisição não recria tabelas');

    echo "OK: {$verificacoes} verificações de domínio, serviços, persistência e HTTP." . PHP_EOL;
} catch (Throwable $erro) {
    fwrite(STDERR, (string) $erro . PHP_EOL);
    if (is_file($temporario . '/servidor.log')) {
        fwrite(STDERR, file_get_contents($temporario . '/servidor.log'));
    }
    $codigo = 1;
} finally {
    if (is_resource($servidor)) {
        proc_terminate($servidor);
        proc_close($servidor);
    }
    // Solta referências PDO antes de remover somente os arquivos criados neste teste.
    unset($pdo, $legado, $usuariosRepo, $clientesRepo, $veiculosRepo, $usuarios, $clientes, $veiculos, $autenticacao);
    ini_set('error_log', $logAnterior);
    foreach (['banco.sqlite', 'banco.sqlite-journal', 'banco.sqlite-shm', 'banco.sqlite-wal', 'comando.sqlite', 'servidor.log', 'erros.log'] as $nome) {
        $arquivo = $temporario . '/' . $nome;
        if (is_file($arquivo)) {
            unlink($arquivo);
        }
    }
    rmdir($temporario);
}

exit($codigo);

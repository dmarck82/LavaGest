# Guia de estudo do backend

Leia este guia com o editor aberto. A ideia é seguir uma requisição e descobrir
por que cada classe existe.

## Por onde começar

1. [ClienteControlador](src/Controladores/ClienteControlador.php): veja o método
   `criar`, do recebimento dos dados até a resposta.
2. [ClienteServico](src/Servicos/ClienteServico.php): veja quem cria o objeto,
   pede para salvar e verifica regras entre cliente e veículo.
3. [Cliente](src/Modelos/Cliente.php): veja as regras do próprio cliente,
   como nome obrigatório e normalização do WhatsApp.
4. [ClienteRepositorioInterface](src/Contratos/ClienteRepositorioInterface.php):
   veja as operações prometidas por um repositório de clientes.
5. [ClienteRepositorio](src/Repositorios/ClienteRepositorio.php): veja a
   implementação dessas operações usando SQL.
6. [Aplicacao](src/Aplicacao.php): veja onde esses objetos são criados e ligados.
7. [Rotas de clientes](rotas/clientes.php) e
   [Roteador](src/Http/Roteador.php): veja como o endereço escolhe o controlador.
8. Por último, leia o teste em [testes/executar.php](testes/executar.php).

Para executar os testes, entre na pasta `backend` e rode `composer test`.

## Um pedido completo: cadastrar cliente

O navegador envia:

```http
POST /clientes
Authorization: Bearer seu-token
Content-Type: application/json

{"nome": "Ana", "whatsapp": "(11) 99999-8888"}
```

O percurso é:

```text
POST /clientes
  -> Roteador encontra a rota e confere o token
  -> ClienteControlador lê e valida os tipos dos campos
  -> ClienteServico cria o objeto Cliente e pede para salvar
  -> Cliente valida e normaliza seus valores
  -> ClienteRepositorio executa o INSERT
  -> ClienteControlador monta uma resposta com status 201
```

O modelo é construído dentro do serviço. Por isso suas validações acontecem
antes de o serviço chamar o repositório.

### O controlador entende HTTP

O método `ClienteControlador::criar` tem etapas visíveis:

```php
$dadosRecebidos = $requisicao->json();
$camposObrigatorios = ['nome', 'whatsapp'];
$dadosValidados = Campos::validar($dadosRecebidos, self::CAMPOS, $camposObrigatorios);

$cliente = $this->servico->criar($dadosValidados);
$dadosResposta = $cliente->paraArray();

return new RespostaJson($dadosResposta, 201);
```

Ele sabe que existem JSON e status HTTP. Não precisa saber qual tabela salva o
cliente nem escrever SQL.

`Campos` verifica a forma da entrada: por exemplo, se `nome` é um texto.
`Cliente` verifica a regra do valor: por exemplo, se o nome está vazio.
Assim a regra continua valendo mesmo se o cliente for criado fora de uma rota.

### O serviço coordena a operação

No serviço, a sequência é criar o objeto, chamar o repositório e retornar o
cliente salvo. No método `deletar`, existe uma decisão adicional: se o cliente
possui veículos, sua exclusão é recusada.

Essa regra usa informações de dois recursos. Ela fica no serviço porque um
objeto `Cliente` não precisa consultar o banco para funcionar.

### A interface é um acordo

A interface declara métodos e seus tipos. Ela não executa SQL:

```php
interface ClienteRepositorioInterface
{
    public function criar(Cliente $cliente): Cliente;
}
```

Esse é só um trecho da interface real. Ele diz: “receba um cliente e devolva o
cliente salvo”.

O repositório declara `implements ClienteRepositorioInterface` e precisa
implementar os métodos desse acordo. O serviço usa esse acordo no construtor:

```php
private ClienteRepositorioInterface $clienteRepositorio;
```

Assim podemos fornecer um repositório que usa SQLite ou outro que guarda dados
em memória durante um teste, desde que ambos respeitem o contrato.

### A aplicação liga as peças

O arquivo `Aplicacao.php` cria os objetos em ordem:

```php
$clienteRepositorio = new ClienteRepositorio($conexao);
$clienteServico = new ClienteServico($clienteRepositorio, $veiculoRepositorio);
$clienteControlador = new ClienteControlador($clienteServico);
```

Isso é **injeção de dependência**: entregar ao objeto os colaboradores de que
ele precisa. O próprio serviço não faz `new ClienteRepositorio(...)`.

## Onde aparece SOLID

| Letra | Ideia | Exemplo no projeto |
| --- | --- | --- |
| S | Cada parte tem uma responsabilidade clara. | Controlador adapta HTTP; serviço coordena; modelo valida suas regras; repositório persiste. |
| O | Permitir novas implementações sem reescrever quem as usa. | Outra implementação de `ClienteRepositorioInterface` pode ser fornecida ao mesmo serviço. |
| L | Uma implementação substituta precisa cumprir o comportamento prometido. | Repositórios de tokens do banco e da memória devolvem `null` para token inválido ou expirado. |
| I | Contratos devem atender às necessidades de seus consumidores. | Há contratos específicos por recurso, em vez de um repositório genérico com métodos para tudo. |
| D | Regras da aplicação dependem de contratos. | `ClienteServico` recebe interfaces, sem depender diretamente de PDO. |

SOLID orienta decisões. Ele não exige uma classe para cada método. Por isso
mantemos um serviço por recurso, reunindo as operações de CRUD.

Os contratos ainda agrupam operações por recurso. Se surgir uma necessidade
real de separar leitura e escrita, podemos refinar essa divisão. O contrato deve
ser útil para o código atual.

## Algumas escolhas de escrita

- Declaramos propriedades e fazemos as atribuições no construtor, deixando a
  ligação entre parâmetro e atributo visível.
- Usamos variáveis intermediárias para separar “buscar”, “converter” e “responder”.
- Preferimos `foreach` para montar listas e `if` para decisões.
- Os nomes indicam o papel: `$clienteRepositorio`, `$dadosRecebidos`,
  `$dadosResposta`.
- Há repetição nos CRUDs. Isso facilita comparar os arquivos e enxergar o fluxo.
  Extrairemos algo compartilhado quando a repetição esconder uma regra importante.

## Sintaxe que você vai encontrar

| Trecho | O que significa |
| --- | --- |
| `Cliente $cliente` | O parâmetro precisa ser um objeto Cliente. |
| `: Cliente` | O método devolve um objeto Cliente. |
| `?Cliente` | Pode ser Cliente ou `null`. |
| `void` | O método não devolve um valor. |
| `$this->clienteRepositorio` | Acessa o atributo daquele objeto. |
| `new Cliente(...)` | Cria um objeto. |
| `implements` | Declara que uma classe cumpre uma interface. |
| `throw new ...` | Interrompe a operação e informa um erro por exceção. |
| `$dados['telefone'] ?? null` | Usa o valor se existir e não for `null`; caso contrário, usa `null`. |
| `array_key_exists('email', $dados)` | Verifica se a chave foi enviada, mesmo que o valor seja `null`. |
| `callable` | Uma função que pode ser recebida e executada depois. |
| `self::CAMPOS` | Acessa uma constante declarada naquela classe. |
| `stdClass` | Objeto simples que o PHP usa ao decodificar um objeto JSON. |
| `PDO` | Objeto usado para conversar com o banco. |

Nos arquivos de rotas há funções como:

```php
$roteador->adicionar('GET', '/clientes', function () use ($controlador): RespostaJson {
    return $controlador->listar();
});
```

A função é guardada pelo roteador e executada quando chegar `GET /clientes`.
O trecho `use ($controlador)` disponibiliza a variável externa dentro da função.
É diferente de `use LavaGest\\...`, no começo do arquivo, que importa o nome
de uma classe.

Na atualização, distinguir ausência de `null` é importante:
`{}` mantém o e-mail atual; `{"email": null}` limpa o e-mail.

## Como estudar uma alteração

Tente seguir o campo `telefone` do JSON até o banco. Depois acompanhe a tentativa
de excluir um cliente que possui veículos: encontre onde a exceção nasce e onde
ela vira uma resposta 409.

Quando adicionar uma funcionalidade, escreva primeiro o caminho completo para
um único recurso. Use os demais CRUDs como exemplos e rode os testes depois da
mudança. Os comandos, variáveis de ambiente e detalhes operacionais estão em
[ARQUITETURA.md](ARQUITETURA.md).

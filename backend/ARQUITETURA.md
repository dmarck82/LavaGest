# Padrão do backend

Para uma explicação passo a passo e um exemplo de requisição completa, comece
pelo [GUIA_DE_ESTUDO.md](GUIA_DE_ESTUDO.md).

O backend usa uma arquitetura em camadas com serviços de aplicação e contratos
de repositório. Cada recurso tem um serviço com suas operações de CRUD; crie um
serviço separado quando surgir um fluxo com responsabilidade própria, como
autenticação ou agendamento.

## Caminho de uma requisição

`Roteador → Controlador HTTP → Serviço de aplicação → Contrato → Repositório PDO`

Os serviços usam modelos de domínio. O caminho de volta retorna modelos aos
controladores, que convertem os dados públicos com `paraArray()` e montam
`RespostaJson`.

| Pasta | Responsabilidade |
| --- | --- |
| `rotas/` | Registrar método, caminho, ação e proteção. |
| `src/Http/` | Requisição, JSON, validação estrutural, resposta, roteamento e tradução de exceções. |
| `src/Controladores/` | Adaptar entrada HTTP e saída dos serviços. |
| `src/Servicos/` | Coordenar casos de uso sem SQL, saída HTTP ou variáveis globais. |
| `src/Modelos/` | Validar e normalizar regras das entidades. |
| `src/Contratos/` | Definir operações de persistência usadas pela aplicação. |
| `src/Repositorios/` | Implementar contratos com PDO/SQLite e traduzir violações de persistência conhecidas. |
| `src/Seguranca/` | Validar o token recebido explicitamente, sem ler cabeçalhos. |
| `src/Banco/` | Criar conexão e executar migrações. |
| `src/Aplicacao.php` | Compor dependências concretas e iniciar o fluxo HTTP. |

## Regras para novos recursos

1. Coloque invariantes na entidade; validação de formato/tipo de JSON fica em
   `Campos`, chamada pelo controlador.
2. Defina um contrato específico de persistência. Métodos extras de consultas
   internas podem continuar apenas no repositório concreto.
3. Faça o repositório PDO implementar esse contrato.
4. Injete contratos no serviço; serviços não instanciam repositórios.
5. Faça o controlador receber o serviço e devolver `RespostaJson`.
6. Adicione o arquivo de registro de rotas, chame sua função em `rotas/api.php`
   e crie os objetos necessários em `Aplicacao`.
7. Cubra o comportamento em `testes/executar.php`.

Não é necessário criar classe-base, repositório genérico, contêiner de DI nem
interface para cada classe. A composição explícita pode mudar ao adicionar um
recurso: ela é o local esperado para escolher implementações.

Os contratos usam nomes como `ClienteRepositorioInterface` para tornar seu papel
explícito. Os contratos atuais são por recurso. Se um consumidor passar a precisar de uma
capacidade independente (somente consulta, por exemplo), separe essa capacidade
em outro contrato quando isso reduzir acoplamento de fato.

## Estilo de código para estudo

Declare atributos e construtores explicitamente. Prefira nomes completos,
variáveis intermediárias, `if` e `foreach`. Evite reunir busca, conversão e
resposta na mesma expressão. Comentários explicam decisões e o papel das camadas.

As rotas são registradas por funções nomeadas, com callbacks escritos em várias
linhas. O roteador compara os caminhos parte por parte e reconhece `{id}` como
um identificador numérico.

## HTTP e erros

- Leitura e validação do objeto JSON ficam em `Requisicao`; `null`, listas e
  escalares são rejeitados com 422; JSON malformado retorna 400.
- Campos ausentes no PUT preservam valores; campos opcionais aceitam `null`
  para limpeza. Campos obrigatórios presentes não aceitam `null`.
- Rotas novas são protegidas por padrão. São públicas, como antes,
  `POST /usuarios` e `POST /autenticacao`.
- Rotas inexistentes retornam 404; método não registrado em caminho existente
  retorna 405. Não há autenticação executada para uma rota inexistente.
- Exceções de negócio derivam de `ConflitoException` (409),
  `NaoAutorizadoException` (401) ou `RecursoNaoEncontradoException` (404).
  Novos conflitos específicos não exigem editar `TratadorDeErros`.
- `InvalidArgumentException` retorna 422. Demais falhas são registradas no log
  e retornam 500 com mensagem genérica. Erros de banco não são tratados como 404.
- A exclusão de cliente com veículos é barrada no serviço e protegida também
  pela chave estrangeira no banco. O repositório traduz esse conflito.
- Duplicidade é identificada por SQLSTATE e consulta ao campo único, sem depender
  do texto de erro do driver. As constraints continuam sendo a proteção contra
  concorrência.

As regras de acesso existentes foram preservadas. Papéis/permissões e alteração
da política para usuários inativos são decisões de produto separadas.

## Banco e configuração

Instale as dependências e rode `composer migrar` antes de iniciar a API.
Migrações não executam durante requisições HTTP.

Cada arquivo SQL novo deve receber um nome ordenável, como `006_...`.
O executor registra as migrações aplicadas na tabela `migracoes` e executa
cada arquivo pendente dentro de uma transação. Não altere migrações já aplicadas.

As cinco migrações existentes usam `IF NOT EXISTS`, permitindo registrar o
histórico de bancos anteriores sem recriar tabelas ou apagar seus dados.
Foi testada a preservação de dados em um banco anterior ao histórico.

Variáveis de ambiente opcionais:

| Variável | Padrão |
| --- | --- |
| `LAVAGEST_DB_PATH` | `backend/dados/banco.sqlite` |
| `LAVAGEST_CORS_ORIGIN` | `http://localhost:5500` |

O backend continua usando SQLite. O caminho configurável e os contratos permitem
isolar testes; outro banco exige outra implementação e migrações compatíveis.
O arquivo `.env.example` é apenas documentação, sem carregamento automático.

Exemplo PowerShell:

```powershell
$env:LAVAGEST_DB_PATH = 'D:/dados/lavagest/banco.sqlite'
$env:LAVAGEST_CORS_ORIGIN = 'http://localhost:5500'
composer migrar
php -S localhost:8000 public/index.php
```

Use os mesmos valores no comando de migração e no processo da API.

## Verificação

`composer test` executa os testes sem dependências adicionais. A suíte cobre
modelos, contratos com dublê em memória, serviços com SQLite, migrações sobre banco
novo/legado e endpoints HTTP reais em porta livre.

O banco dos testes fica em diretório temporário próprio. O servidor e os arquivos
criados pela suíte são encerrados/removidos ao final. O banco local do projeto não
é utilizado. A suíte exige PHP CLI com PDO SQLite e `proc_open` habilitado.

Não foi adicionada ferramenta de análise estática nesta mudança. Ela pode ser
introduzida depois, com uma configuração adequada aos tipos e contratos do projeto.

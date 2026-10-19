# Estrutura do frontend

O frontend continua usando HTML, Bootstrap e JavaScript puro. O navegador carrega
módulos com `import` e `export`; não há etapa de build nem dependências npm para
executar o sistema.

A organização separa responsabilidades. Usamos ideias de SOLID quando ajudam
nessa separação, sem reproduzir todas as classes do backend no navegador.

## Pastas

```text
frontend/
  paginas/                  arquivos HTML das páginas
  servidor.js               servidor de desenvolvimento Node.js e URLs sem extensão
  css/                      estilos
  js/
    clientes.js             monta as dependências da página de clientes
    usuarios.js             monta as dependências da página de usuários
    veiculos.js             monta as dependências da página de veículos
    autenticacao.js         monta as dependências do login
    navegacao.js             menu ativo e botão Sair
    configuracao.js         endereços da API, do CEP e da FIPE
    paginas/                coordenação dos eventos e operações
    telas/                  acesso ao DOM, formulários, modais e tabelas
    servicos/               preparação/validação de dados e chamadas às APIs
    infraestrutura/         transporte HTTP e armazenamento da sessão
    compartilhado/          formatadores, máscaras, mensagens e tabelas
  testes/                   testes executados com Node.js
```

Os arquivos `api.js` e `utilitarios.js` antigos foram substituídos pelos módulos
com responsabilidades específicas nas pastas acima.

## Caminho de uma ação

```text
Usuário clica em Salvar
  -> tela avisa a página
  -> página lê os campos da tela
  -> serviço prepara os dados e chama a função de requisição
  -> cliente HTTP faz a chamada à API
  -> página trata sucesso ou erro
  -> tela atualiza o modal, a mensagem e a tabela
```

- **Entrada da página**: escolhe as implementações concretas e liga as peças.
- **Página**: decide o fluxo, como criar ou atualizar, confirmar exclusão e
  recarregar a lista. Recebe serviços e tela como parâmetros.
- **Tela**: sabe quais elementos HTML existem. Não chama API nem guarda token.
- **Serviço**: conhece os endpoints e a estrutura dos dados. Não acessa DOM,
  Bootstrap, `window` ou `sessionStorage`.
- **Cliente HTTP**: adiciona cabeçalhos, envia a requisição e interpreta resposta
  ou erro. Recebe a função que obtém o token.
- **Sessão**: guarda/remove token e expiração e encaminha ao login quando não há
  token. A validação real de autenticação continua no backend.

A validação no frontend melhora a experiência de preenchimento. As regras do
backend continuam sendo a autoridade sobre o cadastro.

## SOLID: o que foi aplicado

| Princípio | Aplicação neste projeto |
| --- | --- |
| S — responsabilidade única | DOM nas telas, fluxo nas páginas, dados e endpoints nos serviços, HTTP na infraestrutura. |
| O — aberto/fechado | Uma página pode receber outro serviço ou outra tela com as mesmas operações. Novas páginas têm seus próprios módulos. A montagem explícita pode mudar. |
| L — substituição | Não usamos herança. A ideia de substituição aparece nos colaboradores: os serviços falsos dos testes devem cumprir o comportamento esperado pela página. |
| I — segregação | Cada tela e serviço expõe operações do seu recurso. Evitamos uma interface ou controlador genérico com opções para todos os formulários. |
| D — inversão de dependência | Páginas recebem serviços/tela; serviços recebem a função de requisição. Implementações concretas ficam nos arquivos de entrada. |

Separar arquivos, sozinho, não garante SOLID. Em JavaScript puro, o acordo entre
os módulos está nos parâmetros e métodos que usamos, nos exemplos e nos testes.
Não há uma interface PHP/TypeScript que o compilador confira.

## Padrão para novas funcionalidades

1. Defina o HTML e dê nomes claros aos IDs.
2. Crie uma tela para ler/preencher esses campos e exibir seus dados.
3. Crie um serviço para preparar os dados e conversar com a API.
4. Escreva a página que liga os eventos às operações.
5. Monte essas dependências em um arquivo de entrada em `js/` e carregue-o no
   HTML com `type="module"`.
6. Registre a URL pública no mapa `paginas` de `servidor.js`.
7. Cubra o comportamento com testes.

Prefira funções nomeadas, `if`, `for...of`, nomes completos e etapas visíveis.
Aceitamos repetição pequena nos CRUDs para facilitar o estudo. Compartilhe uma
função quando ela representar uma responsabilidade comum, como exibir um aviso.

## Operações assíncronas

Durante um envio, a página bloqueia outro envio e a tela desabilita Salvar.
O botão é reativado no `finally`, inclusive quando a API recusa o cadastro.

Consultas de CEP/FIPE recebem um número sequencial. Quando a resposta chega,
a página compara esse número com o atual. Se o campo mudou ou o modal fechou,
a resposta antiga é ignorada. Os serviços dessas integrações não modificam campos.

A edição de veículos preenche marca/modelo salvos antes de consultar sugestões.
Falhas na FIPE permitem continuar preenchendo manualmente. CEP indisponível
também mantém o preenchimento manual.

## Executar e testar

Na raiz do repositório:

```powershell
npm --prefix frontend run dev
```

O backend continua sendo executado separadamente em `http://localhost:8000`.
Os endereços das integrações estão em `js/configuracao.js`.

O frontend usa somente Node.js 20 ou superior para servir os arquivos e executar
os testes. O PHP é necessário apenas para o backend. Não é preciso instalar
pacotes npm.

`servidor.js` usa os módulos nativos do Node. Ele associa `/clientes`, `/usuarios`
e `/veiculos` aos arquivos de `paginas/`, inclusive ao abrir uma URL diretamente
ou atualizar a página. Essa associação precisa existir no servidor de arquivos;
ela não é feita pelos scripts executados no navegador.

O endereço padrão é `http://localhost:5500`. Para alterar o servidor local,
configure `LAVAGEST_FRONT_HOST` e `LAVAGEST_FRONT_PORT` antes de executar o comando.
Se mudar a origem do frontend, ajuste também `LAVAGEST_CORS_ORIGIN` no backend.

Na hospedagem, configure as mesmas rotas no servidor que entregar os arquivos.
O código do navegador continua sendo HTML, CSS e JavaScript puro.

Para os testes:

```powershell
cd frontend
npm test
```

Não é necessário executar `npm install`: os testes usam os recursos nativos do
Node. O teste HTTP inicia somente um servidor frontend em porta temporária e o
encerra ao terminar. Não usa o banco local.

A suíte cobre serviços, coordenação das páginas, formatação, sessão, transporte
HTTP, uso das telas com um documento de teste e entrega real de páginas/módulos.
CEP, FIPE e API são simulados nos testes de lógica, sem chamadas externas.
O documento de teste não substitui uma validação completa em navegador.

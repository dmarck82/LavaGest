# Guia de estudo do frontend

Comece pelo cadastro de clientes. Ele mostra todas as partes sem exigir que você
entenda o projeto inteiro de uma vez.

## Ordem de leitura

1. [js/clientes.js](js/clientes.js): veja onde montamos as dependências.
2. [clientesPagina.js](js/paginas/clientesPagina.js): acompanhe `salvarCliente`.
3. [clienteTela.js](js/telas/clienteTela.js): veja como lemos e mostramos os dados.
4. [clienteServico.js](js/servicos/clienteServico.js): veja os dados preparados
   e a chamada à API.
5. [clienteHttp.js](js/infraestrutura/clienteHttp.js): veja onde `fetch` acontece.
6. [paginas.test.js](testes/paginas.test.js): veja um teste da página usando
   serviços e tela de mentira.

## Como as peças se encontram

Em `js/clientes.js`, montamos a página:

```javascript
const requisicao = criarClienteHttp(URL_API, sessao.obterToken);
const clienteServico = criarClienteServico(requisicao);
const cepServico = criarCepServico(URL_CEP);
const tela = criarClienteTela(document, bootstrap);

iniciarClientes(clienteServico, cepServico, tela, confirmar);
```

Esse é o mesmo raciocínio de entregar repositórios aos serviços no backend:
um módulo recebe os colaboradores de que precisa, já preparados.

A função `criarClienteServico` devolve um objeto com métodos:

```javascript
return {
    listar: listar,
    criar: criar,
    atualizar: atualizar,
    excluir: excluir,
    filtrar: filtrar,
};
```

As funções conhecem a `requisicao` recebida. Não precisamos de uma classe
apenas para guardar essa dependência. Essa é uma escolha de escrita do projeto,
não uma regra de SOLID.

## Do clique ao cadastro

A tela registra o evento do formulário. A página fornece a função que será
executada:

```javascript
tela.aoSalvar(salvarCliente);
```

Quando o usuário envia o formulário, `salvarCliente`:

1. chama `evento.preventDefault()` para evitar o envio tradicional do HTML;
2. lê os dados com `tela.lerDados()`;
3. escolhe `clienteServico.criar(dados)` ou `atualizar(id, dados)`;
4. fecha o modal e recarrega os clientes em caso de sucesso;
5. mostra o erro dentro do modal se houver falha.

A página não precisa saber que o campo se chama `#whatsapp` nem qual é o
endereço HTTP da API. Esses detalhes ficam na tela e no serviço.

## Cada dúvida tem um lugar

| Quero mudar... | Onde começar |
| --- | --- |
| O nome de um campo HTML ou a disposição das células | Tela do recurso e seu HTML. |
| Os dados enviados para o backend | Serviço do recurso. |
| O que acontece depois de salvar ou excluir | Página do recurso. |
| O endereço da API | `js/configuracao.js`. |
| Um cabeçalho ou mensagem de falha de conexão | `infraestrutura/clienteHttp.js`. |
| A máscara do telefone | `compartilhado/formatadores.js` e `mascaras.js`. |
| Como o botão Sair limpa a sessão | `infraestrutura/sessao.js` e `navegacao.js`. |
| O serviço externo de CEP | `servicos/cepServico.js`. |

## Por que não colocar tudo na página?

Uma página que sabe montar tabela, validar telefone, salvar token e consultar
CEP fica difícil de mudar por um único motivo. A separação permite ler um fluxo
sem carregar todos esses detalhes ao mesmo tempo.

Também permite testar. Em vez de uma API real, fornecemos uma função que registra
os pedidos. Em vez de um formulário real, fornecemos uma tela que devolve dados
conhecidos. A página usa as mesmas operações em ambos os casos.

Esse é o acordo entre as partes. Se um substituto disser que tem `listar()`,
ele precisa devolver a lista esperada, não um formato completamente diferente.

## Sintaxe para acompanhar o código

| Sintaxe | Significado |
| --- | --- |
| `export function` | Disponibiliza uma função para outro módulo. |
| `import { ... } from '...'` | Usa funções exportadas por outro arquivo. |
| `async function` | Declara uma função que devolve uma promessa. |
| `await` | Espera o resultado daquela operação antes de continuar a função. |
| `try / catch / finally` | Tenta uma operação, trata a falha e executa a limpeza. |
| `addEventListener` | Registra o que fazer quando um evento ocorrer. |
| `textContent` | Coloca um valor como texto, sem interpretá-lo como HTML. |
| `for...of` | Percorre os itens de uma lista. |
| `??` | Usa o valor à direita quando o da esquerda é `null` ou ausente. |

Só usamos `innerHTML` para ícones fixos. Nomes, mensagens e outros dados são
mostrados com `textContent`.

## Exercício para entender a separação

Siga a criação de um cliente e descubra onde o WhatsApp deixa de ter máscara.
Depois encontre a diferença entre ocultar o modal e excluir um registro.
Por fim, leia o teste que impede uma resposta antiga de CEP de sobrescrever
o endereço atual.

As decisões de arquitetura, a relação com SOLID e os comandos estão em
[ARQUITETURA.md](ARQUITETURA.md).

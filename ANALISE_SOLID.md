# Análise SOLID — LavaGest

Este diagnóstico registra o estado anterior à refatoração do backend. As melhorias
aplicadas e o padrão atual estão documentados em
[backend/ARQUITETURA.md](backend/ARQUITETURA.md). Foi adotado um serviço de aplicação
por recurso, agrupando as operações de CRUD, para manter o padrão simples.

## Conclusão

O projeto segue SOLID parcialmente. A base é boa para PHP puro: já há separação entre HTTP, modelos, repositórios e infraestrutura; modelos protegem regras importantes; e as dependências entram por construtor.

O principal problema é que a aplicação ainda depende de classes concretas e repete responsabilidades HTTP/validação. A evolução recomendada é uma arquitetura em camadas pequena, com casos de uso e contratos pontuais — sem framework, classes-base genéricas ou contêiner de DI.

## O que segue boas práticas

| Princípio | Evidência | Avaliação |
| --- | --- | --- |
| S — Responsabilidade única | Modelos concentram regras; repositórios concentram SQL; `TratadorDeErros` concentra respostas de erro. | Bom começo. |
| O — Aberto/fechado | Regras das entidades estão encapsuladas em `Cliente` e `Veiculo`, sem vazar para rotas/SQL. | Parcial. |
| L — Substituição de Liskov | Não há herança no código. Evitar herança artificial é positivo. | Não se aplica hoje. |
| I — Segregação de interfaces | Não há interfaces grandes que obriguem clientes a métodos inúteis. | Não se aplica; faltam contratos úteis. |
| D — Inversão de dependência | Controladores recebem dependências no construtor. | Parcial: os tipos recebidos são concretos. |

Também são pontos positivos: `strict_types`, PSR-4, consultas PDO parametrizadas, autenticação isolada, validações no domínio e módulos reutilizáveis de API/utilitários no frontend.

## O que enfraquece SOLID

### S — responsabilidades misturadas

- `ClienteControlador` e `VeiculoControlador` validam formato de entrada, montam entidades, coordenam repositórios e convertem resposta para array. A validação de campos repete-se entre controladores.
- As rotas repetem leitura de `php://input`, `json_decode`, autenticação, status e `json_encode`. `rotas/veiculos.php` está comprimida em uma linha por operação, o que reduz legibilidade.
- `Aplicacao` configura HTTP, cria conexão, executa migrações, monta dependências e despacha rota. Como composição da aplicação isso é aceitável, mas rodar migrações a cada requisição é responsabilidade operacional separada.
- No frontend, `clientes.js` (341 linhas) reúne DOM, formulário, máscara, API, mensagens e tabela.

### O — mudanças exigem editar vários pontos

- Um novo recurso exige alterar `Aplicacao.php`, `rotas/api.php` e criar outra função de rotas; o dispatcher é uma sequência crescente de `if`.
- `TratadorDeErros` precisa ganhar um `catch` para cada exceção de negócio nova.
- As categorias estão codificadas em `Veiculo`. É aceitável enquanto forem internas; extraia para enum quando forem compartilhadas por mais de uma parte do sistema.

### L — sem violação atual

Não existem subclasses a avaliar. Não crie uma classe-base de repositório/controlador apenas para “aplicar SOLID”: usuários, clientes, veículos e tokens não têm um contrato comum claro.

### I — contratos ausentes

Não há interfaces para repositórios ou autenticação. Assim, os controladores dependem diretamente de `UsuarioRepositorio`, `ClienteRepositorio`, `VeiculoRepositorio` e `TokenRepositorio`. Isso dificulta testes isolados e troca de infraestrutura.

### D — maior oportunidade

- A injeção por construtor é correta, mas recebe implementações concretas.
- `Conexao::criar()` fixa SQLite e o caminho do arquivo.
- `Autenticador` lê `$_SERVER` diretamente, misturando validação do token com detalhe HTTP.

## Achados complementares

- Não foram encontrados testes automatizados, configuração de PHPUnit/Pest ou análise estática.
- Havia uma `ClientePossuiVeiculosException`, mas a exclusão delegava ao banco e o tratador não a tratava. Correção do diagnóstico inicial: como `PDOException` herda de `RuntimeException`, a restrição podia retornar 404 indevidamente. A refatoração distingue conflito (409), recurso inexistente (404) e falha interna (500).
- Repositórios identificam algumas restrições únicas pelo texto da mensagem de `PDOException`; isso acopla o comportamento ao SQLite.
- `Usuario::atualizar()` normaliza telefone de forma diferente do construtor, uma regra duplicada que pode divergir.

## Padrão simples recomendado

Adotar arquitetura em camadas com **casos de uso** e **portas de repositório**:

```text
Rota HTTP → Controlador HTTP → Caso de uso → Interface de repositório → Repositório PDO
                              └→ Modelo de domínio
```

- **Rota:** casa método/caminho e chama o controlador.
- **Controlador HTTP:** lê JSON, valida formato básico e produz resposta JSON.
- **Caso de uso:** por exemplo `CriarCliente`, `AtualizarVeiculo`, `AutenticarUsuario`; coordena regras sem conhecer PDO, `$_SERVER` ou `echo`.
- **Modelo:** mantém invariantes de negócio, como já acontece.
- **Interface:** expõe apenas operações usadas pelo caso de uso, como `buscarPorId`, `salvar`, `remover` e `possuiVeiculos`.
- **Repositório PDO:** implementa a interface e concentra SQL.
- **Aplicacao:** apenas compõe implementações concretas.

Isso é uma versão leve de Ports & Adapters/Clean Architecture. Não adotar CQRS, barramento de comandos, fábricas genéricas ou `BaseRepository` agora.

## Plano incremental

1. Padronizar as rotas: formatar `rotas/veiculos.php` e criar pequenos `LeitorJson` e `RespostaJson` para remover repetição.
2. Corrigir exclusão de cliente com veículos e criar testes para os fluxos críticos.
3. Extrair primeiro um caso de uso — `AutenticarUsuario` ou `CriarCliente` — e criar somente as interfaces de repositório que ele consome.
4. Aplicar o mesmo formato a veículos, serviços e agendamentos à medida que forem criados.
5. Executar migrações em setup/deploy, não por requisição; deixar banco e CORS configuráveis por ambiente.

## Regra de decisão

Criar uma interface ou serviço novo apenas quando houver duas implementações (inclusive teste), regra compartilhada entre casos de uso, mistura de HTTP/domínio/persistência na mesma classe, ou uma mudança externa que deva ficar confinada. Assim o projeto permanece simples, porém ganha uma direção arquitetural consistente.

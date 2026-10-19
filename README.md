# LavaGest

Projeto de estudo para praticar Programação Orientada a Objetos em PHP puro enquanto construímos, gradualmente, um sistema de gestão para lava jato.

## Estado atual

- API REST em PHP puro.
- Banco SQLite preparado por comando de migração, com controle das migrações aplicadas.
- CRUD de usuários.
- Autenticação por tokens opacos, com expiração.
- Rotas protegidas com o cabeçalho \`Authorization: Bearer <token>\`.
- Senhas protegidas com \`password_hash()\` e \`password_verify()\`.
- Tratamento centralizado de erros HTTP em JSON.
- Classes organizadas por namespaces PSR-4 sob \`LavaGest\`.
- Serviços de aplicação e contratos de repositório; padrão descrito em [backend/ARQUITETURA.md](backend/ARQUITETURA.md).
- Interface inicial em HTML, Bootstrap e JavaScript puro.
- Frontend organizado em páginas, telas e serviços; veja [frontend/ARQUITETURA.md](frontend/ARQUITETURA.md).

## Estrutura

\`\`\`text
backend/
  public/       ponto de entrada da API
  rotas/        definição e encaminhamento das rotas
  src/          classes da aplicação
  migracoes/    estrutura do banco SQLite

frontend/
  css/
  js/
  paginas/      telas HTML de autenticação, clientes, usuários e veículos
  servidor.js   servidor de desenvolvimento JavaScript e URLs sem extensão
\`\`\`

## Executar localmente

Em um terminal:

\`\`\`bash
cd backend
composer install
composer migrar
php -S localhost:8000 public/index.php
\`\`\`

Em outro terminal, na raiz do repositório:

\`\`\`bash
npm --prefix frontend run dev
\`\`\`

Abra \`http://localhost:5500\`.

O frontend usa URLs sem extensão: `/clientes`, `/usuarios` e `/veiculos`.
O arquivo `frontend/servidor.js` usa Node.js para entregar o HTML correspondente
e redirecionar os endereços antigos com `.html`. O frontend exige Node.js 20 ou
superior e não depende de PHP. Pare o servidor antigo antes de usar o novo comando.

O backend exige PHP 8.1 ou superior com PDO SQLite. Execute \`composer test\`
na pasta \`backend\` para testar domínio, serviços, persistência e API HTTP.
Os testes usam banco temporário próprio. Configuração por ambiente e instruções
para novos recursos estão em [backend/ARQUITETURA.md](backend/ARQUITETURA.md).

Para acompanhar o código passo a passo, comece pelo [guia de estudo do backend](backend/GUIA_DE_ESTUDO.md).

Para estudar o frontend, comece pelo [guia de estudo do frontend](frontend/GUIA_DE_ESTUDO.md).
Os testes do frontend são executados com `cd frontend` e `npm test`, usando Node.js
20 ou superior. Não exigem PHP nem instalar pacotes npm.

## Próximas etapas

O domínio do LavaGest começará com clientes, veículos, serviços e agendamentos. A evolução será guiada pela prática de POO e API REST, evitando abstrações antes de haver uma necessidade real.

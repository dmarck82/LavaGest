# LavaGest

Projeto de estudo para praticar Programação Orientada a Objetos em PHP puro enquanto construímos, gradualmente, um sistema de gestão para lava jato.

## Estado atual

- API REST em PHP puro.
- Banco SQLite criado automaticamente por migrações.
- CRUD de usuários.
- Autenticação por tokens opacos, com expiração.
- Rotas protegidas com o cabeçalho \`Authorization: Bearer <token>\`.
- Senhas protegidas com \`password_hash()\` e \`password_verify()\`.
- Tratamento centralizado de erros HTTP em JSON.
- Classes organizadas por namespaces PSR-4 sob \`LavaGest\`.
- Interface inicial em HTML, Bootstrap e JavaScript puro.

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
  index.html    tela de autenticação
  usuarios.html gestão de usuários
\`\`\`

## Executar localmente

Em um terminal:

\`\`\`bash
cd backend
composer install
php -S localhost:8000 public/index.php
\`\`\`

Em outro terminal, na raiz do repositório:

\`\`\`bash
php -S localhost:5500 -t frontend
\`\`\`

Abra \`http://localhost:5500\`.

## Próximas etapas

O domínio do LavaGest começará com clientes, veículos, serviços e agendamentos. A evolução será guiada pela prática de POO e API REST, evitando abstrações antes de haver uma necessidade real.

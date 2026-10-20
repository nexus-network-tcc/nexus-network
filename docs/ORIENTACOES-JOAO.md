# Nexus Network: orientações para rodar e mexer no projeto

Este guia leva você do zero até o site funcionando no seu computador e mostra onde mexer em cada coisa.

## 1. O que você precisa

- **Windows** com o **XAMPP** instalado (PHP 8.2 ou mais novo e MariaDB/MySQL). Download: https://www.apachefriends.org
- Um editor de código (VS Code, por exemplo).
- Não precisa de Composer, Node nem npm. O projeto é PHP puro.

## 2. Colocando o projeto para rodar

1. **Copie a pasta** do projeto para `C:\xampp\htdocs\` e mantenha o nome **`Nexus Network Company`**.
   O endereço do site depende desse nome. Se mudar o nome da pasta, o endereço muda junto.
2. Abra o **XAMPP Control Panel** e inicie **Apache** e **MySQL**.
3. **Crie o banco de dados.** Escolha uma das opções:
   - **Opção A (recomendada para começar): banco de teste com dados de exemplo.**
     No phpMyAdmin (http://localhost/phpmyadmin), vá em **Importar**, escolha o arquivo `database/teste.sql` e clique em **Importar**. Ele cria o banco `nexus_network_teste` do zero, com 26 clientes, 41 prestadores, avaliações, conversas, denúncias e buscas.
   - **Opção B: cópia exata do banco do Gustavo.** Importe um arquivo de `database/backup/` (veja a seção 7).
   - **Opção C: banco vazio para uso real.** Crie o banco `nexus_network` (utf8mb4_unicode_ci), selecione-o, importe `database/schema.sql` e depois `database/dados-base.sql`.
4. **Crie o arquivo `.env`.** Copie `.env.example`, cole na mesma pasta e renomeie a cópia para `.env`. Depois ajuste:
   - `DB_NAME=nexus_network_teste` (ou `nexus_network`, conforme o banco que você criou)
   - `DB_USER=root` e, se o seu MySQL não tem senha, deixe `DB_PASS` vazio ou ausente
   - `APP_URL=` pode ficar vazio, porque o site detecta sozinho
   - `MAIL_DRIVER=log` (os e-mails não são enviados, só gravados em `storage/logs/mail.log`)
5. Abra **http://localhost/Nexus%20Network%20Company/**

### Contas de teste (banco `nexus_network_teste`)

| Tipo | E-mail | Senha |
|------|--------|-------|
| Cliente | `cliente@teste.com` | `Nexus@123` |
| Prestador | `prestador@teste.com` | `Nexus@123` |
| Administrador | `admin@teste.com` | `Nexus@123` |

Com o banco de teste ativo, a tela de login mostra botões de entrada rápida. O painel do admin fica em `/admin`.

## 3. Mapa do projeto

```
Nexus Network Company/
├── app/
│   ├── routes.php          ← todas as URLs do site e quem pode acessar cada uma
│   ├── helpers.php         ← funções pequenas usadas nas telas (e(), url(), stars()...)
│   ├── controllers/        ← recebem a requisição e decidem o que mostrar
│   ├── models/             ← consultas ao banco (User, Review, ProviderSearch, Report...)
│   ├── views/              ← as telas em HTML + PHP
│   │   ├── partials/       ← pedaços reutilizáveis (card de prestador, menu admin...)
│   │   └── layouts/main.php ← estrutura comum (cabeçalho, rodapé)
│   ├── services/           ← serviços externos (Google, geocodificação, e-mail)
│   ├── core/               ← base do mini framework (Router, Database, Auth, Csrf...)
│   └── lib/PHPMailer/      ← biblioteca de e-mail já incluída
├── database/
│   ├── schema.sql          ← estrutura das tabelas
│   ├── dados-base.sql      ← categorias e cidades
│   ├── teste.sql           ← banco de teste completo (gerado por gerar-teste.php)
│   ├── migracoes/          ← atualizações de estrutura para bancos antigos
│   └── backup/             ← cópias exatas dos bancos do Gustavo
├── public/
│   ├── assets/css/app.css  ← todo o CSS do site
│   ├── assets/js/          ← app.js (geral), chat.js, map.js
│   ├── assets/img/         ← logo e imagens
│   └── uploads/fotos/      ← fotos de perfil enviadas pelos usuários
├── storage/logs/           ← mail.log (e-mails simulados)
├── docs/                   ← este guia e a logo original
├── .env.example            ← modelo de configuração
└── README.md               ← visão geral do projeto
```

### Como uma página funciona (exemplo: `/buscar`)

1. `app/routes.php` liga a URL `/buscar` a `SearchController@index`.
2. `app/controllers/SearchController.php` lê os filtros da URL, chama `ProviderSearch::run()` e pede para mostrar a tela.
3. `app/models/ProviderSearch.php` monta e executa a consulta SQL.
4. `app/views/search/index.php` desenha o resultado em HTML.

Para criar uma página nova: adicione a rota em `routes.php`, crie o método no controller e crie o arquivo em `views/`.

## 4. Regras de ouro ao editar

- **Escape tudo o que vem do usuário** nas telas com `<?= e($valor) ?>`. Isso evita injeção de HTML (XSS).
- **Todo formulário POST precisa de `<?= csrf_field() ?>`**, senão o site recusa o envio.
- **Consultas ao banco só com parâmetros** (`Database::all('... WHERE id = ?', [$id])`). Nunca junte texto do usuário direto no SQL.
- **Mudou a estrutura do banco?** Altere o `schema.sql`, crie um arquivo novo em `database/migracoes/` (numeração seguinte: `003-...sql`) e, se for o caso, atualize `database/dados-teste-extra.sql`.
- **Para regenerar o `teste.sql`** depois de mudar o schema ou os dados de teste, rode no terminal, dentro da pasta do projeto:
  ```
  C:\xampp\php\php.exe database/gerar-teste.php
  ```
  Depois reimporte o `teste.sql` no phpMyAdmin (isso recria o banco de teste e apaga o que foi feito nele).
- **Cores do site:** preto e verde `#20cd8d`. As variáveis estão no começo de `public/assets/css/app.css`.
- **Nunca envie o `.env`** para ninguém nem para o GitHub. Ele guarda configurações e senhas locais.

## 5. Tarefas comuns

| Quero... | Mexo em... |
|----------|------------|
| Mudar texto de uma página | O arquivo em `app/views/` (home em `views/home/index.php`) |
| Mudar cores ou espaçamentos | `public/assets/css/app.css` |
| Criar um filtro na busca | `models/ProviderSearch.php`, `controllers/SearchController.php` e `views/search/index.php` |
| Adicionar uma categoria | `database/dados-base.sql` (e inserir direto no banco que está em uso) |
| Criar um admin | `C:\xampp\php\php.exe database/criar-admin.php "Nome" email@x.com "Senha@Forte1"` |
| Ver e-mails de recuperação de senha | `storage/logs/mail.log` |
| Trocar o banco usado | `DB_NAME` no `.env` |

## 6. Problemas comuns

- **A página dá 404 ou abre a lista de arquivos:** confira se a pasta se chama exatamente `Nexus Network Company` e se está em `C:\xampp\htdocs\`. No `httpd.conf` do Apache, o `mod_rewrite` precisa estar ativo (já vem ativo no XAMPP) e o `AllowOverride` de `htdocs` deve ser `All`.
- **Apache não inicia:** outra coisa está usando a porta 80 (Skype, IIS, outro servidor). Feche o programa ou troque a porta pelo botão Config do XAMPP. Se trocar a porta, o endereço vira `http://localhost:PORTA/Nexus%20Network%20Company/`.
- **"Erro de conexão" com o banco:** o MySQL não está iniciado, ou `DB_NAME`, `DB_USER` ou `DB_PASS` no `.env` estão errados.
- **Falha de certificado (SSL) ao chamar Google, endereços ou e-mail:** antivírus como Avast, AVG e Kaspersky interceptam o HTTPS. Só no seu computador de desenvolvimento, coloque `SSL_VERIFY=false` no `.env`.
- **Login com Google não funciona:** precisa de `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` no `.env`. O passo a passo está no `README.md`. Sem isso, use o login normal por e-mail e senha.
- **Mapa não carrega:** ele usa mapas do OpenStreetMap pela internet. Verifique a conexão.
- **Mexeu no CSS e nada mudou:** atualize com **Ctrl + F5** para limpar o cache do navegador.

## 7. Backups do banco

Em `database/backup/` estão cópias dos dois bancos do Gustavo, tiradas em 15/10/2026:

- `nexus_network_teste-AAAAMMDD.sql`: banco de teste, exatamente como estava (inclui o que foi alterado durante os testes).
- `nexus_network-AAAAMMDD.sql`: banco real (estrutura, categorias e cidades).

Para importar, use o phpMyAdmin (aba **Importar**) sem selecionar nenhum banco antes. Os arquivos já têm o `CREATE DATABASE` dentro. Se o banco já existir, ele é recriado.

Para tirar uma cópia do seu banco, rode no terminal:
```
C:\xampp\mysql\bin\mysqldump.exe -uroot --databases nexus_network_teste > backup.sql
```

## 8. Trabalhando em dupla sem perder alterações

Enviar o projeto inteiro por zip toda vez faz um sobrescrever o trabalho do outro. O ideal é usar **git**:

1. Crie um repositório **privado** no GitHub e adicione o João e o Gustavo.
2. Dentro da pasta do projeto: `git init`, `git add .`, `git commit -m "primeira versão"` e depois `git remote add origin <endereço>` e `git push -u origin main`.
3. O `.gitignore` já impede o envio do `.env`, das fotos de perfil e dos logs.
4. Antes de começar a mexer: `git pull`. Ao terminar um pedaço: `git add .`, `git commit -m "o que mudou"` e `git push`.
5. Mudanças no banco entram como arquivos em `database/migracoes/`, para o outro importar na própria máquina.

## 9. Se quiser ajuda de uma IA

Existe um texto pronto em `docs/PROMPT-CLAUDE-JOAO.md`, para colar no Claude Code (ou outro assistente) já com o contexto do projeto.

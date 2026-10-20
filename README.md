# Nexus Network

Plataforma que conecta clientes a prestadores de serviços da região: busca por serviço e distância, perfis com avaliações, chat entre cliente e prestador, login com Google e recuperação de senha por e-mail.

TCC do curso Técnico em Desenvolvimento de Sistemas, Etec Prof. Dr. Antônio Eufrásio de Toledo (Presidente Prudente/SP).
Equipe: Gustavo Henrique Antonio Castro, João Pedro de Souza Santos e Nicolas Sandoval Pereira. Orientador: Prof. Flávio Claudino de Araújo.

## Tecnologias

- PHP 8.2 (sem framework, organizado em MVC) e MySQL/MariaDB (XAMPP)
- HTML, CSS próprio (sem Bootstrap) e JavaScript puro
- APIs: Google OAuth 2.0, IBGE Localidades (estados e cidades) e Nominatim/OpenStreetMap (coordenadas)
- Mapas: Leaflet com agrupamento de marcadores (Leaflet.markercluster) e mapa base do OpenStreetMap
- PHPMailer (incluído em `app/lib`). Não é preciso Composer.

## Instalação (XAMPP)

1. Copie a pasta para `C:\xampp\htdocs\Nexus Network Company`.
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Crie o banco (veja abaixo) e copie `.env.example` para `.env`, ajustando `DB_NAME`.
4. Acesse <http://localhost/Nexus%20Network%20Company/>.

### Bancos de dados

Existem dois bancos, e o `DB_NAME` do `.env` escolhe qual o site usa:

| Banco | Para quê | Como criar |
|-------|----------|------------|
| `nexus_network` | Uso real: começa vazio, só com categorias e cidades | No phpMyAdmin, crie o banco `nexus_network` (utf8mb4_unicode_ci), selecione-o e importe `database/schema.sql` e depois `database/dados-base.sql` |
| `nexus_network_teste` | Testes e apresentação: 26 clientes, 41 prestadores, 1 admin, cerca de 145 avaliações, horários, conversas e contatos fictícios | Importe `database/teste.sql` (ele mesmo cria o banco do zero) |

**Contas do banco de teste** (senha **`Nexus@123`**): `cliente@teste.com`, `prestador@teste.com` e `admin@teste.com`. As demais contas seguem o formato `nome.sobrenomeN@teste.com`. Duas contas já vêm suspensas, para testar a moderação.

**Atualizando um banco criado antes do painel admin, dos horários e do mapa:** importe `database/migracoes/001-admin-mapa-horarios.sql` com o banco selecionado. Os dados existentes são mantidos.

**Atualizando um banco criado antes do selo de verificado, das denúncias e do registro de buscas:** importe `database/migracoes/002-verificado-denuncias-buscas.sql`. Para ver dados de exemplo dessas telas no banco de teste, importe também `database/dados-teste-extra.sql` (o `teste.sql` novo já os inclui).

### Administrador

O painel fica em `/admin`. Ele mostra a visão geral, permite suspender, reativar e excluir usuários, moderar avaliações e responder as mensagens do Contato por e-mail.
Para criar um administrador no banco que estiver no `.env`:

```
C:\xampp\php\php.exe database/criar-admin.php "Nome Completo" admin@seudominio.com "Senha@Forte1"
```
Com o banco de teste ativo, a tela de login mostra botões para entrar direto com essas contas.

Para recriar o banco de teste com dados limpos, reimporte `database/teste.sql`.
Para gerar dados novos, depois de mudar o `schema.sql` ou o gerador, rode:

```
C:\xampp\php\php.exe database/gerar-teste.php
```

### E-mail (recuperação de senha e contato)

Com `MAIL_DRIVER=log` (padrão), os e-mails não são enviados: o conteúdo, incluindo o código de recuperação, é gravado em `storage/logs/mail.log`.
Para enviar de verdade, use `MAIL_DRIVER=smtp` e preencha `MAIL_USER`/`MAIL_PASS`. No Gmail, use uma *senha de app*.

### Login com Google

1. Em <https://console.cloud.google.com/apis/credentials>, crie um "ID do cliente OAuth" do tipo *Aplicativo da Web*.
2. Em "URIs de redirecionamento autorizados", adicione `http://localhost/Nexus%20Network%20Company/auth/google/callback`.
3. Preencha `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` no `.env`.

Sem essas chaves, o botão do Google some e o restante funciona normalmente.

### Antivírus que inspeciona HTTPS (Avast, AVG, Kaspersky…)

Esses antivírus trocam o certificado das conexões HTTPS, e o PHP passa a recusar as chamadas ao Google, ao Nominatim e ao SMTP. Nesse caso, em **desenvolvimento**, use `SSL_VERIFY=false` no `.env`. Em produção, mantenha `true`.

## Estrutura

```
app/
  bootstrap.php        inicialização (env, autoload, sessão)
  routes.php           todas as rotas (URL → controller)
  config/config.php    configurações lidas do .env
  core/                Router, Database (PDO), Session, Csrf, Auth, View, Validator, Upload
  controllers/         Page, Auth, Register, GoogleAuth, PasswordReset, Search, Provider, Review, Chat, Profile, Admin
  models/              User, Category, Review, Conversation, PasswordReset, ProviderSearch, Schedule, Admin
  services/            Geocoder, Location, Mailer, GoogleOAuth, Http, UserForm
  views/               layouts, partials e páginas
database/              schema.sql, dados-base.sql, teste.sql, gerar-teste.php, criar-admin.php e migracoes/
public/                única pasta acessível pela web (index.php, assets, uploads)
storage/logs/          logs de erro e de e-mail
```

O `.htaccess` da raiz encaminha tudo para `public/`, então `app/`, `database/`, `storage/` e `.env` não ficam acessíveis pelo navegador.

## Segurança

- Todas as consultas usam prepared statements (PDO).
- Proteção CSRF em todos os formulários e na API do chat.
- Senhas e códigos de recuperação são guardados com `password_hash`. O código vale 10 minutos e aceita no máximo 5 tentativas.
- O login é bloqueado por 1 minuto após 5 erros seguidos.
- O OAuth usa o parâmetro `state`.
- A sessão usa cookies `HttpOnly` e `SameSite=Lax` e é regenerada no login.
- Uploads: o tipo é validado pelo conteúdo do arquivo, o nome é aleatório e scripts são bloqueados na pasta `uploads`.
- Os dados são guardados como foram digitados e escapados só na exibição.

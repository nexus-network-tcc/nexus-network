# Prompt para colar no Claude Code (ou outro assistente)

Abra o terminal na pasta do projeto (`C:\xampp\htdocs\Nexus Network Company`), inicie o Claude Code e cole **tudo** o que está dentro do bloco abaixo.

```text
Você vai me ajudar a rodar e continuar o desenvolvimento do "Nexus Network", um TCC (plataforma que conecta clientes a prestadores de serviço da região). O projeto está nesta pasta. Fale comigo em português.

PRIMEIRO, LEIA para entender o projeto: README.md e docs/ORIENTACOES-JOAO.md. Depois olhe app/routes.php e a estrutura de app/.

STACK: PHP 8.2 sem framework e sem Composer (MVC simples em app/), MariaDB/MySQL via XAMPP, HTML + CSS próprio (public/assets/css/app.css, sem Bootstrap) e JavaScript puro. Cores: preto e verde #20cd8d (não mude). O site roda em http://localhost/Nexus%20Network%20Company/ pelo Apache do XAMPP.

TAREFA 1: deixar o projeto funcionando nesta máquina.
1. Verifique se o XAMPP está em C:\xampp e se Apache e MySQL estão rodando. Se não estiverem, me diga como iniciá-los pelo XAMPP Control Panel (ou inicie você, se conseguir).
2. Se o arquivo .env não existir, crie-o a partir do .env.example. Use DB_NAME=nexus_network_teste, DB_USER=root, MAIL_DRIVER=log e deixe APP_URL vazio. Não invente chaves do Google nem senhas de e-mail.
3. Importe o banco de teste: database/teste.sql (ele já cria o banco nexus_network_teste do zero), usando C:\xampp\mysql\bin\mysql.exe -uroot --default-character-set=utf8mb4 < database/teste.sql. Se já existir um banco com esse nome, AVISE antes de recriar, porque isso apaga os dados dele.
4. Teste se http://localhost/Nexus%20Network%20Company/ responde 200 e se o login funciona com cliente@teste.com / Nexus@123. A tela /admin deve abrir com admin@teste.com / Nexus@123.
5. Se algo falhar (404, porta 80 ocupada, erro de banco, erro de certificado SSL por causa de antivírus), diagnostique pela causa e me explique, sem apagar nem desfazer nada às cegas. Em desenvolvimento, SSL_VERIFY=false no .env resolve o erro de certificado causado por antivírus.

REGRAS PARA QUALQUER ALTERAÇÃO DE CÓDIGO:
- Siga o estilo do código existente (nomes, comentários, organização). Uma rota nova vai em app/routes.php, a lógica no controller, as consultas SQL em app/models/ e o HTML em app/views/.
- Escape tudo o que vem do usuário nas telas com e(). Todo formulário POST usa csrf_field(). Consultas SQL sempre com parâmetros (Database::all/first/value/insert/update), nunca concatenando texto do usuário.
- Mudança na estrutura do banco: atualize database/schema.sql E crie uma migração nova em database/migracoes/ (próximo número, ex.: 003-...sql). Se precisar de dados de exemplo no banco de teste, use database/dados-teste-extra.sql e regenere o teste.sql com: C:\xampp\php\php.exe database/gerar-teste.php
- Não apague nem recrie bancos, não use comandos destrutivos e não rode git reset/force sem eu pedir. Antes de apagar qualquer dado, me pergunte.
- Nunca coloque senhas, chaves de API nem o conteúdo do .env em arquivos versionados ou em mensagens de commit. O .env não deve ser enviado ao GitHub.
- Depois de cada alteração, rode "C:\xampp\php\php.exe -l" nos arquivos PHP alterados e teste a página afetada. Me diga o que foi testado e o que não deu para testar.
- Se meu pedido for ambíguo, faça no máximo uma pergunta curta antes de começar.

Quando terminar a TAREFA 1, me mostre um resumo curto do que está rodando (URLs e contas de teste) e pergunte o que eu quero desenvolver.
```

## Dicas

- Se for pedir uma funcionalidade nova, escreva o que ela deve fazer e onde deve aparecer. Exemplo: "Na página do prestador, quero um botão de favoritar que salva no banco e uma página /favoritos com a lista."
- Ao final de cada tarefa, peça: "Atualize o README e o docs/ORIENTACOES-JOAO.md se algo mudou."
- Antes de mexer em algo grande, peça um commit ou um backup do banco: `C:\xampp\mysql\bin\mysqldump.exe -uroot --databases nexus_network_teste > backup.sql`

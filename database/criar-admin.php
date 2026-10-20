<?php

/**
 * Cria (ou promove) um administrador no banco configurado no .env.
 *
 * Uso: php database/criar-admin.php "Nome Completo" email@dominio.com "Senha@Forte1"
 */

if (PHP_SAPI !== 'cli') {
    exit('Execute pela linha de comando.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

[, $nome, $email, $senha] = $argv + [null, null, null, null];
if (!$nome || !$email || !$senha) {
    fwrite(STDERR, "Uso: php database/criar-admin.php \"Nome Completo\" email@dominio.com \"Senha@Forte1\"\n");
    exit(1);
}

$email = mb_strtolower(trim($email));
if (!Validator::email($email)) {
    fwrite(STDERR, "E-mail inválido.\n");
    exit(1);
}
if ($erro = Validator::passwordError($senha, $senha)) {
    fwrite(STDERR, $erro . "\n");
    exit(1);
}

$existente = User::findByEmail($email);
if ($existente) {
    if ($existente['tipo'] !== 'admin') {
        fwrite(STDERR, "Já existe um {$existente['tipo']} com este e-mail. Use outro e-mail para o administrador.\n");
        exit(1);
    }
    User::setPassword((int) $existente['id'], $senha);
    echo "Administrador já existia; senha atualizada.\n";
    exit(0);
}

Database::insert('usuarios', [
    'tipo'  => 'admin',
    'nome'  => trim($nome),
    'email' => $email,
    'senha' => password_hash($senha, PASSWORD_DEFAULT),
]);
echo "Administrador {$email} criado no banco " . Config::get('db.name') . ".\n";

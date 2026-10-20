<?php

return [
    'app' => [
        'name'  => Env::get('APP_NAME', 'Nexus Network'),
        'debug' => Env::bool('APP_DEBUG'),
        'url'   => rtrim((string) Env::get('APP_URL', ''), '/'),
    ],

    'db' => [
        'host' => Env::get('DB_HOST', 'localhost'),
        'port' => Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'nexus_network'),
        'user' => Env::get('DB_USER', 'root'),
        'pass' => Env::get('DB_PASS', ''),
        // Bancos na nuvem (Aiven, TiDB...) exigem conexão criptografada.
        // DB_SSL_CA: caminho do certificado da CA; sem ele usa o pacote público em app/lib/cacert.pem.
        'ssl'    => Env::bool('DB_SSL'),
        'ssl_ca' => Env::get('DB_SSL_CA', APP_PATH . '/lib/cacert.pem'),
        // O Aiven assina com uma CA propria (nao esta no pacote publico): so valide o servidor
        // com DB_SSL_VERIFY=true junto de DB_SSL_CA apontando para o certificado dele.
        'ssl_verify' => Env::bool('DB_SSL_VERIFY'),
    ],

    'google' => [
        'client_id'     => Env::get('GOOGLE_CLIENT_ID'),
        'client_secret' => Env::get('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'  => Env::get('GOOGLE_REDIRECT_URI'),
    ],

    'mail' => [
        'driver'    => Env::get('MAIL_DRIVER', 'log'),
        'host'      => Env::get('MAIL_HOST', 'smtp.gmail.com'),
        'port'      => (int) Env::get('MAIL_PORT', '587'),
        'user'      => Env::get('MAIL_USER'),
        'pass'      => Env::get('MAIL_PASS'),
        'from'      => Env::get('MAIL_FROM', Env::get('MAIL_USER')),
        'from_name' => Env::get('MAIL_FROM_NAME', 'Nexus Network'),
    ],

    'contact_email' => Env::get('CONTACT_EMAIL'),
    'support_email' => 'nexustcc5@gmail.com',

    'ssl' => [
        'verify' => Env::bool('SSL_VERIFY', true),
        'ca'     => APP_PATH . '/lib/cacert.pem',
    ],

    'upload' => [
        'dir'      => ROOT_PATH . '/public/uploads/fotos',
        'url'      => 'uploads/fotos',
        'max_size' => 5 * 1024 * 1024,
    ],

    'search' => [
        'per_page' => 12,
        'radii'    => [5, 10, 25, 50, 100],
    ],
];

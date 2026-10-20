<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

require APP_PATH . '/core/Env.php';
Env::load(ROOT_PATH . '/.env');

spl_autoload_register(static function (string $class): void {
    foreach (['core', 'models', 'controllers', 'services'] as $dir) {
        $file = APP_PATH . "/{$dir}/{$class}.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

Config::load(require APP_PATH . '/config/config.php');

require APP_PATH . '/helpers.php';

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/logs/php-error.log');

if (PHP_SAPI !== 'cli') {
    Session::start();
}

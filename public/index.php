<?php

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
require APP_PATH . '/routes.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
} catch (PDOException $e) {
    error_log((string) $e);
    abort(500, Config::get('app.debug')
        ? 'Erro no banco de dados: ' . $e->getMessage()
        : 'Estamos com instabilidade no momento. Tente novamente em instantes.');
} catch (Throwable $e) {
    error_log((string) $e);
    abort(500, Config::get('app.debug') ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : null);
}

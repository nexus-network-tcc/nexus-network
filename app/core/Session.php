<?php

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('nexus_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();

        // Mensagens flash e dados antigos de formulário vivem por uma requisição
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
        $_SESSION['_errors_now'] = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_flash'], $_SESSION['_old'], $_SESSION['_errors']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Mensagem exibida na próxima página (tipos: success, error, info). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function flashes(): array
    {
        $messages = $_SESSION['_flash_now'] ?? [];
        $_SESSION['_flash_now'] = [];
        return $messages;
    }

    public static function withInput(array $input, array $errors = []): void
    {
        unset($input['senha'], $input['senha_confirmacao'], $input['senha_atual'], $input['_token']);
        $_SESSION['_old'] = $input;
        $_SESSION['_errors'] = $errors;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_now'][$key] ?? $default;
    }

    public static function errors(): array
    {
        return $_SESSION['_errors_now'] ?? [];
    }
}

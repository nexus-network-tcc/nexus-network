<?php

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** Usuário logado, sempre lido do banco (nome/foto atualizados). */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            self::$user = $id ? User::find((int) $id) : null;
            // Conta suspensa por um admin encerra a sessão na próxima requisição
            if (self::$user !== null && self::$user['suspenso_em'] !== null) {
                self::$user = null;
                Session::flash('error', 'Sua conta foi suspensa. Fale com o suporte.');
            }
            if ($id && self::$user === null) {
                Session::forget('user_id');
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function isCliente(): bool
    {
        return (self::user()['tipo'] ?? null) === 'cliente';
    }

    public static function isPrestador(): bool
    {
        return (self::user()['tipo'] ?? null) === 'prestador';
    }

    public static function isAdmin(): bool
    {
        return (self::user()['tipo'] ?? null) === 'admin';
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
    }
}

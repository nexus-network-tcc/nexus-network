<?php

final class PasswordReset
{
    public const MAX_ATTEMPTS = 5;
    public const MINUTES_VALID = 10;
    public const RESEND_SECONDS = 60;

    /** Gera um novo código de 6 dígitos (invalidando os anteriores) e devolve o código em texto puro. */
    public static function create(int $userId): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Database::transaction(static function () use ($userId, $code) {
            Database::query('UPDATE redefinicoes_senha SET usado = 1 WHERE usuario_id = ? AND usado = 0', [$userId]);
            Database::insert('redefinicoes_senha', [
                'usuario_id'  => $userId,
                'codigo_hash' => password_hash($code, PASSWORD_DEFAULT),
                'expira_em'   => date('Y-m-d H:i:s', time() + self::MINUTES_VALID * 60),
            ]);
        });

        return $code;
    }

    public static function recentlySent(int $userId): bool
    {
        return (bool) Database::value(
            'SELECT 1 FROM redefinicoes_senha WHERE usuario_id = ? AND criado_em > NOW() - INTERVAL ? SECOND',
            [$userId, self::RESEND_SECONDS]
        );
    }

    /**
     * Confere o código. Retorna 'ok', 'invalid', 'expired' ou 'blocked'.
     */
    public static function verify(int $userId, string $code): string
    {
        $reset = Database::first(
            'SELECT * FROM redefinicoes_senha WHERE usuario_id = ? AND usado = 0 ORDER BY id DESC LIMIT 1',
            [$userId]
        );

        if (!$reset || strtotime($reset['expira_em']) < time()) {
            return 'expired';
        }
        if ((int) $reset['tentativas'] >= self::MAX_ATTEMPTS) {
            return 'blocked';
        }
        if (!password_verify($code, $reset['codigo_hash'])) {
            Database::query('UPDATE redefinicoes_senha SET tentativas = tentativas + 1 WHERE id = ?', [$reset['id']]);
            return (int) $reset['tentativas'] + 1 >= self::MAX_ATTEMPTS ? 'blocked' : 'invalid';
        }

        Database::query('UPDATE redefinicoes_senha SET usado = 1 WHERE id = ?', [$reset['id']]);
        return 'ok';
    }
}

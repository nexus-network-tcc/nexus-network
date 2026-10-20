<?php

/**
 * Limite de tentativas de login que falharam, guardado no banco (não dá para burlar apagando cookies).
 * Bloqueia por e-mail (5 falhas) e por IP (20 falhas) dentro de uma janela de 15 minutos.
 */
final class LoginThrottle
{
    private const WINDOW = 900;
    private const MAX_PER_EMAIL = 5;
    private const MAX_PER_IP = 20;

    /** Segundos restantes de bloqueio para este e-mail/IP (0 = liberado). */
    public static function blockedFor(string $email): int
    {
        try {
            return max(
                self::remaining('email = ?', $email, self::MAX_PER_EMAIL),
                self::remaining('ip = ?', self::ip(), self::MAX_PER_IP)
            );
        } catch (PDOException) {
            return 0; // banco ainda sem a migração 004
        }
    }

    public static function fail(string $email): void
    {
        try {
            Database::insert('tentativas_login', ['email' => $email !== '' ? mb_substr($email, 0, 190) : null, 'ip' => self::ip()]);
            if (random_int(1, 50) === 1) { // limpeza ocasional
                Database::query('DELETE FROM tentativas_login WHERE criado_em < NOW() - INTERVAL 1 DAY');
            }
        } catch (PDOException) {
            // sem a tabela não há limite, mas o login continua funcionando
        }
    }

    /** Login correto: zera as falhas do e-mail. */
    public static function clear(string $email): void
    {
        try {
            Database::query('DELETE FROM tentativas_login WHERE email = ?', [$email]);
        } catch (PDOException) {
        }
    }

    public static function ip(): string
    {
        return mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    private static function remaining(string $where, string $value, int $max): int
    {
        $ages = Database::all(
            "SELECT TIMESTAMPDIFF(SECOND, criado_em, NOW()) AS idade FROM tentativas_login
             WHERE {$where} AND criado_em > NOW() - INTERVAL " . self::WINDOW . ' SECOND
             ORDER BY id DESC LIMIT ' . $max,
            [$value]
        );
        if (count($ages) < $max) {
            return 0;
        }
        // o bloqueio termina quando a falha de ordem "max" (a mais antiga das que contam) sair da janela
        return max(1, self::WINDOW - (int) end($ages)['idade']);
    }
}

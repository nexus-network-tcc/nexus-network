<?php

/**
 * Avisos do sino do cabeçalho (novo pedido, resposta, avaliação recebida…).
 */
final class Notification
{
    public const PER_PAGE = 20;

    public const ICONS = [
        'agendamento' => 'fa-calendar-check',
        'avaliacao'   => 'fa-star',
    ];

    public static function create(int $userId, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        try {
            Database::insert('notificacoes', [
                'usuario_id' => $userId,
                'tipo'       => $type,
                'titulo'     => mb_strimwidth($title, 0, 120, '…'),
                'mensagem'   => $message !== null ? mb_strimwidth($message, 0, 255, '…') : null,
                'link'       => $link,
            ]);
        } catch (PDOException) {
            // um aviso que falha não pode derrubar a ação principal (banco sem a migração 003)
        }
    }

    /** Aviso no sino e, se houver e-mail, também por e-mail (falha de envio nunca interrompe a ação). */
    public static function send(array $to, string $type, string $title, string $message, string $link): void
    {
        self::create((int) $to['id'], $type, $title, $message, $link);
        if (!empty($to['email'])) {
            Mailer::send(
                $to['email'],
                $title . ' - Nexus Network',
                '<p>Olá, ' . e(first_name($to['nome'])) . '!</p><p>' . e($message) . '</p>'
                . '<p><a href="' . e(absolute_url($link)) . '">Abrir na Nexus Network</a></p>'
            );
        }
    }

    public static function unreadCount(int $userId): int
    {
        try {
            return (int) Database::value('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida_em IS NULL', [$userId]);
        } catch (PDOException) {
            return 0;
        }
    }

    public static function recent(int $userId, int $limit = self::PER_PAGE): array
    {
        return Database::all(
            'SELECT id, tipo, titulo, mensagem, link, lida_em, criado_em FROM notificacoes
             WHERE usuario_id = ? ORDER BY id DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM notificacoes WHERE id = ?', [$id]);
    }

    public static function markRead(int $id): void
    {
        Database::query('UPDATE notificacoes SET lida_em = NOW() WHERE id = ? AND lida_em IS NULL', [$id]);
    }

    public static function markAllRead(int $userId): void
    {
        Database::query('UPDATE notificacoes SET lida_em = NOW() WHERE usuario_id = ? AND lida_em IS NULL', [$userId]);
    }
}

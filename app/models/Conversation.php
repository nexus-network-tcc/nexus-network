<?php

final class Conversation
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM conversas WHERE id = ?', [$id]);
    }

    public static function findOrCreate(int $clientId, int $providerId): int
    {
        $id = Database::value(
            'SELECT id FROM conversas WHERE cliente_id = ? AND prestador_id = ?',
            [$clientId, $providerId]
        );
        if ($id) {
            return (int) $id;
        }

        Database::query(
            'INSERT IGNORE INTO conversas (cliente_id, prestador_id) VALUES (?, ?)',
            [$clientId, $providerId]
        );
        return (int) Database::value(
            'SELECT id FROM conversas WHERE cliente_id = ? AND prestador_id = ?',
            [$clientId, $providerId]
        );
    }

    public static function belongsTo(array $conversation, int $userId): bool
    {
        return (int) $conversation['cliente_id'] === $userId || (int) $conversation['prestador_id'] === $userId;
    }

    /**
     * Conversas do usuário com o outro participante, a última mensagem e as não lidas.
     */
    public static function listFor(array $user): array
    {
        $mine = $user['tipo'] === 'cliente' ? 'cliente_id' : 'prestador_id';
        $other = $user['tipo'] === 'cliente' ? 'prestador_id' : 'cliente_id';

        return Database::all(
            "SELECT c.id, c.criado_em,
                    o.id AS outro_id, o.nome AS outro_nome, o.foto AS outro_foto, p.profissao AS outro_profissao,
                    m.conteudo AS ultima_mensagem, m.enviada_em AS ultima_data, m.remetente_id AS ultimo_remetente,
                    (SELECT COUNT(*) FROM mensagens x
                      WHERE x.conversa_id = c.id AND x.remetente_id <> :uid1 AND x.lida = 0) AS nao_lidas
             FROM conversas c
             JOIN usuarios o ON o.id = c.{$other}
             LEFT JOIN prestador_perfis p ON p.usuario_id = o.id
             LEFT JOIN mensagens m ON m.id = (SELECT MAX(id) FROM mensagens WHERE conversa_id = c.id)
             WHERE c.{$mine} = :uid2
             ORDER BY COALESCE(m.enviada_em, c.criado_em) DESC",
            ['uid1' => $user['id'], 'uid2' => $user['id']]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM mensagens m
             JOIN conversas c ON c.id = m.conversa_id
             WHERE (c.cliente_id = ? OR c.prestador_id = ?) AND m.remetente_id <> ? AND m.lida = 0',
            [$userId, $userId, $userId]
        );
    }

    public static function messagesAfter(int $conversationId, int $afterId, int $limit = 200): array
    {
        return Database::all(
            'SELECT id, remetente_id, conteudo, lida, enviada_em
             FROM mensagens WHERE conversa_id = ? AND id > ?
             ORDER BY id ASC LIMIT ' . (int) $limit,
            [$conversationId, $afterId]
        );
    }

    public static function addMessage(int $conversationId, int $senderId, string $content): int
    {
        return Database::insert('mensagens', [
            'conversa_id'  => $conversationId,
            'remetente_id' => $senderId,
            'conteudo'     => $content,
        ]);
    }

    public static function markRead(int $conversationId, int $readerId): void
    {
        Database::query(
            'UPDATE mensagens SET lida = 1 WHERE conversa_id = ? AND remetente_id <> ? AND lida = 0',
            [$conversationId, $readerId]
        );
    }
}

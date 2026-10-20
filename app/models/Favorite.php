<?php

/**
 * Prestadores favoritados pelos clientes.
 */
final class Favorite
{
    /** @var array<int, array<int, true>> cache por requisição: cliente => [prestador => true] */
    private static array $cache = [];

    public static function has(int $clientId, int $providerId): bool
    {
        return isset(self::ids($clientId)[$providerId]);
    }

    /** @return array<int, true> */
    public static function ids(int $clientId): array
    {
        if (!isset(self::$cache[$clientId])) {
            try {
                $rows = Database::all('SELECT prestador_id FROM favoritos WHERE cliente_id = ?', [$clientId]);
            } catch (PDOException) {
                $rows = []; // banco ainda sem a migração 003
            }
            self::$cache[$clientId] = array_fill_keys(array_map('intval', array_column($rows, 'prestador_id')), true);
        }
        return self::$cache[$clientId];
    }

    /** Alterna o favorito e devolve o novo estado. */
    public static function toggle(int $clientId, int $providerId): bool
    {
        unset(self::$cache[$clientId]);
        $removed = Database::query(
            'DELETE FROM favoritos WHERE cliente_id = ? AND prestador_id = ?',
            [$clientId, $providerId]
        )->rowCount();
        if ($removed) {
            return false;
        }
        Database::query('INSERT IGNORE INTO favoritos (cliente_id, prestador_id) VALUES (?, ?)', [$clientId, $providerId]);
        return true;
    }

    /** Favoritos do cliente no formato dos cards de prestador. */
    public static function providersFor(int $clientId): array
    {
        return Database::all(
            'SELECT u.id, u.nome, u.foto, u.cidade, u.estado, p.profissao, p.descricao, p.preco_min, p.preco_max, p.verificado_em,
                    COALESCE(r.media, 0) AS media, COALESCE(r.total, 0) AS total_avaliacoes
             FROM favoritos f
             JOIN usuarios u ON u.id = f.prestador_id
             JOIN prestador_perfis p ON p.usuario_id = u.id
             LEFT JOIN (SELECT prestador_id, AVG(nota) AS media, COUNT(*) AS total
                        FROM avaliacoes GROUP BY prestador_id) r ON r.prestador_id = u.id
             WHERE f.cliente_id = ? AND u.suspenso_em IS NULL
             ORDER BY f.criado_em DESC',
            [$clientId]
        );
    }
}

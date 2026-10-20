<?php

final class Category
{
    public static function all(): array
    {
        return Database::all('SELECT * FROM categorias ORDER BY id');
    }

    public static function allWithCounts(): array
    {
        return Database::all(
            'SELECT c.*, COUNT(pc.prestador_id) AS total
             FROM categorias c
             LEFT JOIN prestador_categorias pc ON pc.categoria_id = c.id
                 AND pc.prestador_id IN (SELECT id FROM usuarios WHERE suspenso_em IS NULL)
             GROUP BY c.id
             ORDER BY c.id'
        );
    }

    public static function idsForProvider(int $providerId): array
    {
        return array_map('intval', array_column(
            Database::all('SELECT categoria_id FROM prestador_categorias WHERE prestador_id = ?', [$providerId]),
            'categoria_id'
        ));
    }

    public static function forProvider(int $providerId): array
    {
        return Database::all(
            'SELECT c.* FROM categorias c
             JOIN prestador_categorias pc ON pc.categoria_id = c.id
             WHERE pc.prestador_id = ? ORDER BY c.nome',
            [$providerId]
        );
    }

    /** Mantém só ids que existem de fato. */
    public static function validIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return array_map('intval', array_column(
            Database::all("SELECT id FROM categorias WHERE id IN ({$placeholders})", $ids),
            'id'
        ));
    }

    public static function syncForProvider(int $providerId, array $ids): void
    {
        Database::query('DELETE FROM prestador_categorias WHERE prestador_id = ?', [$providerId]);
        foreach (self::validIds($ids) as $categoryId) {
            Database::insert('prestador_categorias', ['prestador_id' => $providerId, 'categoria_id' => $categoryId]);
        }
    }
}

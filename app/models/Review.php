<?php

final class Review
{
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM avaliacoes WHERE id = ?', [$id]);
    }

    public static function forProvider(int $providerId): array
    {
        return Database::all(
            'SELECT a.*, u.nome AS cliente_nome, u.foto AS cliente_foto, u.cidade AS cliente_cidade
             FROM avaliacoes a
             JOIN usuarios u ON u.id = a.cliente_id
             WHERE a.prestador_id = ?
             ORDER BY a.criado_em DESC',
            [$providerId]
        );
    }

    public static function byClient(int $clientId): array
    {
        return Database::all(
            'SELECT a.*, u.nome AS prestador_nome, u.foto AS prestador_foto, p.profissao
             FROM avaliacoes a
             JOIN usuarios u ON u.id = a.prestador_id
             JOIN prestador_perfis p ON p.usuario_id = u.id
             WHERE a.cliente_id = ?
             ORDER BY a.criado_em DESC',
            [$clientId]
        );
    }

    /** Média, total e distribuição das notas (5 → 1). */
    public static function summary(int $providerId): array
    {
        $rows = Database::all(
            'SELECT nota, COUNT(*) AS total FROM avaliacoes WHERE prestador_id = ? GROUP BY nota',
            [$providerId]
        );

        $distribution = array_fill_keys([5, 4, 3, 2, 1], 0);
        $total = 0;
        $sum = 0;
        foreach ($rows as $row) {
            $distribution[(int) $row['nota']] = (int) $row['total'];
            $total += (int) $row['total'];
            $sum += (int) $row['nota'] * (int) $row['total'];
        }

        return [
            'media'        => $total ? round($sum / $total, 1) : 0.0,
            'total'        => $total,
            'distribuicao' => $distribution,
        ];
    }

    public static function exists(int $clientId, int $providerId): bool
    {
        return (bool) Database::value(
            'SELECT 1 FROM avaliacoes WHERE cliente_id = ? AND prestador_id = ?',
            [$clientId, $providerId]
        );
    }

    public static function create(int $clientId, int $providerId, int $rating, string $comment): void
    {
        Database::insert('avaliacoes', [
            'cliente_id'   => $clientId,
            'prestador_id' => $providerId,
            'nota'         => $rating,
            'comentario'   => $comment,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM avaliacoes WHERE id = ?', [$id]);
    }

    public static function latest(int $limit = 3): array
    {
        return Database::all(
            'SELECT a.nota, a.comentario, c.nome AS cliente_nome, c.foto AS cliente_foto, c.cidade AS cliente_cidade,
                    p.profissao
             FROM avaliacoes a
             JOIN usuarios c ON c.id = a.cliente_id
             JOIN prestador_perfis p ON p.usuario_id = a.prestador_id
             WHERE a.nota >= 4 AND CHAR_LENGTH(a.comentario) >= 30
             ORDER BY a.criado_em DESC
             LIMIT ' . (int) $limit
        );
    }
}

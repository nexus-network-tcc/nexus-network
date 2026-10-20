<?php

/**
 * Registro das buscas dos visitantes (só categorias e termo, nada que identifique a pessoa).
 */
final class SearchLog
{
    /** @param array{q?: string, categorias?: int[]} $filters */
    public static function record(array $filters, int $results): void
    {
        $term = mb_strtolower(trim($filters['q'] ?? ''));
        $categories = $filters['categorias'] ?? [];
        if ($term === '' && !$categories) {
            return;
        }

        try {
            if ($categories) {
                foreach ($categories as $categoryId) {
                    Database::insert('buscas', [
                        'categoria_id' => $categoryId,
                        'termo'        => $term !== '' ? mb_substr($term, 0, 100) : null,
                        'resultados'   => $results,
                    ]);
                }
            } else {
                Database::insert('buscas', ['termo' => mb_substr($term, 0, 100), 'resultados' => $results]);
            }
        } catch (PDOException) {
            // O registro é só estatística: nunca deve quebrar a busca (ex.: banco ainda sem a migração 002).
        }
    }

    /** Categorias mais buscadas nos últimos N dias. */
    public static function topCategories(int $days = 30, int $limit = 6): array
    {
        return Database::all(
            'SELECT c.nome, c.icone, COUNT(*) AS total
             FROM buscas b JOIN categorias c ON c.id = b.categoria_id
             WHERE b.criado_em >= NOW() - INTERVAL ' . (int) $days . ' DAY
             GROUP BY c.id ORDER BY total DESC, c.nome LIMIT ' . (int) $limit
        );
    }

    /** Termos digitados mais buscados nos últimos N dias. */
    public static function topTerms(int $days = 30, int $limit = 6): array
    {
        return Database::all(
            'SELECT termo, COUNT(*) AS total, ROUND(AVG(resultados)) AS media_resultados
             FROM buscas
             WHERE termo IS NOT NULL AND criado_em >= NOW() - INTERVAL ' . (int) $days . ' DAY
             GROUP BY termo ORDER BY total DESC, termo LIMIT ' . (int) $limit
        );
    }

    public static function total(int $days = 30): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM buscas WHERE criado_em >= NOW() - INTERVAL ' . (int) $days . ' DAY');
    }
}

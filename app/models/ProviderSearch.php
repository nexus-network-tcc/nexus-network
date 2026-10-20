<?php

/**
 * Busca de prestadores com filtros, distância (fórmula de Haversine) e paginação.
 */
final class ProviderSearch
{
    public const MAP_LIMIT = 300;

    public const AVAILABILITY = [
        ''      => 'Qualquer horário',
        'hoje'  => 'Atende hoje',
        'agora' => 'Atendendo agora',
    ];

    /** Nota mínima (média das avaliações). */
    public const RATINGS = [3.0, 4.0, 4.5];

    /** Teto para o preço inicial do profissional (valores a combinar ficam de fora). */
    public const PRICES = [100, 200, 300, 500, 1000];

    public const ORDERS = [
        'distancia' => 'Mais próximos',
        'avaliacao' => 'Melhor avaliados',
        'preco'     => 'Menor preço',
        'recentes'  => 'Mais recentes',
    ];

    /**
     * @param array{q?: string, categorias?: int[], raio?: int, ordem?: string, pagina?: int} $filters
     * @param array{lat: float, lon: float}|null $origin
     */
    public static function run(array $filters, ?array $origin): array
    {
        $params = [];
        $where = ["u.tipo = 'prestador'", 'u.suspenso_em IS NULL'];

        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            $where[] = '(u.nome LIKE :q1 OR p.profissao LIKE :q2 OR p.descricao LIKE :q3 OR u.cidade LIKE :q4)';
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        $categories = $filters['categorias'] ?? [];
        if ($categories) {
            $keys = [];
            foreach (array_values($categories) as $i => $id) {
                $keys[] = ':cat' . $i;
                $params['cat' . $i] = $id;
            }
            $where[] = 'EXISTS (SELECT 1 FROM prestador_categorias pc
                                WHERE pc.prestador_id = u.id AND pc.categoria_id IN (' . implode(',', $keys) . '))';
        }

        if ($origin) {
            $distance = '(6371 * ACOS(LEAST(1, GREATEST(-1,
                            COS(RADIANS(:lat1)) * COS(RADIANS(u.latitude)) * COS(RADIANS(u.longitude) - RADIANS(:lon1))
                          + SIN(RADIANS(:lat2)) * SIN(RADIANS(u.latitude))))))';
            $params += ['lat1' => $origin['lat'], 'lon1' => $origin['lon'], 'lat2' => $origin['lat']];
        } else {
            $distance = 'NULL';
        }

        $radius = (int) ($filters['raio'] ?? 0);
        if ($origin && $radius > 0) {
            $where[] = 'u.latitude IS NOT NULL';
        }

        // Disponibilidade calculada no horário de Brasília (definido no bootstrap)
        $now = new DateTimeImmutable();
        $params += ['dia_agora' => (int) $now->format('w'), 'hora_agora1' => $now->format('H:i:s'), 'hora_agora2' => $now->format('H:i:s')];
        $availability = $filters['disponibilidade'] ?? '';
        if ($availability === 'hoje') {
            $where[] = 'EXISTS (SELECT 1 FROM prestador_horarios h WHERE h.prestador_id = u.id AND h.dia_semana = :dia_hoje)';
            $params['dia_hoje'] = (int) $now->format('w');
        } elseif ($availability === 'agora') {
            $where[] = 'EXISTS (SELECT 1 FROM prestador_horarios h WHERE h.prestador_id = u.id AND h.dia_semana = :dia_filtro
                                AND :hora_filtro1 >= h.inicio AND :hora_filtro2 < h.fim)';
            $params += ['dia_filtro' => (int) $now->format('w'), 'hora_filtro1' => $now->format('H:i:s'), 'hora_filtro2' => $now->format('H:i:s')];
        }

        $minRating = (float) ($filters['nota_min'] ?? 0);
        if ($minRating > 0) {
            $where[] = 'COALESCE(r.media, 0) >= :nota_min';
            $params['nota_min'] = $minRating;
        }

        $maxPrice = (int) ($filters['preco_max'] ?? 0);
        if ($maxPrice > 0) {
            $where[] = 'p.preco_min IS NOT NULL AND p.preco_min <= :preco_teto';
            $params['preco_teto'] = $maxPrice;
        }

        if (!empty($filters['verificados'])) {
            $where[] = 'p.verificado_em IS NOT NULL';
        }

        $sql = "SELECT u.id, u.nome, u.foto, u.cidade, u.estado, u.criado_em, u.latitude, u.longitude,
                       p.profissao, p.descricao, p.preco_min, p.preco_max, p.verificado_em,
                       COALESCE(r.media, 0) AS media, COALESCE(r.total, 0) AS total_avaliacoes,
                       EXISTS (SELECT 1 FROM prestador_horarios ha WHERE ha.prestador_id = u.id AND ha.dia_semana = :dia_agora
                               AND :hora_agora1 >= ha.inicio AND :hora_agora2 < ha.fim) AS atende_agora,
                       {$distance} AS distancia
                FROM usuarios u
                JOIN prestador_perfis p ON p.usuario_id = u.id
                LEFT JOIN (SELECT prestador_id, AVG(nota) AS media, COUNT(*) AS total
                           FROM avaliacoes GROUP BY prestador_id) r ON r.prestador_id = u.id
                WHERE " . implode(' AND ', $where);

        if ($origin && $radius > 0) {
            $sql .= ' HAVING distancia <= :raio';
            $params['raio'] = $radius;
        }

        $total = (int) Database::value("SELECT COUNT(*) FROM ({$sql}) AS t", $params);

        $order = $filters['ordem'] ?? 'distancia';
        $orderSql = match ($order) {
            'avaliacao' => 'media DESC, total_avaliacoes DESC, u.nome',
            'preco'     => 'p.preco_min IS NULL, p.preco_min ASC, u.nome',
            'recentes'  => 'u.criado_em DESC',
            default     => $origin ? 'distancia IS NULL, distancia ASC, media DESC' : 'media DESC, total_avaliacoes DESC',
        };

        // Modo mapa: todos os resultados (até um limite), sem paginação
        if (!empty($filters['mapa'])) {
            $results = Database::all("{$sql} ORDER BY {$orderSql} LIMIT " . self::MAP_LIMIT, $params);
            return ['results' => $results, 'total' => $total, 'page' => 1, 'pages' => 1];
        }

        $perPage = (int) Config::get('search.per_page');
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($filters['pagina'] ?? 1)), $pages);
        $offset = ($page - 1) * $perPage;

        $results = Database::all("{$sql} ORDER BY {$orderSql} LIMIT {$perPage} OFFSET {$offset}", $params);

        return compact('results', 'total', 'page', 'pages');
    }

    /** Prestadores em destaque para a página inicial. */
    public static function featured(int $limit = 4): array
    {
        return Database::all(
            'SELECT u.id, u.nome, u.foto, u.cidade, u.estado, p.profissao, p.descricao, p.preco_min, p.preco_max, p.verificado_em,
                    AVG(a.nota) AS media, COUNT(a.id) AS total_avaliacoes
             FROM usuarios u
             JOIN prestador_perfis p ON p.usuario_id = u.id
             JOIN avaliacoes a ON a.prestador_id = u.id
             WHERE u.suspenso_em IS NULL
             GROUP BY u.id
             ORDER BY media DESC, total_avaliacoes DESC
             LIMIT ' . (int) $limit
        );
    }

    /** Distância em km entre dois pontos (para a página do prestador). */
    public static function distance(array $a, array $b): float
    {
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLon = deg2rad($b['lon'] - $a['lon']);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLon / 2) ** 2;
        return 6371 * 2 * asin(min(1, sqrt($h)));
    }
}

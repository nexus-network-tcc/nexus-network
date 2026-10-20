<?php

/**
 * Consultas do painel de administração.
 */
final class Admin
{
    public const PER_PAGE = 20;

    public static function stats(): array
    {
        return Database::first(
            "SELECT
                (SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente')                            AS clientes,
                (SELECT COUNT(*) FROM usuarios WHERE tipo = 'prestador')                          AS prestadores,
                (SELECT COUNT(*) FROM usuarios WHERE suspenso_em IS NOT NULL)                     AS suspensos,
                (SELECT COUNT(*) FROM usuarios WHERE tipo <> 'admin' AND criado_em >= NOW() - INTERVAL 7 DAY) AS novos_semana,
                (SELECT COUNT(*) FROM avaliacoes)                                                 AS avaliacoes,
                (SELECT ROUND(AVG(nota), 1) FROM avaliacoes)                                      AS media,
                (SELECT COUNT(*) FROM conversas)                                                  AS conversas,
                (SELECT COUNT(*) FROM mensagens)                                                  AS mensagens,
                (SELECT COUNT(*) FROM contatos WHERE status = 'novo')                             AS contatos_novos,
                (SELECT COUNT(*) FROM denuncias WHERE status = 'aberta')                          AS denuncias_abertas,
                (SELECT COUNT(*) FROM prestador_perfis WHERE verificado_em IS NOT NULL)           AS verificados"
        ) ?? [];
    }

    /** Cadastros de clientes e prestadores nos últimos N meses (inclui meses sem cadastro). */
    public static function signupsByMonth(int $months = 6): array
    {
        $rows = Database::all(
            "SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes,
                    SUM(tipo = 'cliente') AS clientes, SUM(tipo = 'prestador') AS prestadores
             FROM usuarios
             WHERE tipo <> 'admin' AND criado_em >= DATE_FORMAT(NOW() - INTERVAL ? MONTH, '%Y-%m-01')
             GROUP BY mes",
            [$months - 1]
        );
        $byMonth = array_column($rows, null, 'mes');

        $names = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $time = strtotime(date('Y-m-01') . " -{$i} month");
            $key = date('Y-m', $time);
            $result[] = [
                'label'       => $names[(int) date('n', $time) - 1] . '/' . date('y', $time),
                'clientes'    => (int) ($byMonth[$key]['clientes'] ?? 0),
                'prestadores' => (int) ($byMonth[$key]['prestadores'] ?? 0),
            ];
        }
        return $result;
    }

    /** Cadastros por semana (segunda a domingo) nas últimas N semanas, incluindo semanas vazias. */
    public static function signupsByWeek(int $weeks = 8): array
    {
        $monday = strtotime('monday this week');
        $start = date('Y-m-d', strtotime('-' . ($weeks - 1) . ' week', $monday));
        $rows = Database::all(
            "SELECT YEARWEEK(criado_em, 1) AS semana, SUM(tipo = 'cliente') AS clientes, SUM(tipo = 'prestador') AS prestadores
             FROM usuarios WHERE tipo <> 'admin' AND criado_em >= ? GROUP BY semana",
            [$start]
        );
        $byWeek = array_column($rows, null, 'semana');

        $result = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $time = strtotime("-{$i} week", $monday);
            $key = date('o', $time) . date('W', $time);
            $result[] = [
                'label'       => date('d/m', $time),
                'clientes'    => (int) ($byWeek[$key]['clientes'] ?? 0),
                'prestadores' => (int) ($byWeek[$key]['prestadores'] ?? 0),
            ];
        }
        return $result;
    }

    /** Quantidade de avaliações por nota (5 → 1). */
    public static function ratingDistribution(): array
    {
        $distribution = array_fill_keys([5, 4, 3, 2, 1], 0);
        foreach (Database::all('SELECT nota, COUNT(*) AS total FROM avaliacoes GROUP BY nota') as $row) {
            $distribution[(int) $row['nota']] = (int) $row['total'];
        }
        return $distribution;
    }

    public static function latestUsers(int $limit = 5): array
    {
        return Database::all(
            "SELECT id, nome, email, tipo, foto, cidade, estado, criado_em FROM usuarios
             WHERE tipo <> 'admin' ORDER BY criado_em DESC, id DESC LIMIT " . (int) $limit
        );
    }

    public static function latestReviews(int $limit = 5): array
    {
        return self::reviews([], 1, $limit)['rows'];
    }

    /** @param array{q?: string, tipo?: string, status?: string} $f */
    public static function users(array $f, int $page): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(u.nome LIKE :q1 OR u.email LIKE :q2 OR u.cidade LIKE :q3)';
            $like = '%' . $f['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (in_array($f['tipo'] ?? '', ['cliente', 'prestador', 'admin'], true)) {
            $where[] = 'u.tipo = :tipo';
            $params['tipo'] = $f['tipo'];
        }
        if (($f['status'] ?? '') === 'ativo') {
            $where[] = 'u.suspenso_em IS NULL';
        } elseif (($f['status'] ?? '') === 'suspenso') {
            $where[] = 'u.suspenso_em IS NOT NULL';
        }

        if (($f['status'] ?? '') === 'verificado') {
            $where[] = 'p.verificado_em IS NOT NULL';
        }

        $sql = 'FROM usuarios u LEFT JOIN prestador_perfis p ON p.usuario_id = u.id WHERE ' . implode(' AND ', $where);
        return self::paginate(
            "SELECT u.id, u.nome, u.email, u.tipo, u.foto, u.cidade, u.estado, u.criado_em, u.suspenso_em, p.profissao, p.verificado_em,
                    (SELECT COUNT(*) FROM avaliacoes a WHERE a.prestador_id = u.id) AS avaliacoes_recebidas
             {$sql} ORDER BY u.criado_em DESC, u.id DESC",
            "SELECT COUNT(*) {$sql}",
            $params,
            $page
        );
    }

    /** @param array{q?: string, nota?: int} $f */
    public static function reviews(array $f, int $page, int $perPage = self::PER_PAGE): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(a.comentario LIKE :q1 OR c.nome LIKE :q2 OR p.nome LIKE :q3)';
            $like = '%' . $f['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (($f['nota'] ?? 0) >= 1 && $f['nota'] <= 5) {
            $where[] = 'a.nota = :nota';
            $params['nota'] = (int) $f['nota'];
        }

        $sql = 'FROM avaliacoes a
                JOIN usuarios c ON c.id = a.cliente_id
                JOIN usuarios p ON p.id = a.prestador_id
                WHERE ' . implode(' AND ', $where);
        return self::paginate(
            "SELECT a.*, c.nome AS cliente_nome, c.foto AS cliente_foto, p.nome AS prestador_nome {$sql}
             ORDER BY a.criado_em DESC, a.id DESC",
            "SELECT COUNT(*) {$sql}",
            $params,
            $page,
            $perPage
        );
    }

    public static function contacts(string $status, int $page): array
    {
        $where = '1 = 1';
        $params = [];
        if (in_array($status, ['novo', 'lido', 'respondido'], true)) {
            $where = 'status = :status';
            $params['status'] = $status;
        }
        return self::paginate(
            "SELECT * FROM contatos WHERE {$where} ORDER BY status = 'novo' DESC, criado_em DESC",
            "SELECT COUNT(*) FROM contatos WHERE {$where}",
            $params,
            $page
        );
    }

    public static function contactCounts(): array
    {
        $rows = Database::all('SELECT status, COUNT(*) AS total FROM contatos GROUP BY status');
        $counts = ['novo' => 0, 'lido' => 0, 'respondido' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public static function findContact(int $id): ?array
    {
        return Database::first('SELECT * FROM contatos WHERE id = ?', [$id]);
    }

    public static function updateContact(int $id, array $data): void
    {
        Database::update('contatos', $data, 'id = :id', ['id' => $id]);
    }

    private static function paginate(string $select, string $count, array $params, int $page, int $perPage = self::PER_PAGE): array
    {
        $total = (int) Database::value($count, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $rows = Database::all("{$select} LIMIT {$perPage} OFFSET {$offset}", $params);
        return compact('rows', 'total', 'page', 'pages');
    }
}

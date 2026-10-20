<?php

/**
 * Denúncias de perfis e avaliações feitas pelos usuários e analisadas no painel admin.
 */
final class Report
{
    public const PER_PAGE = 15;

    public const REASONS = [
        'spam'     => 'Spam ou propaganda',
        'ofensivo' => 'Conteúdo ofensivo',
        'falso'    => 'Informação falsa',
        'golpe'    => 'Suspeita de golpe',
        'outro'    => 'Outro motivo',
    ];

    public const STATUSES = [
        'aberta'     => 'Abertas',
        'resolvida'  => 'Resolvidas',
        'descartada' => 'Descartadas',
    ];

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM denuncias WHERE id = ?', [$id]);
    }

    /** O usuário já tem uma denúncia aberta contra este alvo? */
    public static function alreadyOpen(int $reporterId, ?int $providerId, ?int $reviewId): bool
    {
        return (bool) Database::value(
            "SELECT 1 FROM denuncias WHERE denunciante_id = ? AND status = 'aberta'
               AND prestador_id <=> ? AND avaliacao_id <=> ?",
            [$reporterId, $providerId, $reviewId]
        );
    }

    public static function create(int $reporterId, ?int $providerId, ?int $reviewId, string $reason, ?string $details): void
    {
        Database::insert('denuncias', [
            'denunciante_id' => $reporterId,
            'prestador_id'   => $providerId,
            'avaliacao_id'   => $reviewId,
            'motivo'         => $reason,
            'detalhes'       => $details,
        ]);
    }

    public static function countOpen(): int
    {
        try {
            return (int) Database::value("SELECT COUNT(*) FROM denuncias WHERE status = 'aberta'");
        } catch (PDOException) {
            return 0; // banco ainda sem a migração 002
        }
    }

    /** Número de denúncias por status (para as abas do painel). */
    public static function counts(): array
    {
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach (Database::all('SELECT status, COUNT(*) AS total FROM denuncias GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    /** Lista paginada com o alvo denunciado (perfil ou avaliação) já resolvido. */
    public static function list(string $status, int $page): array
    {
        $where = '1 = 1';
        $params = [];
        if (isset(self::STATUSES[$status])) {
            $where = 'd.status = :status';
            $params['status'] = $status;
        }

        $total = (int) Database::value("SELECT COUNT(*) FROM denuncias d WHERE {$where}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = Database::all(
            "SELECT d.*,
                    r.nome AS denunciante_nome,
                    pu.nome AS perfil_nome, pu.suspenso_em AS perfil_suspenso, pp.profissao AS perfil_profissao,
                    a.comentario AS avaliacao_comentario, a.nota AS avaliacao_nota, a.prestador_id AS avaliacao_prestador_id,
                    ac.nome AS avaliacao_autor, apu.nome AS avaliacao_prestador_nome,
                    (SELECT COUNT(*) FROM denuncias x
                      WHERE x.id <> d.id AND x.prestador_id <=> d.prestador_id AND x.avaliacao_id <=> d.avaliacao_id) AS outras
             FROM denuncias d
             JOIN usuarios r ON r.id = d.denunciante_id
             LEFT JOIN usuarios pu ON pu.id = d.prestador_id
             LEFT JOIN prestador_perfis pp ON pp.usuario_id = d.prestador_id
             LEFT JOIN avaliacoes a ON a.id = d.avaliacao_id
             LEFT JOIN usuarios ac ON ac.id = a.cliente_id
             LEFT JOIN usuarios apu ON apu.id = a.prestador_id
             WHERE {$where}
             ORDER BY d.status = 'aberta' DESC, d.criado_em DESC, d.id DESC
             LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );

        return compact('rows', 'total', 'page', 'pages');
    }

    /** Fecha a denúncia e todas as outras abertas contra o mesmo alvo. */
    public static function close(array $report, string $status): void
    {
        Database::query(
            "UPDATE denuncias SET status = ?, resolvido_em = NOW()
             WHERE status = 'aberta' AND prestador_id <=> ? AND avaliacao_id <=> ?",
            [$status, $report['prestador_id'], $report['avaliacao_id']]
        );
    }
}

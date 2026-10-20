<?php

/**
 * Pedidos de orçamento/agendamento entre cliente e prestador.
 * Fluxo: pendente → aceito → concluido (ou recusado / cancelado).
 */
final class Appointment
{
    public const SLOT_MINUTES = 60;
    public const DAYS_AHEAD = 14;
    public const LEAD_MINUTES = 60; // antecedência mínima para marcar

    public const STATUSES = [
        'pendente'  => 'Pendente',
        'aceito'    => 'Aceito',
        'recusado'  => 'Recusado',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT g.*, c.nome AS cliente_nome, c.email AS cliente_email, c.foto AS cliente_foto,
                    p.nome AS prestador_nome, p.email AS prestador_email, p.foto AS prestador_foto, pp.profissao
             FROM agendamentos g
             JOIN usuarios c ON c.id = g.cliente_id
             JOIN usuarios p ON p.id = g.prestador_id
             JOIN prestador_perfis pp ON pp.usuario_id = p.id
             WHERE g.id = ?',
            [$id]
        );
    }

    /** Horários livres: dia (Y-m-d) => lista de "HH:MM", a partir dos horários de atendimento do prestador. */
    public static function slots(int $providerId): array
    {
        $schedule = Schedule::forProvider($providerId);
        if (!$schedule) {
            return [];
        }

        $busy = array_flip(array_column(Database::all(
            "SELECT DATE_FORMAT(data_hora, '%Y-%m-%d %H:%i') AS slot FROM agendamentos
             WHERE prestador_id = ? AND status IN ('pendente', 'aceito') AND data_hora >= NOW()",
            [$providerId]
        ), 'slot'));

        $earliest = (new DateTimeImmutable())->modify('+' . self::LEAD_MINUTES . ' minutes');
        $today = new DateTimeImmutable('today');
        $slots = [];

        for ($i = 0; $i < self::DAYS_AHEAD; $i++) {
            $day = $today->modify("+{$i} days");
            $period = $schedule[(int) $day->format('w')] ?? null;
            if (!$period) {
                continue;
            }

            $date = $day->format('Y-m-d');
            $cursor = new DateTimeImmutable("{$date} {$period['inicio']}");
            $end = new DateTimeImmutable("{$date} {$period['fim']}");
            while ($cursor->modify('+' . self::SLOT_MINUTES . ' minutes') <= $end) {
                $key = $cursor->format('Y-m-d H:i');
                if ($cursor >= $earliest && !isset($busy[$key])) {
                    $slots[$date][] = $cursor->format('H:i');
                }
                $cursor = $cursor->modify('+' . self::SLOT_MINUTES . ' minutes');
            }
        }
        return $slots;
    }

    /** O horário (Y-m-d + HH:MM) ainda está livre? */
    public static function isFree(int $providerId, string $date, string $time): bool
    {
        return in_array($time, self::slots($providerId)[$date] ?? [], true);
    }

    /** Cria o pedido garantindo, dentro de uma transação, que o horário não foi tomado por outro. */
    public static function create(int $clientId, int $providerId, string $date, string $time, string $description): ?int
    {
        return Database::transaction(static function () use ($clientId, $providerId, $date, $time, $description) {
            // trava as linhas do prestador para serializar pedidos concorrentes para o mesmo horário
            Database::query('SELECT usuario_id FROM prestador_perfis WHERE usuario_id = ? FOR UPDATE', [$providerId]);
            if (!self::isFree($providerId, $date, $time)) {
                return null;
            }
            return Database::insert('agendamentos', [
                'cliente_id'   => $clientId,
                'prestador_id' => $providerId,
                'data_hora'    => "{$date} {$time}:00",
                'descricao'    => $description,
            ]);
        });
    }

    /**
     * Cliente troca o horário de um pedido pendente ou aceito. O pedido volta a "pendente"
     * para o prestador confirmar o novo horário.
     */
    public static function reschedule(int $id, int $providerId, string $date, string $time): bool
    {
        return Database::transaction(static function () use ($id, $providerId, $date, $time) {
            Database::query('SELECT usuario_id FROM prestador_perfis WHERE usuario_id = ? FOR UPDATE', [$providerId]);
            if (!self::isFree($providerId, $date, $time)) {
                return false;
            }
            return Database::query(
                "UPDATE agendamentos SET data_hora = ?, status = 'pendente', resposta = NULL, lembrete_em = NULL
                 WHERE id = ? AND status IN ('pendente', 'aceito')",
                ["{$date} {$time}:00", $id]
            )->rowCount() > 0;
        });
    }

    /**
     * Lembrete 24h antes dos serviços aceitos, para cliente e prestador.
     * Marca o pedido antes de avisar, para que execuções simultâneas não dupliquem o aviso.
     */
    public static function remindDue(int $limit = 20): int
    {
        $due = Database::all(
            "SELECT id FROM agendamentos
             WHERE status = 'aceito' AND lembrete_em IS NULL
               AND data_hora > NOW() AND data_hora <= NOW() + INTERVAL 24 HOUR
             ORDER BY data_hora LIMIT " . (int) $limit
        );

        $sent = 0;
        foreach ($due as $row) {
            $claimed = Database::query(
                "UPDATE agendamentos SET lembrete_em = NOW() WHERE id = ? AND lembrete_em IS NULL AND status = 'aceito'",
                [$row['id']]
            )->rowCount();
            if (!$claimed || !($a = self::find((int) $row['id']))) {
                continue;
            }

            $day = format_date($a['data_hora'], 'Y-m-d') === date('Y-m-d') ? 'hoje' : 'amanhã';
            $hour = format_date($a['data_hora'], 'H:i');
            Notification::send(
                ['id' => $a['cliente_id'], 'email' => $a['cliente_email'], 'nome' => $a['cliente_nome']],
                'agendamento', 'Lembrete de serviço',
                "Seu serviço com {$a['prestador_nome']} é {$day} às {$hour}.", '/agendamentos'
            );
            Notification::send(
                ['id' => $a['prestador_id'], 'email' => $a['prestador_email'], 'nome' => $a['prestador_nome']],
                'agendamento', 'Lembrete de serviço',
                "Você tem um serviço com {$a['cliente_nome']} {$day} às {$hour}.", '/agendamentos'
            );
            $sent++;
        }
        return $sent;
    }

    /**
     * Roda remindDue no máximo a cada 10 minutos, disparado pelas próprias visitas ao site
     * (para funcionar sem cron). Com cron/Agendador de Tarefas, use database/enviar-lembretes.php.
     */
    public static function remindDueThrottled(): void
    {
        $stamp = ROOT_PATH . '/storage/lembretes.last';
        if (is_file($stamp) && filemtime($stamp) > time() - 600) {
            return;
        }
        @touch($stamp);
        try {
            self::remindDue();
        } catch (Throwable) {
            // lembrete é conveniência: nunca derruba a página (ex.: banco sem a migração 004)
        }
    }

    /** Muda o status só se o atual estiver entre os permitidos (evita transições repetidas). */
    public static function transition(int $id, array $from, string $to, array $extra = []): bool
    {
        $marks = implode(',', array_fill(0, count($from), '?'));
        $sets = ['status = ?'];
        $params = [$to];
        foreach ($extra as $column => $value) { // colunas vêm do código, nunca do usuário
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }
        $stmt = Database::query(
            'UPDATE agendamentos SET ' . implode(', ', $sets) . " WHERE id = ? AND status IN ({$marks})",
            array_merge($params, [$id], $from)
        );
        return $stmt->rowCount() > 0;
    }

    /** Pedidos do usuário (cliente ou prestador), os que pedem ação primeiro. */
    public static function listFor(array $user): array
    {
        $mine = $user['tipo'] === 'cliente' ? 'g.cliente_id' : 'g.prestador_id';
        return Database::all(
            "SELECT g.*, c.nome AS cliente_nome, c.foto AS cliente_foto,
                    p.nome AS prestador_nome, p.foto AS prestador_foto, pp.profissao,
                    EXISTS (SELECT 1 FROM avaliacoes a WHERE a.cliente_id = g.cliente_id AND a.prestador_id = g.prestador_id) AS avaliado
             FROM agendamentos g
             JOIN usuarios c ON c.id = g.cliente_id
             JOIN usuarios p ON p.id = g.prestador_id
             JOIN prestador_perfis pp ON pp.usuario_id = p.id
             WHERE {$mine} = ?
             ORDER BY FIELD(g.status, 'pendente', 'aceito', 'concluido', 'recusado', 'cancelado'), g.data_hora DESC",
            [$user['id']]
        );
    }

    /** O cliente já teve algum serviço concluído com este prestador? (libera a avaliação) */
    public static function hasCompleted(int $clientId, int $providerId): bool
    {
        try {
            return (bool) Database::value(
                "SELECT 1 FROM agendamentos WHERE cliente_id = ? AND prestador_id = ? AND status = 'concluido' LIMIT 1",
                [$clientId, $providerId]
            );
        } catch (PDOException) {
            return false; // banco ainda sem a migração 003
        }
    }

    /** O prestador já aceitou (ou concluiu) algum pedido deste cliente? (libera telefone e e-mail) */
    public static function hasAccepted(int $clientId, int $providerId): bool
    {
        try {
            return (bool) Database::value(
                "SELECT 1 FROM agendamentos WHERE cliente_id = ? AND prestador_id = ? AND status IN ('aceito', 'concluido') LIMIT 1",
                [$clientId, $providerId]
            );
        } catch (PDOException) {
            return false;
        }
    }

    /** Pedidos que aguardam resposta do prestador. */
    public static function countPending(int $providerId): int
    {
        try {
            return (int) Database::value(
                "SELECT COUNT(*) FROM agendamentos WHERE prestador_id = ? AND status = 'pendente'",
                [$providerId]
            );
        } catch (PDOException) {
            return 0;
        }
    }
}

<?php

/**
 * Horários de atendimento do prestador (dia_semana: 0 = domingo ... 6 = sábado).
 */
final class Schedule
{
    public const DIAS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    public const DIAS_CURTOS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    /** @return array<int, array{inicio: string, fim: string}> indexado pelo dia da semana */
    public static function forProvider(int $providerId): array
    {
        $rows = Database::all(
            'SELECT dia_semana, TIME_FORMAT(inicio, "%H:%i") AS inicio, TIME_FORMAT(fim, "%H:%i") AS fim
             FROM prestador_horarios WHERE prestador_id = ? ORDER BY dia_semana',
            [$providerId]
        );
        $schedule = [];
        foreach ($rows as $row) {
            $schedule[(int) $row['dia_semana']] = ['inicio' => $row['inicio'], 'fim' => $row['fim']];
        }
        return $schedule;
    }

    /** @param array<int, array{inicio: string, fim: string}> $schedule */
    public static function save(int $providerId, array $schedule): void
    {
        Database::transaction(static function () use ($providerId, $schedule) {
            Database::query('DELETE FROM prestador_horarios WHERE prestador_id = ?', [$providerId]);
            foreach ($schedule as $day => $period) {
                Database::insert('prestador_horarios', [
                    'prestador_id' => $providerId,
                    'dia_semana'   => $day,
                    'inicio'       => $period['inicio'],
                    'fim'          => $period['fim'],
                ]);
            }
        });
    }

    /**
     * Situação no momento: aberto/fechado e um texto amigável.
     * @return array{aberto: bool|null, texto: string}
     */
    public static function status(array $schedule, ?DateTimeImmutable $now = null): array
    {
        if (!$schedule) {
            return ['aberto' => null, 'texto' => 'Horários não informados'];
        }

        $now ??= new DateTimeImmutable();
        $today = (int) $now->format('w');
        $time = $now->format('H:i');

        $todayPeriod = $schedule[$today] ?? null;
        if ($todayPeriod && $time >= $todayPeriod['inicio'] && $time < $todayPeriod['fim']) {
            return ['aberto' => true, 'texto' => 'Atendendo agora · até ' . $todayPeriod['fim']];
        }
        if ($todayPeriod && $time < $todayPeriod['inicio']) {
            return ['aberto' => false, 'texto' => 'Fechado agora · abre hoje às ' . $todayPeriod['inicio']];
        }

        for ($i = 1; $i <= 7; $i++) {
            $day = ($today + $i) % 7;
            if (isset($schedule[$day])) {
                $when = $i === 1 ? 'amanhã' : (mb_strtolower(self::DIAS[$day]) . ($day === 0 || $day === 6 ? '' : '-feira'));
                return ['aberto' => false, 'texto' => 'Fechado agora · abre ' . $when . ' às ' . $schedule[$day]['inicio']];
            }
        }
        return ['aberto' => false, 'texto' => 'Fechado agora'];
    }

    /** Resumo curto, ex.: "Seg–Sex 08:00–18:00 · Sáb 08:00–12:00". */
    public static function summary(array $schedule): string
    {
        $groups = [];
        foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
            if (!isset($schedule[$day])) {
                continue;
            }
            $period = $schedule[$day]['inicio'] . '–' . $schedule[$day]['fim'];
            $last = array_key_last($groups);
            if ($last !== null && $groups[$last]['period'] === $period && $groups[$last]['end'] === self::previous($day)) {
                $groups[$last]['end'] = $day;
            } else {
                $groups[] = ['start' => $day, 'end' => $day, 'period' => $period];
            }
        }
        return implode(' · ', array_map(static function ($g) {
            $days = $g['start'] === $g['end']
                ? self::DIAS_CURTOS[$g['start']]
                : self::DIAS_CURTOS[$g['start']] . '–' . self::DIAS_CURTOS[$g['end']];
            return $days . ' ' . $g['period'];
        }, $groups));
    }

    private static function previous(int $day): int
    {
        return $day === 0 ? 6 : $day - 1;
    }
}

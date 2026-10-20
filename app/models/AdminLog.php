<?php

/**
 * Registro das ações dos administradores (quem fez o quê, em quem e quando).
 */
final class AdminLog
{
    public const PER_PAGE = 25;

    public const ACTIONS = [
        'suspender'          => ['Suspendeu conta', 'fa-user-slash'],
        'reativar'           => ['Reativou conta', 'fa-user-check'],
        'excluir_usuario'    => ['Excluiu conta', 'fa-user-xmark'],
        'verificar'          => ['Concedeu selo de verificado', 'fa-circle-check'],
        'desverificar'       => ['Removeu selo de verificado', 'fa-circle-minus'],
        'remover_avaliacao'  => ['Removeu avaliação', 'fa-star-half-stroke'],
        'descartar_denuncia' => ['Descartou denúncia', 'fa-flag'],
        'responder_contato'  => ['Respondeu contato', 'fa-envelope-circle-check'],
    ];

    /** Grava a ação do admin logado. Falhar ao registrar nunca impede a ação em si. */
    public static function record(string $action, string $target, ?string $details = null): void
    {
        try {
            Database::insert('admin_logs', [
                'admin_id' => Auth::id(),
                'acao'     => $action,
                'alvo'     => mb_substr($target, 0, 190),
                'detalhes' => $details !== null ? mb_substr($details, 0, 255) : null,
                'ip'       => LoginThrottle::ip(),
            ]);
        } catch (PDOException) {
            // banco ainda sem a migração 004
        }
    }

    /** Descrição estável de um usuário para o registro. */
    public static function userLabel(array $user): string
    {
        return $user['nome'] . ' (#' . $user['id'] . ', ' . $user['tipo'] . ')';
    }

    public static function list(string $action, int $page): array
    {
        $where = '1 = 1';
        $params = [];
        if (isset(self::ACTIONS[$action])) {
            $where = 'l.acao = :acao';
            $params['acao'] = $action;
        }

        $total = (int) Database::value("SELECT COUNT(*) FROM admin_logs l WHERE {$where}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = Database::all(
            "SELECT l.*, a.nome AS admin_nome FROM admin_logs l
             LEFT JOIN usuarios a ON a.id = l.admin_id
             WHERE {$where} ORDER BY l.id DESC LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );

        return compact('rows', 'total', 'page', 'pages');
    }
}

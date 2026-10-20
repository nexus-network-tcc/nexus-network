<?php

final class NotificationController
{
    public function index(): void
    {
        $userId = (int) Auth::id();
        $items = Notification::recent($userId);
        View::render('notifications/index', ['title' => 'Notificações', 'items' => $items]);
    }

    /** Abre uma notificação: marca como lida e vai para o destino. */
    public function open(int $id): void
    {
        $item = Notification::find($id);
        if (!$item || (int) $item['usuario_id'] !== (int) Auth::id()) {
            abort(404, 'Notificação não encontrada.');
        }

        Notification::markRead($id);
        redirect($item['link'] ?: '/notificacoes');
    }

    public function readAll(): void
    {
        Notification::markAllRead((int) Auth::id());
        Session::flash('success', 'Notificações marcadas como lidas.');
        redirect('/notificacoes');
    }

    /** GET /api/notificacoes: contador para o sino (polling). */
    public function count(): void
    {
        json_response(['nao_lidas' => Notification::unreadCount((int) Auth::id())]);
    }
}

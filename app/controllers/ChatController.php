<?php

final class ChatController
{
    private const MAX_LENGTH = 2000;

    public function index(): void
    {
        View::render('chat/index', [
            'title'         => 'Conversas',
            'conversations' => Conversation::listFor(Auth::user()),
            'active'        => null,
            'other'         => null,
        ]);
    }

    /** Cliente inicia (ou retoma) a conversa com um prestador. */
    public function start(): void
    {
        $providerId = (int) input('prestador_id', 0);
        $provider = User::find($providerId);
        if (!$provider || $provider['tipo'] !== 'prestador') {
            abort(404, 'Profissional não encontrado.');
        }

        $id = Conversation::findOrCreate((int) Auth::id(), $providerId);
        redirect('/conversas/' . $id);
    }

    public function show(int $id): void
    {
        $conversation = $this->authorized($id);
        $user = Auth::user();
        $otherId = (int) ($user['tipo'] === 'cliente' ? $conversation['prestador_id'] : $conversation['cliente_id']);

        Conversation::markRead($id, (int) $user['id']);

        View::render('chat/index', [
            'title'         => 'Conversas',
            'conversations' => Conversation::listFor($user),
            'active'        => $conversation,
            'other'         => User::find($otherId),
            'messages'      => array_map([$this, 'present'], Conversation::messagesAfter($id, 0)),
        ]);
    }

    /** GET /api/conversas/{id}/mensagens?depois=ID: novas mensagens (polling). */
    public function messages(int $id): void
    {
        $this->authorized($id);
        $after = max(0, (int) input('depois', 0));
        $messages = Conversation::messagesAfter($id, $after);
        if ($messages) {
            Conversation::markRead($id, (int) Auth::id());
        }
        json_response(['mensagens' => array_map([$this, 'present'], $messages)]);
    }

    /** POST /api/conversas/{id}/mensagens: envia uma mensagem. */
    public function send(int $id): void
    {
        $this->authorized($id);
        $content = trim((string) input('conteudo', ''));

        if ($content === '') {
            json_response(['erro' => 'Escreva uma mensagem.'], 422);
        }
        if (mb_strlen($content) > self::MAX_LENGTH) {
            json_response(['erro' => 'A mensagem pode ter no máximo ' . self::MAX_LENGTH . ' caracteres.'], 422);
        }

        $messageId = Conversation::addMessage($id, (int) Auth::id(), $content);
        $message = Database::first('SELECT id, remetente_id, conteudo, lida, enviada_em FROM mensagens WHERE id = ?', [$messageId]);
        json_response(['mensagem' => $this->present($message)], 201);
    }

    private function authorized(int $id): array
    {
        $conversation = Conversation::find($id);
        if (!$conversation || !Conversation::belongsTo($conversation, (int) Auth::id())) {
            if (str_starts_with(request_path(), '/api/')) {
                json_response(['erro' => 'Conversa não encontrada.'], 404);
            }
            abort(404, 'Conversa não encontrada.');
        }
        return $conversation;
    }

    private function present(array $message): array
    {
        return [
            'id'       => (int) $message['id'],
            'minha'    => (int) $message['remetente_id'] === (int) Auth::id(),
            'conteudo' => $message['conteudo'],
            'lida'     => (bool) $message['lida'],
            'hora'     => date('H:i', strtotime($message['enviada_em'])),
            'data'     => date('Y-m-d', strtotime($message['enviada_em'])),
        ];
    }
}

<?php

/**
 * Pedidos de orçamento/agendamento: o cliente solicita, o prestador aceita/recusa/conclui.
 */
final class AppointmentController
{
    public function index(): void
    {
        $user = Auth::user();
        $appointments = Appointment::listFor($user);

        // horários livres de cada prestador, para o botão "Reagendar" do cliente
        $slots = [];
        if ($user['tipo'] === 'cliente') {
            foreach ($appointments as $a) {
                if (in_array($a['status'], ['pendente', 'aceito'], true) && !isset($slots[$a['prestador_id']])) {
                    $slots[$a['prestador_id']] = Appointment::slots((int) $a['prestador_id']);
                }
            }
        }

        View::render('appointments/index', [
            'title'        => 'Agendamentos',
            'user'         => $user,
            'appointments' => $appointments,
            'slots'        => $slots,
        ]);
    }

    /** Cliente pede orçamento escolhendo um horário livre do prestador. */
    public function store(int $providerId): void
    {
        $provider = User::find($providerId);
        if (!$provider || $provider['tipo'] !== 'prestador' || $provider['suspenso_em'] !== null) {
            abort(404, 'Profissional não encontrado.');
        }

        $target = '/prestador/' . $providerId;
        $slot = (string) input('horario', '');
        $description = trim((string) input('descricao', ''));

        $errors = [];
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})\|(\d{2}:\d{2})$/', $slot, $m)) {
            $errors['horario'] = 'Escolha um dia e horário.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 500) {
            $errors['descricao'] = 'Descreva o serviço em 10 a 500 caracteres.';
        }
        if ($errors) {
            Session::withInput(['horario' => $slot, 'descricao' => $description], $errors);
            redirect($target . '#agendar');
        }

        $id = Appointment::create((int) Auth::id(), $providerId, $m[1], $m[2], $description);
        if ($id === null) {
            Session::withInput(['descricao' => $description], ['horario' => 'Esse horário acabou de ser ocupado. Escolha outro.']);
            redirect($target . '#agendar');
        }

        $when = $this->when($m[1] . ' ' . $m[2]);
        $client = Auth::user();
        $this->notify(
            $provider,
            'Novo pedido de orçamento',
            $client['nome'] . ' quer agendar para ' . $when . '.',
            '/agendamentos',
            true
        );

        Session::flash('success', 'Pedido enviado! Você será avisado quando o profissional responder.');
        redirect('/agendamentos');
    }

    /** Cliente escolhe outro horário; o prestador precisa confirmar de novo. */
    public function reschedule(int $id): void
    {
        $appt = Appointment::find($id);
        if (!$appt || (int) $appt['cliente_id'] !== (int) Auth::id()) {
            abort(404, 'Pedido não encontrado.');
        }
        if (!in_array($appt['status'], ['pendente', 'aceito'], true)) {
            Session::flash('info', 'Só é possível reagendar pedidos pendentes ou aceitos.');
            redirect('/agendamentos');
        }

        if (!preg_match('/^(\d{4}-\d{2}-\d{2})\|(\d{2}:\d{2})$/', (string) input('horario', ''), $m)) {
            Session::flash('error', 'Escolha o novo dia e horário.');
            redirect('/agendamentos#agendamento-' . $id);
        }

        $old = $this->when($appt['data_hora']);
        if (!Appointment::reschedule($id, (int) $appt['prestador_id'], $m[1], $m[2])) {
            Session::flash('error', 'Esse horário não está mais livre. Escolha outro.');
            redirect('/agendamentos#agendamento-' . $id);
        }

        $provider = User::find((int) $appt['prestador_id']);
        if ($provider) {
            $this->notify(
                $provider,
                'Pedido de reagendamento',
                $appt['cliente_nome'] . ' pediu para mudar de ' . $old . ' para ' . $this->when($m[1] . ' ' . $m[2]) . '. Confirme o novo horário.',
                '/agendamentos',
                true
            );
        }
        Session::flash('success', 'Novo horário enviado. O profissional precisa confirmar.');
        redirect('/agendamentos');
    }

    public function accept(int $id): void
    {
        $appt = $this->ownedByProvider($id);
        $price = Validator::money(input('orcamento', ''));
        $reply = trim((string) input('resposta', ''));

        if ($price !== null && ($price < 0 || $price > 99999999)) {
            Session::flash('error', 'Informe um valor de orçamento válido.');
            redirect('/agendamentos');
        }
        if (mb_strlen($reply) > 500) {
            Session::flash('error', 'A resposta pode ter no máximo 500 caracteres.');
            redirect('/agendamentos');
        }

        if (!Appointment::transition($id, ['pendente'], 'aceito', ['orcamento' => $price, 'resposta' => $reply ?: null])) {
            Session::flash('info', 'Este pedido já foi respondido.');
            redirect('/agendamentos');
        }

        $this->notifyClient($appt, 'Pedido aceito', $appt['prestador_nome'] . ' aceitou o horário de ' . $this->when($appt['data_hora'])
            . ($price !== null ? ' com orçamento de ' . money($price) : '') . '.');
        Session::flash('success', 'Pedido aceito. O cliente foi avisado.');
        redirect('/agendamentos');
    }

    public function decline(int $id): void
    {
        $appt = $this->ownedByProvider($id);
        $reply = trim((string) input('resposta', ''));

        if (!Appointment::transition($id, ['pendente'], 'recusado', ['resposta' => mb_substr($reply, 0, 500) ?: null])) {
            Session::flash('info', 'Este pedido já foi respondido.');
            redirect('/agendamentos');
        }

        $this->notifyClient($appt, 'Pedido recusado', $appt['prestador_nome'] . ' não pôde atender em ' . $this->when($appt['data_hora']) . '. Tente outro horário.');
        Session::flash('success', 'Pedido recusado.');
        redirect('/agendamentos');
    }

    /** Prestador marca como concluído (só depois do horário combinado). */
    public function complete(int $id): void
    {
        $appt = $this->ownedByProvider($id);
        if (strtotime($appt['data_hora']) > time()) {
            Session::flash('error', 'Você só pode concluir o serviço a partir do horário combinado.');
            redirect('/agendamentos');
        }

        if (!Appointment::transition($id, ['aceito'], 'concluido')) {
            Session::flash('info', 'Só é possível concluir pedidos aceitos.');
            redirect('/agendamentos');
        }

        $this->notifyClient(
            $appt,
            'Serviço concluído',
            'Como foi com ' . $appt['prestador_nome'] . '? Deixe sua avaliação.',
            '/prestador/' . $appt['prestador_id'] . '#avaliar'
        );
        Session::flash('success', 'Serviço marcado como concluído. O cliente já pode avaliar você.');
        redirect('/agendamentos');
    }

    /** Cliente ou prestador cancela um pedido pendente ou aceito. */
    public function cancel(int $id): void
    {
        $appt = Appointment::find($id);
        $userId = (int) Auth::id();
        if (!$appt || !in_array($userId, [(int) $appt['cliente_id'], (int) $appt['prestador_id']], true)) {
            abort(404, 'Pedido não encontrado.');
        }

        if (!Appointment::transition($id, ['pendente', 'aceito'], 'cancelado')) {
            Session::flash('info', 'Este pedido não pode mais ser cancelado.');
            redirect('/agendamentos');
        }

        $byClient = $userId === (int) $appt['cliente_id'];
        $other = User::find((int) ($byClient ? $appt['prestador_id'] : $appt['cliente_id']));
        $who = $byClient ? $appt['cliente_nome'] : $appt['prestador_nome'];
        if ($other) {
            $this->notify($other, 'Agendamento cancelado', $who . ' cancelou o horário de ' . $this->when($appt['data_hora']) . '.', '/agendamentos', true);
        }
        Session::flash('success', 'Agendamento cancelado.');
        redirect('/agendamentos');
    }

    private function ownedByProvider(int $id): array
    {
        $appt = Appointment::find($id);
        if (!$appt || (int) $appt['prestador_id'] !== (int) Auth::id()) {
            abort(404, 'Pedido não encontrado.');
        }
        return $appt;
    }

    private function notifyClient(array $appt, string $title, string $message, string $link = '/agendamentos'): void
    {
        $client = User::find((int) $appt['cliente_id']);
        if ($client) {
            $this->notify($client, $title, $message, $link, true);
        }
    }

    /** Cria o aviso do sino e, opcionalmente, manda e-mail (falhas de envio nunca interrompem a ação). */
    private function notify(array $to, string $title, string $message, string $link, bool $email): void
    {
        Notification::send($email ? $to : ['id' => $to['id']], 'agendamento', $title, $message, $link);
    }

    private function when(string $dateTime): string
    {
        return format_date($dateTime, 'd/m') . ' às ' . format_date($dateTime, 'H:i');
    }
}

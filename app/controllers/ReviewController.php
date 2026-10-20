<?php

final class ReviewController
{
    public function store(int $providerId): void
    {
        $provider = User::find($providerId);
        if (!$provider || $provider['tipo'] !== 'prestador') {
            abort(404);
        }

        $clientId = (int) Auth::id();
        $rating = (int) input('nota', 0);
        $comment = trim((string) input('comentario', ''));
        $target = '/prestador/' . $providerId;

        $errors = [];
        if ($rating < 1 || $rating > 5) {
            $errors['nota'] = 'Escolha de 1 a 5 estrelas.';
        }
        if (mb_strlen($comment) < 10 || mb_strlen($comment) > 500) {
            $errors['comentario'] = 'O comentário deve ter entre 10 e 500 caracteres.';
        }
        if (Review::exists($clientId, $providerId)) {
            Session::flash('info', 'Você já avaliou este profissional. Exclua a avaliação anterior para enviar outra.');
            redirect($target);
        }
        if (!Appointment::hasCompleted($clientId, $providerId)) {
            Session::flash('info', 'Você poderá avaliar depois que o profissional concluir um serviço agendado com você.');
            redirect($target);
        }

        if ($errors) {
            Session::withInput(['nota' => $rating, 'comentario' => $comment], $errors);
            redirect($target . '#avaliar');
        }

        try {
            Review::create($clientId, $providerId, $rating, $comment);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }

        Notification::create(
            $providerId,
            'avaliacao',
            'Nova avaliação: ' . $rating . ' de 5',
            Auth::user()['nome'] . ' avaliou o seu serviço.',
            '/perfil#avaliacoes'
        );

        Session::flash('success', 'Obrigado! Sua avaliação foi publicada.');
        redirect($target . '#avaliacoes');
    }

    public function destroy(int $id): void
    {
        $review = Review::find($id);
        if (!$review || (int) $review['cliente_id'] !== (int) Auth::id()) {
            abort(403, 'Você só pode excluir as suas próprias avaliações.');
        }

        Review::delete($id);
        Session::flash('success', 'Avaliação excluída.');
        back('/prestador/' . $review['prestador_id']);
    }
}

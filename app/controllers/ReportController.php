<?php

/**
 * Denúncias feitas por clientes e prestadores (a análise fica no AdminController).
 */
final class ReportController
{
    public function provider(int $id): void
    {
        $provider = User::find($id);
        if (!$provider || $provider['tipo'] !== 'prestador' || $provider['suspenso_em'] !== null) {
            abort(404, 'Este profissional não existe ou removeu a conta.');
        }
        if ((int) Auth::id() === $id) {
            Session::flash('error', 'Você não pode denunciar o seu próprio perfil.');
            redirect('/prestador/' . $id);
        }

        $this->save($id, null, '/prestador/' . $id);
    }

    public function review(int $id): void
    {
        $review = Review::find($id);
        if (!$review) {
            abort(404, 'Avaliação não encontrada.');
        }
        if ((int) $review['cliente_id'] === (int) Auth::id()) {
            Session::flash('error', 'Você não pode denunciar a sua própria avaliação.');
            redirect('/prestador/' . $review['prestador_id'] . '#avaliacoes');
        }

        $this->save(null, $id, '/prestador/' . $review['prestador_id'] . '#avaliacoes');
    }

    private function save(?int $providerId, ?int $reviewId, string $target): never
    {
        $reason = (string) input('motivo', '');
        $details = trim((string) input('detalhes', ''));

        if (!isset(Report::REASONS[$reason])) {
            Session::flash('error', 'Escolha o motivo da denúncia.');
            redirect($target);
        }
        if (mb_strlen($details) > 500) {
            Session::flash('error', 'Os detalhes podem ter no máximo 500 caracteres.');
            redirect($target);
        }
        if ($reason === 'outro' && mb_strlen($details) < 10) {
            Session::flash('error', 'Para "Outro motivo", explique o que aconteceu (mínimo de 10 caracteres).');
            redirect($target);
        }

        $reporterId = (int) Auth::id();
        if (Report::alreadyOpen($reporterId, $providerId, $reviewId)) {
            Session::flash('info', 'Você já denunciou isto e a equipe ainda está analisando.');
            redirect($target);
        }

        Report::create($reporterId, $providerId, $reviewId, $reason, $details !== '' ? $details : null);
        Session::flash('success', 'Denúncia enviada. A equipe da Nexus vai analisar em breve. Obrigado por ajudar!');
        redirect($target);
    }
}

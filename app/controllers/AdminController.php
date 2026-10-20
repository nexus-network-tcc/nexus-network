<?php

final class AdminController
{
    public function dashboard(): void
    {
        View::render('admin/dashboard', [
            'title'   => 'Painel',
            'section' => 'painel',
            'stats'   => Admin::stats(),
            'months'  => Admin::signupsByMonth(6),
            'weeks'   => Admin::signupsByWeek(8),
            'ratings' => Admin::ratingDistribution(),
            'topCategories' => SearchLog::topCategories(30, 6),
            'topTerms'      => SearchLog::topTerms(30, 6),
            'searchTotal'   => SearchLog::total(30),
            'users'   => Admin::latestUsers(6),
            'reviews' => Admin::latestReviews(5),
        ]);
    }

    public function users(): void
    {
        $filters = [
            'q'      => mb_substr((string) input('q', ''), 0, 100),
            'tipo'   => (string) input('tipo', ''),
            'status' => (string) input('status', ''),
        ];
        View::render('admin/users', [
            'title'   => 'Usuários',
            'section' => 'usuarios',
            'filters' => $filters,
        ] + Admin::users($filters, (int) input('pagina', 1)));
    }

    public function suspend(int $id): void
    {
        $user = $this->manageableUser($id);
        User::update($id, ['suspenso_em' => date('Y-m-d H:i:s')]);
        AdminLog::record('suspender', AdminLog::userLabel($user));
        Session::flash('success', $user['nome'] . ' foi suspenso(a). A conta não aparece mais no site e não consegue entrar.');
        back('/admin/usuarios');
    }

    public function reactivate(int $id): void
    {
        $user = $this->manageableUser($id);
        User::update($id, ['suspenso_em' => null]);
        AdminLog::record('reativar', AdminLog::userLabel($user));
        Session::flash('success', $user['nome'] . ' foi reativado(a).');
        back('/admin/usuarios');
    }

    public function deleteUser(int $id): void
    {
        $user = $this->manageableUser($id);
        Upload::delete($user['foto']);
        Gallery::deleteFiles($id);
        User::delete($id);
        AdminLog::record('excluir_usuario', AdminLog::userLabel($user));
        Session::flash('success', 'A conta de ' . $user['nome'] . ' foi excluída.');
        back('/admin/usuarios');
    }

    public function verify(int $id): void
    {
        $provider = $this->providerAccount($id);
        Database::update('prestador_perfis', ['verificado_em' => date('Y-m-d H:i:s')], 'usuario_id = :id', ['id' => $id]);
        AdminLog::record('verificar', AdminLog::userLabel($provider));
        Session::flash('success', $provider['nome'] . ' agora tem o selo de verificado.');
        back('/admin/usuarios');
    }

    public function unverify(int $id): void
    {
        $provider = $this->providerAccount($id);
        Database::update('prestador_perfis', ['verificado_em' => null], 'usuario_id = :id', ['id' => $id]);
        AdminLog::record('desverificar', AdminLog::userLabel($provider));
        Session::flash('success', 'O selo de verificado de ' . $provider['nome'] . ' foi removido.');
        back('/admin/usuarios');
    }

    public function reports(): void
    {
        $status = (string) input('status', 'aberta');
        if ($status !== '' && !isset(Report::STATUSES[$status])) {
            $status = 'aberta';
        }
        View::render('admin/reports', [
            'title'   => 'Denúncias',
            'section' => 'denuncias',
            'status'  => $status,
            'counts'  => Report::counts(),
        ] + Report::list($status, (int) input('pagina', 1)));
    }

    public function resolveReport(int $id): void
    {
        $report = Report::find($id);
        if (!$report || $report['status'] !== 'aberta') {
            Session::flash('info', 'Esta denúncia já foi analisada.');
            back('/admin/denuncias');
        }

        $action = (string) input('acao', '');
        if ($action === 'descartar') {
            Report::close($report, 'descartada');
            AdminLog::record('descartar_denuncia', 'Denúncia #' . $id, Report::REASONS[$report['motivo']] ?? null);
            Session::flash('success', 'Denúncia descartada.');
        } elseif ($action === 'remover' && $report['avaliacao_id']) {
            // As denúncias desta avaliação são apagadas junto (ON DELETE CASCADE).
            $review = Review::find((int) $report['avaliacao_id']);
            Review::delete((int) $report['avaliacao_id']);
            AdminLog::record('remover_avaliacao', 'Avaliação #' . $report['avaliacao_id'] . ' (denúncia #' . $id . ')', $review ? mb_strimwidth($review['comentario'], 0, 120, '…') : null);
            Session::flash('success', 'Avaliação removida.');
        } elseif ($action === 'suspender' && $report['prestador_id']) {
            $provider = $this->manageableUser((int) $report['prestador_id']);
            User::update((int) $provider['id'], ['suspenso_em' => date('Y-m-d H:i:s')]);
            Report::close($report, 'resolvida');
            AdminLog::record('suspender', AdminLog::userLabel($provider), 'Denúncia #' . $id);
            Session::flash('success', $provider['nome'] . ' foi suspenso(a) e a denúncia foi resolvida.');
        } else {
            Session::flash('error', 'Ação inválida para esta denúncia.');
        }
        back('/admin/denuncias');
    }

    public function reviews(): void
    {
        $filters = [
            'q'    => mb_substr((string) input('q', ''), 0, 100),
            'nota' => (int) input('nota', 0),
        ];
        View::render('admin/reviews', [
            'title'   => 'Avaliações',
            'section' => 'avaliacoes',
            'filters' => $filters,
        ] + Admin::reviews($filters, (int) input('pagina', 1)));
    }

    public function deleteReview(int $id): void
    {
        $review = Review::find($id);
        if (!$review) {
            abort(404, 'Avaliação não encontrada.');
        }
        Review::delete($id);
        AdminLog::record('remover_avaliacao', 'Avaliação #' . $id, mb_strimwidth($review['comentario'], 0, 120, '…'));
        Session::flash('success', 'Avaliação removida.');
        back('/admin/avaliacoes');
    }

    public function logs(): void
    {
        $action = (string) input('acao', '');
        View::render('admin/logs', [
            'title'   => 'Registro de ações',
            'section' => 'registro',
            'action'  => isset(AdminLog::ACTIONS[$action]) ? $action : '',
        ] + AdminLog::list($action, (int) input('pagina', 1)));
    }

    public function contacts(): void
    {
        $status = (string) input('status', '');
        View::render('admin/contacts', [
            'title'   => 'Contatos',
            'section' => 'contatos',
            'status'  => $status,
            'counts'  => Admin::contactCounts(),
        ] + Admin::contacts($status, (int) input('pagina', 1)));
    }

    public function contact(int $id): void
    {
        $contact = Admin::findContact($id);
        if (!$contact) {
            abort(404, 'Mensagem não encontrada.');
        }
        if ($contact['status'] === 'novo') {
            Admin::updateContact($id, ['status' => 'lido']);
            $contact['status'] = 'lido';
        }
        View::render('admin/contact', [
            'title'   => 'Contato de ' . $contact['nome'],
            'section' => 'contatos',
            'contact' => $contact,
        ]);
    }

    public function reply(int $id): void
    {
        $contact = Admin::findContact($id);
        if (!$contact) {
            abort(404, 'Mensagem não encontrada.');
        }

        $reply = trim((string) input('resposta', ''));
        if (mb_strlen($reply) < 10 || mb_strlen($reply) > 5000) {
            Session::withInput(['resposta' => $reply], ['resposta' => 'A resposta deve ter entre 10 e 5000 caracteres.']);
            redirect('/admin/contatos/' . $id);
        }

        $sent = Mailer::send(
            $contact['email'],
            'Resposta ao seu contato na Nexus Network',
            '<p>Olá, ' . e(first_name($contact['nome'])) . '!</p>'
            . '<p>' . nl2br(e($reply)) . '</p>'
            . '<hr style="border:0;border-top:1px solid #333;margin:24px 0">'
            . '<p style="color:#999;font-size:13px">Sua mensagem: ' . nl2br(e($contact['mensagem'])) . '</p>',
            Config::get('support_email')
        );

        if (!$sent) {
            Session::withInput(['resposta' => $reply]);
            Session::flash('error', 'Não foi possível enviar o e-mail. Verifique as configurações de e-mail no .env.');
            redirect('/admin/contatos/' . $id);
        }

        Admin::updateContact($id, [
            'status'        => 'respondido',
            'resposta'      => $reply,
            'respondido_em' => date('Y-m-d H:i:s'),
        ]);
        AdminLog::record('responder_contato', 'Contato #' . $id . ' (' . $contact['email'] . ')');
        Session::flash('success', 'Resposta enviada para ' . $contact['email'] . '.');
        redirect('/admin/contatos/' . $id);
    }

    /** Conta de prestador (único tipo que recebe selo de verificado). */
    private function providerAccount(int $id): array
    {
        $user = User::find($id);
        if (!$user || $user['tipo'] !== 'prestador') {
            abort(404, 'Prestador não encontrado.');
        }
        return $user;
    }

    /** Usuário que o admin pode suspender/excluir (nunca a si mesmo nem outro admin). */
    private function manageableUser(int $id): array
    {
        $user = User::find($id);
        if (!$user) {
            abort(404, 'Usuário não encontrado.');
        }
        if ($user['tipo'] === 'admin') {
            Session::flash('error', 'Contas de administrador não podem ser suspensas ou excluídas pelo painel.');
            back('/admin/usuarios');
        }
        return $user;
    }
}

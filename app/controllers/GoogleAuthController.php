<?php

final class GoogleAuthController
{
    public function redirect(): void
    {
        if (!GoogleOAuth::configured()) {
            Session::flash('error', 'O login com Google ainda não foi configurado neste servidor.');
            redirect('/entrar');
        }

        $state = bin2hex(random_bytes(16));
        Session::put('google_state', $state);
        redirect(GoogleOAuth::authorizationUrl($state));
    }

    public function callback(): void
    {
        $state = Session::get('google_state');
        Session::forget('google_state');

        if (input('error')) {
            Session::flash('info', 'Login com Google cancelado.');
            redirect('/entrar');
        }

        if (!$state || !hash_equals($state, (string) input('state', '')) || !input('code')) {
            Session::flash('error', 'Não foi possível validar o login com Google. Tente novamente.');
            redirect('/entrar');
        }

        $google = GoogleOAuth::fetchUser((string) input('code'));
        if (!$google) {
            Session::flash('error', 'Não foi possível obter seus dados do Google. Tente novamente.');
            redirect('/entrar');
        }

        // 1) Conta já vinculada ao Google
        $user = User::findByGoogleId($google['sub']);

        // 2) Conta existente com o mesmo e-mail: vincula ao Google
        if (!$user && ($user = User::findByEmail($google['email']))) {
            User::update((int) $user['id'], array_filter([
                'google_id' => $google['sub'],
                'foto'      => $user['foto'] ? null : $google['picture'],
            ]));
        }

        if ($user && $user['suspenso_em'] !== null) {
            Session::flash('error', 'Conta suspensa. Fale com o suporte: ' . Config::get('support_email'));
            redirect('/entrar');
        }

        if ($user) {
            Auth::login($user);
            Session::flash('success', 'Olá, ' . first_name($user['nome']) . '!');
            AuthController::redirectAfterLogin();
        }

        // 3) Primeiro acesso: completa o cadastro (tipo de conta e dados obrigatórios)
        Session::put('google_pending', $google);
        Session::flash('info', 'Falta pouco! Escolha o tipo de conta e complete seus dados.');
        redirect('/cadastro', ['google' => 1]);
    }
}

<?php

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth/login', [
            'title'  => 'Entrar',
            'google' => GoogleOAuth::configured(),
        ]);
    }

    public function login(): void
    {
        $email = mb_strtolower((string) input('email', ''));
        $password = (string) ($_POST['senha'] ?? '');

        if ($wait = LoginThrottle::blockedFor($email)) {
            Session::withInput(['email' => $email]);
            Session::flash('error', 'Muitas tentativas de login. Tente novamente em ' . ($wait < 90 ? $wait . ' segundos' : ceil($wait / 60) . ' minutos') . '.');
            redirect('/entrar');
        }

        $user = $email !== '' ? User::findByEmail($email) : null;

        if ($user && $user['senha'] === null) {
            Session::withInput(['email' => $email]);
            Session::flash('info', 'Esta conta foi criada com o Google. Use "Continuar com Google" ou defina uma senha em "Esqueci minha senha".');
            redirect('/entrar');
        }

        if (!$user || !password_verify($password, $user['senha'])) {
            LoginThrottle::fail($email);

            Session::withInput(['email' => $email]);
            Session::flash('error', 'E-mail ou senha incorretos.');
            redirect('/entrar');
        }

        if ($user['suspenso_em'] !== null) {
            Session::withInput(['email' => $email]);
            Session::flash('error', 'Conta suspensa. Fale com o suporte: ' . Config::get('support_email'));
            redirect('/entrar');
        }

        if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
            User::setPassword((int) $user['id'], $password);
        }

        LoginThrottle::clear($email);
        Auth::login($user);
        Session::flash('success', 'Olá, ' . first_name($user['nome']) . '! Que bom ter você de volta.');
        $this->redirectAfterLogin();
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'Você saiu da sua conta.');
        redirect('/');
    }

    public static function redirectAfterLogin(): never
    {
        $intended = Session::get('intended');
        Session::forget('intended');
        if (is_string($intended) && str_starts_with($intended, url('/')) && !str_starts_with($intended, '//')) {
            header('Location: ' . $intended);
            exit;
        }
        redirect(match (Auth::user()['tipo']) {
            'admin'     => '/admin',
            'prestador' => '/perfil',
            default     => '/buscar',
        });
    }
}

<?php

/**
 * Recuperação de senha em três etapas: e-mail → código → nova senha.
 */
final class PasswordResetController
{
    private const VERIFIED_TTL = 900;

    public function showEmail(): void
    {
        View::render('auth/forgot', ['title' => 'Recuperar senha', 'step' => 1]);
    }

    public function sendCode(): void
    {
        $email = mb_strtolower((string) input('email', ''));
        if (!Validator::email($email)) {
            Session::withInput(['email' => $email], ['email' => 'Informe um e-mail válido.']);
            redirect('/recuperar-senha');
        }

        $user = User::findByEmail($email);
        if ($user && !PasswordReset::recentlySent((int) $user['id'])) {
            $code = PasswordReset::create((int) $user['id']);
            Mailer::send(
                $user['email'],
                'Seu código de recuperação',
                '<p>Olá, ' . e(first_name($user['nome'])) . '!</p>'
                . '<p>Use o código abaixo para redefinir sua senha. Ele vale por ' . PasswordReset::MINUTES_VALID . ' minutos.</p>'
                . '<p style="font-size:32px;letter-spacing:8px;color:#20cd8d;font-weight:bold">' . $code . '</p>'
                . '<p>Se você não pediu a recuperação, ignore este e-mail.</p>'
            );
        }

        // Mesma resposta para e-mails cadastrados ou não (não revela quem tem conta)
        Session::put('reset', ['email' => $email, 'user_id' => $user['id'] ?? null, 'verified_at' => null]);
        Session::flash('success', 'Se o e-mail estiver cadastrado, você receberá um código em instantes.');
        redirect('/recuperar-senha/codigo');
    }

    public function showCode(): void
    {
        $reset = Session::get('reset');
        if (!$reset) {
            redirect('/recuperar-senha');
        }
        View::render('auth/forgot', ['title' => 'Digite o código', 'step' => 2, 'email' => $reset['email']]);
    }

    public function verifyCode(): void
    {
        $reset = Session::get('reset');
        if (!$reset) {
            redirect('/recuperar-senha');
        }

        $code = Validator::digits((string) input('codigo', ''));
        $result = strlen($code) === 6 && $reset['user_id']
            ? PasswordReset::verify((int) $reset['user_id'], $code)
            : 'invalid';

        if ($result !== 'ok') {
            Session::flash('error', match ($result) {
                'expired' => 'Código expirado. Solicite um novo.',
                'blocked' => 'Muitas tentativas incorretas. Solicite um novo código.',
                default   => 'Código incorreto. Confira o e-mail e tente novamente.',
            });
            redirect($result === 'invalid' ? '/recuperar-senha/codigo' : '/recuperar-senha');
        }

        $reset['verified_at'] = time();
        Session::put('reset', $reset);
        redirect('/recuperar-senha/nova');
    }

    public function showNew(): void
    {
        $this->requireVerified();
        View::render('auth/forgot', ['title' => 'Nova senha', 'step' => 3]);
    }

    public function saveNew(): void
    {
        $reset = $this->requireVerified();

        $password = (string) ($_POST['senha'] ?? '');
        if ($error = Validator::passwordError($password, (string) ($_POST['senha_confirmacao'] ?? ''))) {
            Session::withInput([], ['senha' => $error]);
            redirect('/recuperar-senha/nova');
        }

        User::setPassword((int) $reset['user_id'], $password);
        Session::forget('reset');
        Session::forget('login_lock');
        Session::flash('success', 'Senha redefinida! Agora é só entrar com a nova senha.');
        redirect('/entrar');
    }

    private function requireVerified(): array
    {
        $reset = Session::get('reset');
        if (!$reset || !$reset['verified_at'] || time() - $reset['verified_at'] > self::VERIFIED_TTL) {
            Session::forget('reset');
            Session::flash('error', 'Valide o código enviado por e-mail antes de criar a nova senha.');
            redirect('/recuperar-senha');
        }
        return $reset;
    }
}

<?php

final class RegisterController
{
    public function show(): void
    {
        $google = Session::get('google_pending');
        if (input('google') && !$google) {
            redirect('/cadastro');
        }

        $tipo = old('tipo', input('tipo', 'cliente'));

        View::render('auth/register', [
            'title'      => $google ? 'Complete seu cadastro' : 'Criar conta',
            'tipo'       => in_array($tipo, ['cliente', 'prestador'], true) ? $tipo : 'cliente',
            'google'     => input('google') ? $google : null,
            'googleOn'   => GoogleOAuth::configured(),
            'categories' => Category::all(),
        ]);
    }

    public function store(): void
    {
        $in = $_POST;
        $tipo = ($in['tipo'] ?? '') === 'prestador' ? 'prestador' : 'cliente';
        $google = !empty($in['google']) ? Session::get('google_pending') : null;
        $back = $google ? ['tipo' => $tipo, 'google' => 1] : ['tipo' => $tipo];

        if ($google) {
            $in['email'] = $google['email'];
        }

        [$personal, $errors] = UserForm::personal($in);
        [$identity, $identityErrors] = UserForm::identity($in, true);
        $errors += $identityErrors;

        $profile = null;
        $categories = [];
        if ($tipo === 'prestador') {
            [$profile, $categories, $profileErrors] = UserForm::professional($in);
            $errors += $profileErrors;
        }

        $password = (string) ($in['senha'] ?? '');
        if (!$google && ($error = Validator::passwordError($password, (string) ($in['senha_confirmacao'] ?? '')))) {
            $errors['senha'] = $error;
        }

        if (empty($in['termos'])) {
            $errors['termos'] = 'É preciso aceitar os termos e a política de privacidade.';
        }

        $photo = null;
        if (!$errors) {
            try {
                $photo = Upload::photo('foto');
            } catch (InvalidArgumentException $e) {
                $errors['foto'] = $e->getMessage();
            }
        }

        if ($errors) {
            Session::withInput($in, $errors);
            Session::flash('error', 'Confira os campos destacados.');
            redirect('/cadastro', $back);
        }

        $coords = Geocoder::forForm($personal['cidade'], $personal['estado'], $in['latitude'] ?? null, $in['longitude'] ?? null);

        $user = $personal + $identity + [
            'tipo'      => $tipo,
            'senha'     => $google ? null : password_hash($password, PASSWORD_DEFAULT),
            'foto'      => $photo ?? ($google['picture'] ?? null),
            'google_id' => $google['sub'] ?? null,
            'latitude'  => $coords['lat'] ?? null,
            'longitude' => $coords['lon'] ?? null,
        ];

        try {
            $id = User::create($user, $profile, $categories);
        } catch (PDOException $e) {
            Upload::delete($photo);
            if ($e->getCode() === '23000') {
                Session::withInput($in, ['email' => 'E-mail ou CPF já cadastrado.']);
                Session::flash('error', 'E-mail ou CPF já cadastrado.');
                redirect('/cadastro', $back);
            }
            throw $e;
        }

        Session::forget('google_pending');
        Auth::login(User::find($id));
        Session::flash('success', $tipo === 'prestador'
            ? 'Conta criada! Seu perfil já aparece nas buscas.'
            : 'Conta criada! Agora é só buscar o profissional ideal.');
        redirect($tipo === 'prestador' ? '/prestador/' . $id : '/buscar');
    }
}

<?php

/**
 * "Meu perfil" para clientes e prestadores.
 */
final class ProfileController
{
    public function show(): void
    {
        $user = Auth::user();
        $isProvider = $user['tipo'] === 'prestador';

        View::render('profile/show', [
            'title'       => 'Meu perfil',
            'user'        => $user,
            'categories'  => $isProvider ? Category::all() : [],
            'selected'    => $isProvider ? Category::idsForProvider((int) $user['id']) : [],
            'summary'     => $isProvider ? Review::summary((int) $user['id']) : null,
            'reviews'     => $isProvider ? Review::forProvider((int) $user['id']) : Review::byClient((int) $user['id']),
            'schedule'    => $isProvider ? Schedule::forProvider((int) $user['id']) : [],
            'gallery'     => $isProvider ? Gallery::forProvider((int) $user['id']) : [],
        ]);
    }

    public function updateSchedule(): void
    {
        [$schedule, $errors] = UserForm::schedule($_POST);
        if ($errors) {
            Session::withInput($_POST, $errors);
            Session::flash('error', reset($errors));
            redirect('/perfil#horarios');
        }

        Schedule::save((int) Auth::id(), $schedule);
        Session::flash('success', $schedule ? 'Horários de atendimento salvos.' : 'Horários removidos do seu perfil.');
        redirect('/perfil#horarios');
    }

    public function updateData(): void
    {
        $user = Auth::user();
        $in = $_POST;

        [$data, $errors] = UserForm::personal($in);
        $profile = null;
        $categories = null;
        if ($user['tipo'] === 'prestador') {
            [$profile, $categories, $profileErrors] = UserForm::professional($in);
            $errors += $profileErrors;
        }

        if ($errors) {
            Session::withInput($in, $errors);
            Session::flash('error', 'Confira os campos destacados.');
            redirect('/perfil#dados');
        }

        // Atualiza as coordenadas quando a cidade muda (ou se ainda não existiam)
        $cityChanged = $data['cidade'] !== $user['cidade'] || $data['estado'] !== $user['estado'];
        if ($cityChanged || $user['latitude'] === null) {
            $coords = Geocoder::forForm($data['cidade'], $data['estado'], $in['latitude'] ?? null, $in['longitude'] ?? null);
            $data['latitude'] = $coords['lat'] ?? null;
            $data['longitude'] = $coords['lon'] ?? null;
            if ($coords === null && $cityChanged) {
                Session::flash('info', 'Não conseguimos localizar a nova cidade no mapa; a distância pode não aparecer nas buscas.');
            }
        }

        User::update((int) $user['id'], $data, $profile, $categories);
        Session::flash('success', 'Dados atualizados com sucesso.');
        redirect('/perfil#dados');
    }

    public function updatePhoto(): void
    {
        $user = Auth::user();

        if (input('remover')) {
            Upload::delete($user['foto']);
            User::update((int) $user['id'], ['foto' => null]);
            Session::flash('success', 'Foto removida.');
            redirect('/perfil');
        }

        try {
            $photo = Upload::photo('foto');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            redirect('/perfil');
        }

        if ($photo === null) {
            Session::flash('error', 'Escolha uma imagem.');
            redirect('/perfil');
        }

        Upload::delete($user['foto']);
        User::update((int) $user['id'], ['foto' => $photo]);
        Session::flash('success', 'Foto atualizada.');
        redirect('/perfil');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        $hasPassword = $user['senha'] !== null;

        if ($hasPassword && !password_verify((string) ($_POST['senha_atual'] ?? ''), $user['senha'])) {
            Session::withInput([], ['senha_atual' => 'Senha atual incorreta.']);
            redirect('/perfil#seguranca');
        }

        $password = (string) ($_POST['senha'] ?? '');
        if ($error = Validator::passwordError($password, (string) ($_POST['senha_confirmacao'] ?? ''))) {
            Session::withInput([], ['senha' => $error]);
            redirect('/perfil#seguranca');
        }

        User::setPassword((int) $user['id'], $password);
        Session::regenerate();
        Session::flash('success', $hasPassword ? 'Senha alterada.' : 'Senha criada. Agora você também pode entrar com e-mail e senha.');
        redirect('/perfil#seguranca');
    }

    public function destroy(): void
    {
        $user = Auth::user();

        $confirmed = $user['senha'] !== null
            ? password_verify((string) ($_POST['senha_confirmacao_exclusao'] ?? ''), $user['senha'])
            : mb_strtoupper(trim((string) ($_POST['confirmacao'] ?? ''))) === 'EXCLUIR';

        if (!$confirmed) {
            Session::withInput([], ['exclusao' => $user['senha'] !== null ? 'Senha incorreta.' : 'Digite EXCLUIR para confirmar.']);
            redirect('/perfil#zona-de-perigo');
        }

        Upload::delete($user['foto']);
        Gallery::deleteFiles((int) $user['id']);
        User::delete((int) $user['id']);
        Auth::logout();
        Session::start();
        Session::flash('success', 'Sua conta foi excluída. Sentiremos sua falta!');
        redirect('/');
    }
}

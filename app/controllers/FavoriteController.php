<?php

final class FavoriteController
{
    public function index(): void
    {
        View::render('favorites/index', [
            'title'     => 'Favoritos',
            'providers' => Favorite::providersFor((int) Auth::id()),
        ]);
    }

    public function toggle(int $providerId): void
    {
        $provider = User::find($providerId);
        if (!$provider || $provider['tipo'] !== 'prestador') {
            abort(404, 'Profissional não encontrado.');
        }

        $added = Favorite::toggle((int) Auth::id(), $providerId);
        Session::flash('success', $added
            ? $provider['nome'] . ' foi adicionado aos seus favoritos.'
            : $provider['nome'] . ' foi removido dos seus favoritos.');
        back('/prestador/' . $providerId);
    }
}

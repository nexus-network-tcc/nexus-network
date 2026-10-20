<?php

final class ProviderController
{
    public function show(int $id): void
    {
        $provider = User::find($id);
        if (!$provider || $provider['tipo'] !== 'prestador' || ($provider['suspenso_em'] !== null && !Auth::isAdmin())) {
            abort(404, 'Este profissional não existe ou removeu a conta.');
        }

        $user = Auth::user();
        $origin = Location::current();
        $distance = $origin && Geocoder::validCoords($provider['latitude'], $provider['longitude'])
            ? ProviderSearch::distance($origin, ['lat' => (float) $provider['latitude'], 'lon' => (float) $provider['longitude']])
            : null;

        $reviews = Review::forProvider($id);
        $schedule = Schedule::forProvider($id);
        $isClient = $user && $user['tipo'] === 'cliente';
        $hasReviewed = $isClient && Review::exists((int) $user['id'], $id);
        $hasCompleted = $isClient && Appointment::hasCompleted((int) $user['id'], $id);
        $canReview = $isClient && !$hasReviewed && $hasCompleted;
        $isOwner = $user && (int) $user['id'] === $id;
        // Telefone e e-mail: só o dono, o admin (moderação) e clientes cujo pedido foi aceito pelo prestador
        $canSeeContact = $isOwner || ($user && $user['tipo'] === 'admin')
            || ($isClient && Appointment::hasAccepted((int) $user['id'], $id));

        View::render('provider/show', [
            'title'      => $provider['nome'] . ' · ' . $provider['profissao'],
            'provider'   => $provider,
            'categories' => Category::forProvider($id),
            'summary'    => Review::summary($id),
            'reviews'    => $reviews,
            'distance'   => $distance,
            'canReview'  => $canReview,
            'hasReviewed' => $hasReviewed,
            'slots'      => $isClient ? Appointment::slots($id) : [],
            'favorite'   => $isClient && Favorite::has((int) $user['id'], $id),
            'gallery'    => Gallery::forProvider($id),
            'isOwner'    => $isOwner,
            'canSeeContact' => $canSeeContact,
            'schedule'   => $schedule,
            'openStatus' => Schedule::status($schedule),
            'mapa'       => Geocoder::validCoords($provider['latitude'], $provider['longitude']),
        ]);
    }
}

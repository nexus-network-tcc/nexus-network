<?php

final class SearchController
{
    public function index(): void
    {
        $this->applyLocationFromQuery();
        $origin = Location::current();

        $order = (string) input('ordem', '');
        if (!isset(ProviderSearch::ORDERS[$order])) {
            $order = $origin ? 'distancia' : 'avaliacao';
        }
        if ($order === 'distancia' && !$origin) {
            $order = 'avaliacao';
        }

        $radius = (int) input('raio', 0);
        if (!in_array($radius, Config::get('search.radii'), true)) {
            $radius = 0;
        }

        $minRating = (float) input('nota_min', 0);
        $maxPrice = (int) input('preco_max', 0);

        $filters = [
            'nota_min'   => in_array($minRating, ProviderSearch::RATINGS, true) ? $minRating : 0,
            'preco_max'  => in_array($maxPrice, ProviderSearch::PRICES, true) ? $maxPrice : 0,
            'verificados' => input('verificados') === '1',
            'q'          => mb_substr((string) input('q', ''), 0, 100),
            'categorias' => Category::validIds((array) ($_GET['categoria'] ?? [])),
            'raio'       => $radius,
            'ordem'      => $order,
            'pagina'     => max(1, (int) input('pagina', 1)),
            'disponibilidade' => isset(ProviderSearch::AVAILABILITY[(string) input('disponibilidade', '')]) ? (string) input('disponibilidade', '') : '',
        ];
        $mapView = input('vista') === 'mapa';

        $search = ProviderSearch::run($filters + ['mapa' => $mapView], $origin);

        if ($filters['pagina'] === 1) {
            SearchLog::record($filters, $search['total']);
        }

        View::render('search/index', [
            'title'      => 'Buscar profissionais',
            'filters'    => $filters,
            'origin'     => $origin,
            'categories' => Category::allWithCounts(),
            'radii'      => Config::get('search.radii'),
            'ratings'    => ProviderSearch::RATINGS,
            'prices'     => ProviderSearch::PRICES,
            'mapView'    => $mapView,
            'mapa'       => $mapView,
        ] + $search);
    }

    /** Salva o local escolhido pelo navegador (GPS ou cidade geocodificada). */
    public function saveLocation(): void
    {
        $action = (string) input('acao', 'salvar');
        if ($action === 'limpar') {
            Location::clear();
            json_response(['ok' => true]);
        }

        $lat = input('lat');
        $lon = input('lon');
        if (!Geocoder::validCoords($lat, $lon)) {
            json_response(['ok' => false, 'erro' => 'Coordenadas inválidas.'], 422);
        }

        $label = mb_substr(strip_tags((string) input('label', 'Minha localização')), 0, 120);
        Location::set((float) $lat, (float) $lon, $label ?: 'Minha localização', 'navegador');
        json_response(['ok' => true, 'label' => $label]);
    }

    /** Aceita ?local=Cidade, UF (e opcionalmente lat/lon já calculados pelo navegador). */
    private function applyLocationFromQuery(): void
    {
        $local = trim((string) input('local', ''));
        if ($local === '') {
            return;
        }

        $current = Location::current();
        if ($current && mb_strtolower($current['label']) === mb_strtolower($local)) {
            return;
        }

        if (Geocoder::validCoords(input('lat'), input('lon'))) {
            Location::set((float) input('lat'), (float) input('lon'), $local, 'cidade');
            return;
        }

        $city = Location::parseCity($local);
        $coords = $city ? Geocoder::city($city['cidade'], $city['uf']) : null;
        if ($coords) {
            Location::set($coords['lat'], $coords['lon'], $city['cidade'] . ', ' . $city['uf'], 'cidade');
        } else {
            Session::flash('info', 'Não encontramos a cidade "' . $local . '". Use o formato "Cidade, UF".');
        }
    }
}

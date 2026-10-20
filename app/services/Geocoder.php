<?php

/**
 * Converte "cidade + UF" em coordenadas usando o Nominatim (OpenStreetMap),
 * com cache na tabela geocode_cache para não repetir consultas.
 */
final class Geocoder
{
    /** @return array{lat: float, lon: float}|null */
    public static function city(string $cidade, string $uf): ?array
    {
        $cidade = trim($cidade);
        $uf = strtoupper(trim($uf));
        if ($cidade === '' || strlen($uf) !== 2) {
            return null;
        }

        $cached = Database::first(
            'SELECT latitude, longitude FROM geocode_cache WHERE cidade = ? AND estado = ?',
            [$cidade, $uf]
        );
        if ($cached) {
            return ['lat' => (float) $cached['latitude'], 'lon' => (float) $cached['longitude']];
        }

        $results = Http::get('https://nominatim.openstreetmap.org/search?' . http_build_query([
            'city'         => $cidade,
            'state'        => estados()[$uf] ?? $uf,
            'country'      => 'Brasil',
            'format'       => 'json',
            'limit'        => 1,
        ]));

        if (empty($results[0]['lat'])) {
            return null;
        }

        $coords = ['lat' => (float) $results[0]['lat'], 'lon' => (float) $results[0]['lon']];
        self::remember($cidade, $uf, $coords['lat'], $coords['lon']);
        return $coords;
    }

    public static function remember(string $cidade, string $uf, float $lat, float $lon): void
    {
        Database::query(
            'INSERT INTO geocode_cache (cidade, estado, latitude, longitude) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude)',
            [trim($cidade), strtoupper($uf), $lat, $lon]
        );
    }

    public static function validCoords(mixed $lat, mixed $lon): bool
    {
        return is_numeric($lat) && is_numeric($lon)
            && abs((float) $lat) <= 90 && abs((float) $lon) <= 180
            && !((float) $lat == 0.0 && (float) $lon == 0.0);
    }

    /**
     * Coordenadas para um cadastro: usa as enviadas pelo navegador (que já
     * geocodificou a cidade) e, se faltarem, consulta o servidor.
     */
    public static function forForm(string $cidade, string $uf, mixed $lat, mixed $lon): ?array
    {
        if (self::validCoords($lat, $lon)) {
            self::remember($cidade, $uf, (float) $lat, (float) $lon);
            return ['lat' => (float) $lat, 'lon' => (float) $lon];
        }
        return self::city($cidade, $uf);
    }
}

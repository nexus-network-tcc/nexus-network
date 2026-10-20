<?php

/**
 * Ponto de origem usado para calcular distâncias:
 * 1) local escolhido nesta sessão (GPS do navegador ou cidade digitada);
 * 2) cidade do cadastro do usuário logado.
 */
final class Location
{
    /** @return array{lat: float, lon: float, label: string, source: string}|null */
    public static function current(): ?array
    {
        $geo = Session::get('geo');
        if (is_array($geo) && Geocoder::validCoords($geo['lat'] ?? null, $geo['lon'] ?? null)) {
            return $geo;
        }

        $user = Auth::user();
        if ($user && Geocoder::validCoords($user['latitude'], $user['longitude'])) {
            return [
                'lat'    => (float) $user['latitude'],
                'lon'    => (float) $user['longitude'],
                'label'  => $user['cidade'] . ', ' . $user['estado'],
                'source' => 'perfil',
            ];
        }

        return null;
    }

    public static function set(float $lat, float $lon, string $label, string $source): void
    {
        Session::put('geo', ['lat' => $lat, 'lon' => $lon, 'label' => $label, 'source' => $source]);
    }

    public static function clear(): void
    {
        Session::forget('geo');
    }

    /** Interpreta textos como "Presidente Prudente, SP" ou "Presidente Prudente - SP". */
    public static function parseCity(string $text): ?array
    {
        if (!preg_match('/^\s*(.+?)\s*[,\-\/]\s*([A-Za-z]{2})\s*$/u', $text, $m)) {
            return null;
        }
        $uf = strtoupper($m[2]);
        return isset(estados()[$uf]) ? ['cidade' => $m[1], 'uf' => $uf] : null;
    }
}

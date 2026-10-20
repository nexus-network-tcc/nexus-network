<?php
/**
 * Resultados da busca em mapa (Leaflet). Os dados vão em JSON para o map.js.
 * @var array $results
 * @var array|null $origin
 * @var array $filters
 * @var int $total
 */
$points = [];
$noCoords = 0;
foreach ($results as $p) {
    if (!Geocoder::validCoords($p['latitude'], $p['longitude'])) {
        $noCoords++;
        continue;
    }
    $points[] = [
        'nome'      => $p['nome'],
        'iniciais'  => initials($p['nome']),
        'foto'      => $p['foto'] ? avatar_url($p['foto']) : null,
        'profissao' => $p['profissao'],
        'cidade'    => $p['cidade'] . ', ' . $p['estado'],
        'media'     => round((float) $p['media'], 1),
        'total'     => (int) $p['total_avaliacoes'],
        'preco'     => price_range($p['preco_min'] !== null ? (float) $p['preco_min'] : null, $p['preco_max'] !== null ? (float) $p['preco_max'] : null),
        'agora'     => (bool) $p['atende_agora'],
        'distancia' => $p['distancia'] !== null ? distance_label((float) $p['distancia']) : null,
        'lat'       => (float) $p['latitude'],
        'lon'       => (float) $p['longitude'],
        'url'       => url('/prestador/' . $p['id']),
    ];
}
$mapData = [
    'pontos' => $points,
    'origem' => $origin ? ['lat' => $origin['lat'], 'lon' => $origin['lon'], 'label' => $origin['label']] : null,
    'raio'   => $filters['raio'],
];
?>
<div class="map-wrap">
    <div class="results-map" data-results-map role="region" aria-label="Mapa com os profissionais encontrados"></div>
    <script type="application/json" data-map-data><?= json_encode($mapData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</div>
<p class="muted small map-note">
    <i class="fa-solid fa-circle-info"></i> As posições são aproximadas (região da cidade de cada profissional). Clique num grupo para aproximar.
    <?php if ($noCoords): ?><?= $noCoords ?> <?= $noCoords === 1 ? 'profissional não aparece' : 'profissionais não aparecem' ?> no mapa por não ter localização.<?php endif; ?>
    <?php if ($total > ProviderSearch::MAP_LIMIT): ?>Mostrando os <?= ProviderSearch::MAP_LIMIT ?> primeiros resultados; use os filtros para refinar.<?php endif; ?>
</p>

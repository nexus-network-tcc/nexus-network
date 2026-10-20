<?php
$query = array_filter([
    'q'         => $filters['q'],
    'categoria' => $filters['categorias'],
    'raio'      => $filters['raio'] ?: null,
    'ordem'     => $filters['ordem'],
    'disponibilidade' => $filters['disponibilidade'] ?: null,
    'nota_min'  => $filters['nota_min'] ?: null,
    'preco_max' => $filters['preco_max'] ?: null,
    'verificados' => $filters['verificados'] ? '1' : null,
]);
$pageUrl = static fn (int $n) => url('/buscar', $query + ['pagina' => $n]);
$viewQuery = $query + ($mapView ? ['vista' => 'mapa'] : []);
$hidden = static function (array $params): string {
    $html = '';
    foreach ($params as $key => $value) {
        foreach ((array) $value as $v) {
            $html .= '<input type="hidden" name="' . e($key) . (is_array($value) ? '[]' : '') . '" value="' . e($v) . '">';
        }
    }
    return $html;
};
$from = $total ? ($page - 1) * Config::get('search.per_page') + 1 : 0;
$to = min($total, $page * Config::get('search.per_page'));
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <h1>Buscar profissionais</h1>
        <form class="search-bar search-bar-inline" action="<?= url('/buscar') ?>" method="get">
            <label class="search-field">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="sr-only">Serviço ou profissional</span>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Ex.: eletricista, aula de inglês, Carlos…">
            </label>
            <?= $hidden(array_diff_key($viewQuery, ['q' => 1])) ?>
            <button class="btn btn-primary" type="submit">Buscar</button>
        </form>
    </div>
</section>

<div class="container search-layout">
    <aside class="filters" id="filtros" data-filters>
        <form action="<?= url('/buscar') ?>" method="get" class="filters-form" data-autosubmit>
            <?= $hidden(array_intersect_key($viewQuery, ['q' => 1, 'ordem' => 1, 'vista' => 1])) ?>

            <div class="filters-head">
                <h2><i class="fa-solid fa-sliders"></i> Filtros</h2>
                <button type="button" class="icon-btn filters-close" data-filters-toggle aria-label="Fechar filtros"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="filter-block">
                <h3>Sua localização</h3>
                <?php if ($origin): ?>
                    <p class="location-current"><i class="fa-solid fa-location-dot"></i> <?= e($origin['label']) ?></p>
                <?php else: ?>
                    <p class="muted small">Defina sua localização para ver a distância até cada profissional.</p>
                <?php endif; ?>
                <div class="location-actions" data-location-form>
                    <input type="text" class="input input-sm" placeholder="Cidade, UF" data-location-input aria-label="Cidade">
                    <input type="hidden" data-lat><input type="hidden" data-lon>
                    <button type="button" class="btn btn-ghost btn-sm" data-location-apply>Aplicar</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-locate><i class="fa-solid fa-location-crosshairs"></i> Usar GPS</button>
                </div>
            </div>

            <div class="filter-block">
                <h3><label for="raio">Distância máxima</label></h3>
                <select class="input" name="raio" id="raio" <?= $origin ? '' : 'disabled' ?>>
                    <option value="">Qualquer distância</option>
                    <?php foreach ($radii as $r): ?>
                        <option value="<?= $r ?>" <?= $filters['raio'] === $r ? 'selected' : '' ?>>Até <?= $r ?> km</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <fieldset class="filter-block">
                <legend><h3>Disponibilidade</h3></legend>
                <?php foreach (ProviderSearch::AVAILABILITY as $key => $label): ?>
                    <label class="check check-radio">
                        <input type="radio" name="disponibilidade" value="<?= e($key) ?>" <?= $filters['disponibilidade'] === $key ? 'checked' : '' ?>>
                        <span class="check-box"></span>
                        <span class="check-label"><?= $label ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <div class="filter-block">
                <h3><label for="nota_min">Nota mínima</label></h3>
                <select class="input" name="nota_min" id="nota_min">
                    <option value="">Qualquer nota</option>
                    <?php foreach ($ratings as $n): ?>
                        <option value="<?= $n ?>" <?= $filters['nota_min'] === $n ? 'selected' : '' ?>><?= str_replace('.', ',', (string) $n) ?> estrelas ou mais</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-block">
                <h3><label for="preco_max">Preço inicial</label></h3>
                <select class="input" name="preco_max" id="preco_max">
                    <option value="">Qualquer valor</option>
                    <?php foreach ($prices as $v): ?>
                        <option value="<?= $v ?>" <?= $filters['preco_max'] === $v ? 'selected' : '' ?>>Até <?= money((float) $v) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filters['preco_max']): ?><p class="muted small">Profissionais com preço "a combinar" não aparecem neste filtro.</p><?php endif; ?>
            </div>

            <div class="filter-block">
                <label class="check">
                    <input type="checkbox" name="verificados" value="1" <?= $filters['verificados'] ? 'checked' : '' ?>>
                    <span class="check-box"></span>
                    <span class="check-label"><i class="fa-solid fa-circle-check text-green"></i> Somente verificados</span>
                </label>
            </div>

            <fieldset class="filter-block">
                <legend><h3>Categorias</h3></legend>
                <?php foreach ($categories as $c): ?>
                    <label class="check">
                        <input type="checkbox" name="categoria[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $filters['categorias'], true) ? 'checked' : '' ?>>
                        <span class="check-box"></span>
                        <span class="check-label"><i class="fa-solid <?= e($c['icone']) ?>"></i> <?= e($c['nome']) ?></span>
                        <span class="check-count"><?= (int) $c['total'] ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <div class="filters-actions">
                <button class="btn btn-primary btn-block" type="submit">Aplicar filtros</button>
                <a class="btn btn-ghost btn-block" href="<?= url('/buscar') ?>">Limpar tudo</a>
            </div>
        </form>
    </aside>

    <section class="results" aria-live="polite">
        <div class="results-toolbar">
            <p>
                <?php if ($total): ?>
                    <strong><?= $total ?></strong> <?= $total === 1 ? 'profissional encontrado' : 'profissionais encontrados' ?>
                    <?php if (!$mapView): ?><span class="muted">· mostrando <?= $from ?>–<?= $to ?></span><?php endif; ?>
                <?php else: ?>
                    Nenhum profissional encontrado
                <?php endif; ?>
            </p>
            <div class="results-toolbar-actions">
                <button type="button" class="btn btn-ghost btn-sm show-mobile" data-filters-toggle><i class="fa-solid fa-sliders"></i> Filtros</button>
                <div class="view-toggle" role="group" aria-label="Modo de exibição">
                    <a href="<?= url('/buscar', $query) ?>" class="<?= $mapView ? '' : 'is-active' ?>"<?= $mapView ? '' : ' aria-current="true"' ?>><i class="fa-solid fa-list"></i> Lista</a>
                    <a href="<?= url('/buscar', $query + ['vista' => 'mapa']) ?>" class="<?= $mapView ? 'is-active' : '' ?>"<?= $mapView ? ' aria-current="true"' : '' ?>><i class="fa-solid fa-map-location-dot"></i> Mapa</a>
                </div>
                <form action="<?= url('/buscar') ?>" method="get" data-autosubmit>
                    <?= $hidden(array_diff_key($viewQuery, ['ordem' => 1])) ?>
                    <label class="sort-select">
                        <span>Ordenar por</span>
                        <select class="input input-sm" name="ordem">
                            <?php foreach (ProviderSearch::ORDERS as $key => $label): ?>
                                <?php if ($key === 'distancia' && !$origin) continue; ?>
                                <option value="<?= $key ?>" <?= $filters['ordem'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>
        </div>

        <?php if ($results && $mapView): ?>
            <?= View::capture('search/_map', ['results' => $results, 'origin' => $origin, 'filters' => $filters, 'total' => $total]) ?>
        <?php elseif ($results): ?>
            <div class="provider-grid provider-grid-2">
                <?php foreach ($results as $p): ?>
                    <?= partial('provider-card', ['p' => $p]) ?>
                <?php endforeach; ?>
            </div>

            <?= partial('pagination', compact('page', 'pages', 'pageUrl')) ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
                <h2>Nenhum resultado por aqui</h2>
                <p>Tente outro termo, aumente a distância ou remova alguns filtros.</p>
                <a class="btn btn-primary" href="<?= url('/buscar') ?>">Ver todos os profissionais</a>
            </div>
        <?php endif; ?>
    </section>
</div>
<div class="filters-backdrop" data-filters-toggle></div>

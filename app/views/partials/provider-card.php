<?php
/** @var array $p */
$distance = isset($p['distancia']) && $p['distancia'] !== null ? (float) $p['distancia'] : null;
$rating = (float) $p['media'];
$total = (int) $p['total_avaliacoes'];
$viewer = Auth::user();
$canFavorite = $viewer && $viewer['tipo'] === 'cliente';
$isFavorite = $canFavorite && Favorite::has((int) $viewer['id'], (int) $p['id']);
?>
<article class="provider-card">
    <a class="provider-card-link" href="<?= url('/prestador/' . $p['id']) ?>" aria-label="Ver perfil de <?= e($p['nome']) ?>"></a>
    <?php if ($canFavorite): ?>
        <form class="favorite-form" method="post" action="<?= url('/prestador/' . $p['id'] . '/favoritar') ?>">
            <?= csrf_field() ?>
            <button class="favorite-btn<?= $isFavorite ? ' is-favorite' : '' ?>" type="submit" aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
                    title="<?= $isFavorite ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>">
                <i class="fa-<?= $isFavorite ? 'solid' : 'regular' ?> fa-heart" aria-hidden="true"></i>
                <span class="sr-only"><?= $isFavorite ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?></span>
            </button>
        </form>
    <?php endif; ?>
    <header class="provider-card-head">
        <?= avatar($p['foto'], $p['nome'], 'lg') ?>
        <div class="provider-card-id">
            <h3><?= e($p['nome']) ?><?= verified_badge($p['verificado_em'] ?? null) ?></h3>
            <p class="provider-card-role"><?= e($p['profissao']) ?></p>
            <div class="rating-line">
                <?php if ($total > 0): ?>
                    <?= stars($rating) ?>
                    <strong><?= number_format($rating, 1, ',', '') ?></strong>
                    <span class="muted">(<?= $total ?>)</span>
                <?php else: ?>
                    <span class="muted small"><i class="fa-regular fa-star"></i> Sem avaliações ainda</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($p['atende_agora']) || ($distance !== null && $distance <= 5)): ?>
            <div class="provider-card-badges">
                <?php if (!empty($p['atende_agora'])): ?><span class="pill pill-green badge-live">Atendendo agora</span><?php endif; ?>
                <?php if ($distance !== null && $distance <= 5): ?><span class="pill pill-green">Perto de você</span><?php endif; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!empty($p['descricao'])): ?>
        <p class="provider-card-desc"><?= e(mb_strimwidth($p['descricao'], 0, 140, '…')) ?></p>
    <?php endif; ?>

    <footer class="provider-card-foot">
        <span><i class="fa-solid fa-location-dot"></i> <?= e($p['cidade']) ?>, <?= e($p['estado']) ?>
            <?php if ($distance !== null): ?><strong class="text-green">· <?= distance_label($distance) ?></strong><?php endif; ?>
        </span>
        <span class="provider-card-price"><?= e(price_range($p['preco_min'] !== null ? (float) $p['preco_min'] : null, $p['preco_max'] !== null ? (float) $p['preco_max'] : null)) ?></span>
    </footer>
</article>

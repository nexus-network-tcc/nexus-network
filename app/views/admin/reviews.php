<?php
$query = array_filter($filters);
$pageUrl = static fn (int $n) => url('/admin/avaliacoes', $query + ['pagina' => $n]);
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Avaliações', 'section' => $section]) ?>

    <form class="admin-filters" method="get" action="<?= url('/admin/avaliacoes') ?>" data-autosubmit>
        <label class="search-field admin-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">Buscar</span>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Comentário, cliente ou prestador">
        </label>
        <select class="input input-sm" name="nota" aria-label="Nota">
            <option value="">Todas as notas</option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= $filters['nota'] === $i ? 'selected' : '' ?>><?= $i ?> <?= $i === 1 ? 'estrela' : 'estrelas' ?></option>
            <?php endfor; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
    </form>

    <p class="muted small"><?= $total ?> <?= $total === 1 ? 'avaliação' : 'avaliações' ?></p>

    <section class="card">
        <?php if ($rows): ?>
            <ul class="review-list">
                <?php foreach ($rows as $r): ?>
                    <li class="review">
                        <?= avatar($r['cliente_foto'], $r['cliente_nome'], 'md') ?>
                        <div class="review-body">
                            <div class="review-head">
                                <strong><?= e($r['cliente_nome']) ?></strong>
                                <span class="muted small">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    <a href="<?= url('/prestador/' . $r['prestador_id']) ?>"><?= e($r['prestador_nome']) ?></a>
                                    · <?= format_date($r['criado_em']) ?>
                                </span>
                            </div>
                            <?= stars((float) $r['nota']) ?>
                            <p><?= nl2br(e($r['comentario'])) ?></p>
                        </div>
                        <form method="post" action="<?= url('/admin/avaliacoes/' . $r['id'] . '/excluir') ?>" data-confirm="Remover esta avaliação? Ela deixa de contar na nota do prestador.">
                            <?= csrf_field() ?>
                            <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-trash"></i> Remover</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted center">Nenhuma avaliação encontrada.</p>
        <?php endif; ?>
    </section>

    <?= partial('pagination', compact('page', 'pages', 'pageUrl')) ?>
</div>

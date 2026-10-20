<?php
$query = array_filter(['status' => $status]);
$pageUrl = static fn (int $n) => url('/admin/denuncias', $query + ['pagina' => $n]);
$tabs = ['' => 'Todas'] + Report::STATUSES;
$totalAll = array_sum($counts);
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Denúncias', 'section' => $section]) ?>

    <nav class="chip-row admin-status-tabs" aria-label="Filtrar por situação">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="chip <?= $status === $key ? 'is-active' : '' ?>" href="<?= url('/admin/denuncias', $key === '' ? ['status' => ''] : ['status' => $key]) ?>">
                <?= e($label) ?> <span class="muted">(<?= $key === '' ? $totalAll : $counts[$key] ?>)</span>
            </a>
        <?php endforeach; ?>
    </nav>

    <section class="card">
        <?php if ($rows): ?>
            <ul class="report-list">
                <?php foreach ($rows as $d): ?>
                    <?php $isReview = $d['avaliacao_id'] !== null; ?>
                    <li class="report-item">
                        <div class="report-main">
                            <div class="report-top">
                                <span class="pill pill-danger"><i class="fa-solid fa-flag"></i> <?= e(Report::REASONS[$d['motivo']]) ?></span>
                                <span class="pill"><?= $isReview ? 'Avaliação' : 'Perfil' ?></span>
                                <?php if ($d['status'] !== 'aberta'): ?>
                                    <span class="pill <?= $d['status'] === 'resolvida' ? 'pill-green' : '' ?>"><?= $d['status'] === 'resolvida' ? 'Resolvida' : 'Descartada' ?> em <?= format_date($d['resolvido_em']) ?></span>
                                <?php endif; ?>
                                <?php if ((int) $d['outras'] > 0): ?>
                                    <span class="muted small"><?= (int) $d['outras'] ?> <?= (int) $d['outras'] === 1 ? 'outra denúncia' : 'outras denúncias' ?> sobre o mesmo alvo</span>
                                <?php endif; ?>
                                <span class="muted small report-date"><?= e(time_ago($d['criado_em'])) ?></span>
                            </div>

                            <?php if ($isReview): ?>
                                <div class="message-box">
                                    <div class="review-head">
                                        <strong><?= e($d['avaliacao_autor']) ?></strong>
                                        <span class="muted small">
                                            <i class="fa-solid fa-arrow-right"></i>
                                            <a href="<?= url('/prestador/' . $d['avaliacao_prestador_id']) ?>"><?= e($d['avaliacao_prestador_nome']) ?></a>
                                        </span>
                                    </div>
                                    <?= stars((float) $d['avaliacao_nota']) ?>
                                    <p><?= nl2br(e($d['avaliacao_comentario'])) ?></p>
                                </div>
                            <?php else: ?>
                                <p>
                                    Perfil de <a href="<?= url('/prestador/' . $d['prestador_id']) ?>"><strong><?= e($d['perfil_nome']) ?></strong></a>
                                    <span class="muted">· <?= e($d['perfil_profissao']) ?></span>
                                    <?php if ($d['perfil_suspenso']): ?><span class="pill pill-danger"><i class="fa-solid fa-ban"></i> Suspenso</span><?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <p class="muted small">
                                Denunciado por <strong><?= e($d['denunciante_nome']) ?></strong>
                                <?php if ($d['detalhes']): ?>: “<?= e($d['detalhes']) ?>”<?php endif; ?>
                            </p>
                        </div>

                        <?php if ($d['status'] === 'aberta'): ?>
                            <div class="row-actions report-actions">
                                <?php if ($isReview): ?>
                                    <form method="post" action="<?= url('/admin/denuncias/' . $d['id'] . '/resolver') ?>" data-confirm="Remover esta avaliação? Ela deixa de contar na nota do prestador e as denúncias sobre ela são apagadas.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="acao" value="remover">
                                        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-trash"></i> Remover avaliação</button>
                                    </form>
                                <?php elseif (!$d['perfil_suspenso']): ?>
                                    <form method="post" action="<?= url('/admin/denuncias/' . $d['id'] . '/resolver') ?>" data-confirm="Suspender <?= e($d['perfil_nome']) ?>? A conta deixa de aparecer no site e não consegue entrar.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="acao" value="suspender">
                                        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-ban"></i> Suspender perfil</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('/admin/denuncias/' . $d['id'] . '/resolver') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="acao" value="descartar">
                                    <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-xmark"></i> Descartar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted center">Nenhuma denúncia <?= $status === 'aberta' ? 'em aberto. Tudo em ordem!' : 'encontrada.' ?></p>
        <?php endif; ?>
    </section>

    <?= partial('pagination', compact('page', 'pages', 'pageUrl')) ?>
</div>

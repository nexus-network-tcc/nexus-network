<?php
$cards = [
    ['fa-user', 'Clientes', $stats['clientes'], null],
    ['fa-briefcase', 'Prestadores', $stats['prestadores'], null],
    ['fa-user-plus', 'Novos nos últimos 7 dias', $stats['novos_semana'], null],
    ['fa-ban', 'Contas suspensas', $stats['suspensos'], '/admin/usuarios?status=suspenso'],
    ['fa-star', 'Avaliações', $stats['avaliacoes'], '/admin/avaliacoes'],
    ['fa-star-half-stroke', 'Nota média', $stats['media'] ? number_format((float) $stats['media'], 1, ',', '') : '–', null],
    ['fa-comments', 'Conversas · mensagens', $stats['conversas'] . ' · ' . $stats['mensagens'], null],
    ['fa-envelope', 'Contatos sem leitura', $stats['contatos_novos'], '/admin/contatos?status=novo'],
    ['fa-flag', 'Denúncias abertas', $stats['denuncias_abertas'], '/admin/denuncias'],
    ['fa-circle-check', 'Prestadores verificados', $stats['verificados'], '/admin/usuarios?status=verificado'],
];
$max = max(1, ...array_map(static fn ($m) => $m['clientes'] + $m['prestadores'], $months));
$maxWeek = max(1, ...array_map(static fn ($w) => $w['clientes'] + $w['prestadores'], $weeks));
$maxRating = max(1, ...array_values($ratings));
$maxCategory = max(1, ...array_column($topCategories ?: [['total' => 1]], 'total'));
$maxTerm = max(1, ...array_column($topTerms ?: [['total' => 1]], 'total'));
$tipoLabel = ['cliente' => 'Cliente', 'prestador' => 'Prestador', 'admin' => 'Admin'];
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Visão geral', 'section' => $section]) ?>

    <div class="stat-grid">
        <?php foreach ($cards as [$icon, $label, $value, $link]): ?>
            <?php $tag = $link ? 'a' : 'div'; ?>
            <<?= $tag ?> class="stat-card"<?= $link ? ' href="' . e(url($link)) . '"' : '' ?>>
                <i class="fa-solid <?= $icon ?>"></i>
                <strong><?= e((string) $value) ?></strong>
                <span><?= e($label) ?></span>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>

    <div class="admin-grid">
        <section class="card">
            <h2 class="card-title">Cadastros por mês</h2>
            <div class="bar-legend">
                <span><i class="dot dot-green"></i> Prestadores</span>
                <span><i class="dot dot-light"></i> Clientes</span>
            </div>
            <div class="bar-chart" role="img" aria-label="Cadastros nos últimos <?= count($months) ?> meses">
                <?php foreach ($months as $m): ?>
                    <?php $total = $m['clientes'] + $m['prestadores']; ?>
                    <div class="bar-col" title="<?= e($m['label']) ?>: <?= $m['prestadores'] ?> prestadores, <?= $m['clientes'] ?> clientes">
                        <span class="bar-value"><?= $total ?></span>
                        <div class="bar-stack" style="height: <?= round($total / $max * 100) ?>%">
                            <span class="bar-seg bar-seg-light" style="flex: <?= $m['clientes'] ?>"></span>
                            <span class="bar-seg bar-seg-green" style="flex: <?= $m['prestadores'] ?>"></span>
                        </div>
                        <span class="bar-label"><?= e($m['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Últimos cadastros <a class="small card-title-link" href="<?= url('/admin/usuarios') ?>">Ver todos</a></h2>
            <ul class="mini-list">
                <?php foreach ($users as $u): ?>
                    <li>
                        <?= avatar($u['foto'], $u['nome'], 'sm') ?>
                        <div>
                            <strong><?= e($u['nome']) ?></strong>
                            <span class="muted small"><?= $tipoLabel[$u['tipo']] ?> · <?= e(time_ago($u['criado_em'])) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="admin-grid admin-grid-even">
        <section class="card">
            <h2 class="card-title">Cadastros por semana</h2>
            <div class="bar-legend">
                <span><i class="dot dot-green"></i> Prestadores</span>
                <span><i class="dot dot-light"></i> Clientes</span>
            </div>
            <div class="bar-chart bar-chart-sm" role="img" aria-label="Cadastros nas últimas <?= count($weeks) ?> semanas">
                <?php foreach ($weeks as $w): ?>
                    <?php $weekTotal = $w['clientes'] + $w['prestadores']; ?>
                    <div class="bar-col" title="Semana de <?= e($w['label']) ?>: <?= $w['prestadores'] ?> prestadores, <?= $w['clientes'] ?> clientes">
                        <span class="bar-value"><?= $weekTotal ?></span>
                        <div class="bar-stack" style="height: <?= round($weekTotal / $maxWeek * 100) ?>%">
                            <span class="bar-seg bar-seg-light" style="flex: <?= $w['clientes'] ?>"></span>
                            <span class="bar-seg bar-seg-green" style="flex: <?= $w['prestadores'] ?>"></span>
                        </div>
                        <span class="bar-label"><?= e($w['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="muted small chart-note">Cada coluna é uma semana, identificada pela data da segunda-feira.</p>
        </section>

        <section class="card">
            <h2 class="card-title">Categorias mais buscadas <span class="muted small">últimos 30 dias · <?= (int) $searchTotal ?> buscas</span></h2>
            <?php if ($topCategories): ?>
                <ul class="hbar-list">
                    <?php foreach ($topCategories as $c): ?>
                        <li>
                            <span class="hbar-label"><i class="fa-solid <?= e($c['icone']) ?>"></i> <?= e($c['nome']) ?></span>
                            <span class="hbar"><span style="width: <?= round($c['total'] / $maxCategory * 100) ?>%"></span></span>
                            <span class="hbar-value"><?= (int) $c['total'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">Ainda não há buscas por categoria neste período.</p>
            <?php endif; ?>

            <?php if ($topTerms): ?>
                <h3 class="card-subtitle">Termos digitados</h3>
                <ul class="hbar-list">
                    <?php foreach ($topTerms as $t): ?>
                        <li>
                            <span class="hbar-label"><?= e($t['termo']) ?></span>
                            <span class="hbar"><span style="width: <?= round($t['total'] / $maxTerm * 100) ?>%"></span></span>
                            <span class="hbar-value"><?= (int) $t['total'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <section class="card">
        <h2 class="card-title">Distribuição das notas <span class="muted small"><?= (int) $stats['avaliacoes'] ?> avaliações</span></h2>
        <ul class="hbar-list">
            <?php foreach ($ratings as $star => $count): ?>
                <li>
                    <span class="hbar-label"><?= $star ?> <i class="fa-solid fa-star star-icon"></i></span>
                    <span class="hbar"><span style="width: <?= round($count / $maxRating * 100) ?>%"></span></span>
                    <span class="hbar-value"><?= $count ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card">
        <h2 class="card-title">Últimas avaliações <a class="small card-title-link" href="<?= url('/admin/avaliacoes') ?>">Moderar</a></h2>
        <?php if ($reviews): ?>
            <ul class="review-list">
                <?php foreach ($reviews as $r): ?>
                    <li class="review">
                        <?= avatar($r['cliente_foto'], $r['cliente_nome'], 'md') ?>
                        <div class="review-body">
                            <div class="review-head">
                                <strong><?= e($r['cliente_nome']) ?></strong>
                                <span class="muted small">avaliou <a href="<?= url('/prestador/' . $r['prestador_id']) ?>"><?= e($r['prestador_nome']) ?></a> · <?= e(time_ago($r['criado_em'])) ?></span>
                            </div>
                            <?= stars((float) $r['nota']) ?>
                            <p><?= e($r['comentario']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Nenhuma avaliação ainda.</p>
        <?php endif; ?>
    </section>
</div>

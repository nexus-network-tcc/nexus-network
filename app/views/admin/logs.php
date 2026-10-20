<?php
$query = array_filter(['acao' => $action]);
$pageUrl = static fn (int $n) => url('/admin/registro', $query + ['pagina' => $n]);
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Registro de ações', 'section' => $section]) ?>

    <form class="admin-filters" method="get" action="<?= url('/admin/registro') ?>" data-autosubmit>
        <select class="input input-sm" name="acao" aria-label="Tipo de ação">
            <option value="">Todas as ações</option>
            <?php foreach (AdminLog::ACTIONS as $key => [$label]): ?>
                <option value="<?= e($key) ?>" <?= $action === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
    </form>

    <p class="muted small"><?= $total ?> <?= $total === 1 ? 'registro' : 'registros' ?> · mostra quem fez cada ação no painel, em quem e quando.</p>

    <section class="card">
        <?php if ($rows): ?>
            <ul class="log-list">
                <?php foreach ($rows as $l): ?>
                    <?php [$label, $icon] = AdminLog::ACTIONS[$l['acao']] ?? [$l['acao'], 'fa-gear']; ?>
                    <li class="log-item">
                        <span class="notif-icon"><i class="fa-solid <?= e($icon) ?>"></i></span>
                        <div class="log-body">
                            <strong><?= e($label) ?></strong>
                            <span class="log-target"><?= e($l['alvo']) ?></span>
                            <?php if ($l['detalhes']): ?><span class="muted small">“<?= e($l['detalhes']) ?>”</span><?php endif; ?>
                        </div>
                        <div class="log-meta muted small">
                            <span><?= e($l['admin_nome'] ?? 'Admin removido') ?></span>
                            <span><?= format_date($l['criado_em'], 'd/m/Y H:i') ?><?= $l['ip'] ? ' · ' . e($l['ip']) : '' ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted center">Nenhuma ação registrada ainda.</p>
        <?php endif; ?>
    </section>

    <?= partial('pagination', compact('page', 'pages', 'pageUrl')) ?>
</div>

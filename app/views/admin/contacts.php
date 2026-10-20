<?php
$pageUrl = static fn (int $n) => url('/admin/contatos', array_filter(['status' => $status, 'pagina' => $n]));
$tabs = ['' => 'Todos', 'novo' => 'Novos', 'lido' => 'Lidos', 'respondido' => 'Respondidos'];
$assuntos = ['duvida' => 'Dúvida', 'problema' => 'Problema', 'sugestao' => 'Sugestão', 'parceria' => 'Parceria', 'outro' => 'Outro'];
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Contatos', 'section' => $section]) ?>

    <div class="chip-row admin-status-tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= url('/admin/contatos', $key ? ['status' => $key] : []) ?>">
                <?= $label ?>
                <span class="muted"><?= $key ? $counts[$key] : array_sum($counts) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <section class="card card-flush">
        <?php if ($rows): ?>
            <ul class="inbox">
                <?php foreach ($rows as $c): ?>
                    <li>
                        <a class="inbox-item<?= $c['status'] === 'novo' ? ' is-unread' : '' ?>" href="<?= url('/admin/contatos/' . $c['id']) ?>">
                            <span class="status-dot status-<?= e($c['status']) ?>" title="<?= e(ucfirst($c['status'])) ?>"></span>
                            <div class="inbox-body">
                                <div class="inbox-top">
                                    <strong><?= e($c['nome']) ?></strong>
                                    <span class="pill"><?= e($assuntos[$c['assunto']] ?? $c['assunto']) ?></span>
                                    <span class="muted small inbox-date"><?= e(time_ago($c['criado_em'])) ?></span>
                                </div>
                                <p class="muted"><?= e(mb_strimwidth($c['mensagem'], 0, 140, '…')) ?></p>
                            </div>
                            <i class="fa-solid fa-chevron-right muted"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state empty-state-sm">
                <i class="fa-regular fa-envelope-open"></i>
                <p>Nenhuma mensagem por aqui.</p>
            </div>
        <?php endif; ?>
    </section>

    <?= partial('pagination', compact('page', 'pages', 'pageUrl')) ?>
</div>

<?php
$items = [
    'painel'     => ['/admin', 'fa-chart-pie', 'Visão geral'],
    'usuarios'   => ['/admin/usuarios', 'fa-users', 'Usuários'],
    'avaliacoes' => ['/admin/avaliacoes', 'fa-star', 'Avaliações'],
    'denuncias'  => ['/admin/denuncias', 'fa-flag', 'Denúncias'],
    'contatos'   => ['/admin/contatos', 'fa-envelope', 'Contatos'],
    'registro'   => ['/admin/registro', 'fa-clipboard-list', 'Registro'],
];
$novos = (int) Database::value("SELECT COUNT(*) FROM contatos WHERE status = 'novo'");
$abertas = Report::countOpen();
?>
<div class="admin-head">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-shield-halved"></i> Administração</span>
        <h1><?= e($title) ?></h1>
    </div>
    <nav class="admin-tabs" aria-label="Seções do painel">
        <?php foreach ($items as $key => [$path, $icon, $label]): ?>
            <a href="<?= url($path) ?>" class="<?= $section === $key ? 'is-active' : '' ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
                <?php if ($key === 'contatos' && $novos): ?><span class="badge-count"><?= $novos ?></span><?php endif; ?>
                <?php if ($key === 'denuncias' && $abertas): ?><span class="badge-count"><?= $abertas ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

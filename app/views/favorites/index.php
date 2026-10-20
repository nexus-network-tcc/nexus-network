<div class="container member-page member-page-wide">
    <header class="member-head">
        <h1>Meus favoritos</h1>
        <p class="muted">Profissionais que você salvou para contratar depois.</p>
    </header>

    <?php if ($providers): ?>
        <div class="provider-grid provider-grid-2">
            <?php foreach ($providers as $p): ?>
                <?= partial('provider-card', ['p' => $p]) ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-heart"></i>
            <h2>Você ainda não tem favoritos</h2>
            <p>Toque no coração de um profissional para guardá-lo aqui.</p>
            <a class="btn btn-primary" href="<?= url('/buscar') ?>">Buscar profissionais</a>
        </div>
    <?php endif; ?>
</div>

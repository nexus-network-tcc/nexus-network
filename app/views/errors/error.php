<section class="container error-page">
    <span class="error-code"><?= (int) $status ?></span>
    <h1><?= e($title) ?></h1>
    <p class="lead"><?= e($message) ?></p>
    <div class="actions-row">
        <a class="btn btn-primary" href="<?= url('/') ?>"><i class="fa-solid fa-house"></i> Ir para o início</a>
        <a class="btn btn-ghost" href="<?= url('/buscar') ?>"><i class="fa-solid fa-magnifying-glass"></i> Buscar profissionais</a>
    </div>
</section>

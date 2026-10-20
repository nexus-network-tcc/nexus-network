<?php
$faq = require ROOT_PATH . '/app/views/partials/faq-data.php';
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <span class="eyebrow">Central de ajuda</span>
        <h1>Como podemos <span class="text-gradient">ajudar?</span></h1>
        <p class="lead">Encontre respostas rápidas ou fale com a nossa equipe.</p>
        <label class="search-bar search-bar-single">
            <span class="search-field">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="sr-only">Buscar nas perguntas</span>
                <input type="search" placeholder="Digite sua dúvida, ex.: senha, avaliação, preço…" data-faq-search>
            </span>
        </label>
    </div>
</section>

<section class="section">
    <div class="container faq-layout">
        <div class="faq-tabs" role="tablist" aria-label="Assuntos">
            <button class="faq-tab is-active" type="button" data-faq-tab="todos" role="tab" aria-selected="true"><i class="fa-solid fa-list"></i> Todos</button>
            <?php foreach ($faq as $key => [$label, $icon]): ?>
                <button class="faq-tab" type="button" data-faq-tab="<?= $key ?>" role="tab" aria-selected="false" id="<?= $key ?>"><i class="fa-solid <?= $icon ?>"></i> <?= e($label) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="faq-list">
            <?php foreach ($faq as $key => [$label, $icon, $items]): ?>
                <?php foreach ($items as [$question, $answer]): ?>
                    <details class="faq-item" data-faq-group="<?= $key ?>">
                        <summary><?= e($question) ?> <i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
                        <p><?= e($answer) ?></p>
                    </details>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <div class="empty-state empty-state-sm" data-faq-empty hidden>
                <i class="fa-regular fa-circle-question"></i>
                <p>Nenhuma pergunta encontrada. <a href="<?= url('/contato') ?>">Fale com a gente</a>.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container channel-grid">
        <a class="channel-card" href="mailto:<?= e(Config::get('support_email')) ?>">
            <i class="fa-solid fa-envelope"></i>
            <h3>E-mail</h3>
            <p><?= e(Config::get('support_email')) ?></p>
        </a>
        <a class="channel-card" href="<?= url('/contato') ?>">
            <i class="fa-solid fa-message"></i>
            <h3>Formulário de contato</h3>
            <p>Respondemos em até 24 horas úteis</p>
        </a>
        <div class="channel-card">
            <i class="fa-solid fa-clock"></i>
            <h3>Atendimento</h3>
            <p>Segunda a sexta, das 8h às 18h</p>
        </div>
    </div>
</section>

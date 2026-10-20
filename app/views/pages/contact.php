<?php
$subjects = ['duvida' => 'Dúvida', 'problema' => 'Problema técnico', 'sugestao' => 'Sugestão', 'parceria' => 'Parceria', 'outro' => 'Outro'];
$user = Auth::user();
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <span class="eyebrow">Contato</span>
        <h1>Fale com a <span class="text-gradient">gente</span></h1>
        <p class="lead">Dúvidas, sugestões ou problemas? Mande sua mensagem e responderemos em breve.</p>
    </div>
</section>

<section class="section">
    <div class="container contact-layout">
        <form method="post" action="<?= url('/contato') ?>" class="card form-stack" id="formulario">
            <?= csrf_field() ?>
            <h2 class="card-title">Envie uma mensagem</h2>
            <div class="form-grid">
                <div class="field">
                    <label class="label" for="nome">Nome</label>
                    <input class="input<?= has_error('nome') ?>" id="nome" name="nome" value="<?= e(old('nome', $user['nome'] ?? '')) ?>" autocomplete="name" required maxlength="120">
                    <?= error_for('nome') ?>
                </div>
                <div class="field">
                    <label class="label" for="email">E-mail</label>
                    <input class="input<?= has_error('email') ?>" id="email" name="email" type="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" autocomplete="email" required>
                    <?= error_for('email') ?>
                </div>
                <div class="field">
                    <label class="label" for="telefone">Telefone <span class="muted">(opcional)</span></label>
                    <input class="input<?= has_error('telefone') ?>" id="telefone" name="telefone" value="<?= e(old('telefone')) ?>" inputmode="tel" data-mask="phone" placeholder="(18) 99123-4567">
                    <?= error_for('telefone') ?>
                </div>
                <div class="field">
                    <label class="label" for="assunto">Assunto</label>
                    <select class="input<?= has_error('assunto') ?>" id="assunto" name="assunto" required>
                        <option value="">Selecione</option>
                        <?php foreach ($subjects as $key => $label): ?>
                            <option value="<?= $key ?>" <?= old('assunto') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= error_for('assunto') ?>
                </div>
                <div class="field field-full">
                    <label class="label" for="mensagem">Mensagem</label>
                    <textarea class="input<?= has_error('mensagem') ?>" id="mensagem" name="mensagem" rows="6" minlength="10" maxlength="3000" required data-counter><?= e(old('mensagem')) ?></textarea>
                    <?= error_for('mensagem') ?>
                </div>
            </div>
            <label class="check">
                <input type="checkbox" name="privacidade" value="1" <?= old('privacidade') ? 'checked' : '' ?> required>
                <span class="check-box"></span>
                <span class="check-label">Concordo com a <a href="<?= url('/privacidade') ?>" target="_blank">política de privacidade</a>.</span>
            </label>
            <?= error_for('privacidade') ?>
            <div class="form-actions">
                <button class="btn btn-primary btn-lg" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar mensagem</button>
            </div>
        </form>

        <aside class="contact-side">
            <div class="info-card">
                <i class="fa-solid fa-envelope"></i>
                <div><h3>E-mail</h3><a href="mailto:<?= e(Config::get('support_email')) ?>"><?= e(Config::get('support_email')) ?></a></div>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-location-dot"></i>
                <div><h3>Onde estamos</h3><p>Presidente Prudente, SP</p></div>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-clock"></i>
                <div><h3>Horário</h3><p>Segunda a sexta, das 8h às 18h</p></div>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-circle-question"></i>
                <div><h3>Perguntas frequentes</h3><a href="<?= url('/suporte') ?>">Acesse a central de ajuda</a></div>
            </div>
        </aside>
    </div>
</section>

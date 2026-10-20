<?php $labels = [1 => 'E-mail', 2 => 'Código', 3 => 'Nova senha']; ?>
<div class="container auth-shell auth-shell-narrow">
    <section class="auth-card">
        <ol class="stepper" aria-label="Etapas">
            <?php foreach ($labels as $n => $label): ?>
                <li class="<?= $n < $step ? 'is-done' : ($n === $step ? 'is-current' : '') ?>">
                    <span><?= $n < $step ? '<i class="fa-solid fa-check"></i>' : $n ?></span> <?= $label ?>
                </li>
            <?php endforeach; ?>
        </ol>

        <?php if ($step === 1): ?>
            <div class="auth-icon"><i class="fa-solid fa-key"></i></div>
            <h1>Esqueceu a senha?</h1>
            <p class="muted">Informe o e-mail da sua conta. Enviaremos um código de 6 dígitos para você criar uma nova senha.</p>
            <form method="post" action="<?= url('/recuperar-senha') ?>" class="form-stack">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="label" for="email">E-mail</label>
                    <input class="input<?= has_error('email') ?>" id="email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
                    <?= error_for('email') ?>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Enviar código</button>
            </form>

        <?php elseif ($step === 2): ?>
            <div class="auth-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
            <h1>Digite o código</h1>
            <p class="muted">Se <strong><?= e($email) ?></strong> estiver cadastrado, você recebeu um código válido por <?= PasswordReset::MINUTES_VALID ?> minutos. Confira também a caixa de spam.</p>
            <form method="post" action="<?= url('/recuperar-senha/codigo') ?>" class="form-stack">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="label" for="codigo">Código de verificação</label>
                    <input class="input input-code" id="codigo" name="codigo" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" placeholder="000000" required autofocus>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Verificar</button>
            </form>
            <p class="auth-switch">Não recebeu? <a href="<?= url('/recuperar-senha') ?>">Enviar novo código</a></p>

        <?php else: ?>
            <div class="auth-icon"><i class="fa-solid fa-lock"></i></div>
            <h1>Crie uma nova senha</h1>
            <form method="post" action="<?= url('/recuperar-senha/nova') ?>" class="form-stack">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="label" for="senha">Nova senha</label>
                    <div class="input-password">
                        <input class="input<?= has_error('senha') ?>" id="senha" name="senha" type="password" autocomplete="new-password" required autofocus data-password>
                        <button type="button" class="input-icon-btn" data-toggle-password aria-label="Mostrar senha"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="senha_confirmacao">Confirmar nova senha</label>
                    <input class="input" id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required data-password-confirm>
                </div>
                <?= partial('password-rules') ?>
                <?= error_for('senha') ?>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Salvar nova senha</button>
            </form>
        <?php endif; ?>

        <p class="auth-switch"><a href="<?= url('/entrar') ?>"><i class="fa-solid fa-arrow-left"></i> Voltar para o login</a></p>
    </section>
</div>

<div class="container auth-shell">
    <?= partial('auth-aside', ['heading' => 'Que bom te ver de novo!', 'text' => 'Entre para conversar com profissionais, acompanhar suas conversas e avaliar os serviços contratados.']) ?>

    <section class="auth-card">
        <h1>Entrar</h1>
        <p class="muted">Use o e-mail e a senha do seu cadastro, seja cliente ou prestador.</p>

        <?php if ($google): ?>
            <a class="btn btn-google btn-block" href="<?= url('/auth/google') ?>">
                <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                Continuar com Google
            </a>
            <div class="divider"><span>ou com e-mail</span></div>
        <?php endif; ?>

        <?php if (str_ends_with((string) Config::get('db.name'), '_teste')): ?>
            <div class="test-accounts">
                <p><i class="fa-solid fa-flask"></i> <strong>Banco de teste</strong> · senha <code>Nexus@123</code></p>
                <div class="actions-row">
                    <button type="button" class="btn btn-ghost btn-sm" data-fill-login="cliente@teste.com"><i class="fa-solid fa-user"></i> Entrar como cliente</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-fill-login="prestador@teste.com"><i class="fa-solid fa-briefcase"></i> Entrar como prestador</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-fill-login="admin@teste.com"><i class="fa-solid fa-shield-halved"></i> Entrar como admin</button>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('/entrar') ?>" class="form-stack">
            <?= csrf_field() ?>
            <div class="field">
                <label class="label" for="email">E-mail</label>
                <input class="input" id="email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
            </div>
            <div class="field">
                <div class="label-row">
                    <label class="label" for="senha">Senha</label>
                    <a class="small" href="<?= url('/recuperar-senha') ?>">Esqueci minha senha</a>
                </div>
                <div class="input-password">
                    <input class="input" id="senha" name="senha" type="password" autocomplete="current-password" required>
                    <button type="button" class="input-icon-btn" data-toggle-password aria-label="Mostrar senha"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <button class="btn btn-primary btn-block btn-lg" type="submit">Entrar</button>
        </form>

        <p class="auth-switch">Ainda não tem conta?</p>
        <div class="auth-type-links">
            <a class="type-link" href="<?= url('/cadastro', ['tipo' => 'cliente']) ?>">
                <i class="fa-solid fa-user"></i>
                <span><strong>Sou cliente</strong><small>Quero contratar serviços</small></span>
            </a>
            <a class="type-link" href="<?= url('/cadastro', ['tipo' => 'prestador']) ?>">
                <i class="fa-solid fa-briefcase"></i>
                <span><strong>Sou prestador</strong><small>Quero oferecer serviços</small></span>
            </a>
        </div>
    </section>
</div>

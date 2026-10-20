<?php
$isProvider = $tipo === 'prestador';
$v = [
    'nome'           => old('nome', $google['name'] ?? ''),
    'email'          => $google['email'] ?? old('email'),
    'telefone'       => old('telefone'),
    'cpf'            => old('cpf'),
    'data_nasc'      => old('data_nasc'),
    'genero'         => old('genero'),
    'estado'         => old('estado'),
    'cidade'         => old('cidade'),
    'profissao'      => old('profissao'),
    'descricao'      => old('descricao'),
    'preco_min'      => old('preco_min'),
    'preco_max'      => old('preco_max'),
    'preco_combinar' => old('preco_combinar'),
];
$selected = array_map('intval', (array) old('categorias', []));
?>
<div class="container auth-shell auth-shell-wide">
    <?= partial('auth-aside', [
        'heading' => $google ? 'Falta pouco, ' . first_name($google['name'] ?: 'você') . '!' : 'Crie sua conta grátis',
        'text'    => 'Clientes encontram profissionais de confiança. Prestadores ganham visibilidade e novos clientes.',
    ]) ?>

    <section class="auth-card">
        <h1><?= $google ? 'Complete seu cadastro' : 'Criar conta' ?></h1>
        <p class="muted">Campos obrigatórios para garantir a segurança de todos na plataforma.</p>

        <?php if (!$google && $googleOn): ?>
            <a class="btn btn-google btn-block" href="<?= url('/auth/google') ?>">
                <i class="fa-brands fa-google"></i> Cadastrar com Google
            </a>
            <div class="divider"><span>ou preencha os dados</span></div>
        <?php endif; ?>

        <form method="post" action="<?= url('/cadastro') ?>" enctype="multipart/form-data" class="form-stack" data-register-form>
            <?= csrf_field() ?>
            <?php if ($google): ?><input type="hidden" name="google" value="1"><?php endif; ?>

            <div class="segmented" role="radiogroup" aria-label="Tipo de conta">
                <label>
                    <input type="radio" name="tipo" value="cliente" <?= !$isProvider ? 'checked' : '' ?> data-type-radio>
                    <span><i class="fa-solid fa-user"></i> Sou cliente</span>
                </label>
                <label>
                    <input type="radio" name="tipo" value="prestador" <?= $isProvider ? 'checked' : '' ?> data-type-radio>
                    <span><i class="fa-solid fa-briefcase"></i> Sou prestador</span>
                </label>
            </div>

            <div class="photo-picker">
                <div class="photo-preview" data-photo-preview>
                    <?php if (!empty($google['picture'])): ?>
                        <img src="<?= e($google['picture']) ?>" alt="" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <i class="fa-solid fa-camera"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="btn btn-ghost btn-sm" for="foto"><i class="fa-solid fa-upload"></i> Escolher foto</label>
                    <input class="sr-only" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif" data-photo-input>
                    <p class="field-hint">Opcional · JPG, PNG ou WEBP até 5 MB</p>
                    <?= error_for('foto') ?>
                </div>
            </div>

            <h2 class="form-section-title">Dados pessoais</h2>
            <?= partial('form-personal', ['v' => $v, 'emailLocked' => (bool) $google]) ?>

            <div class="provider-fields" data-provider-fields <?= $isProvider ? '' : 'hidden' ?>>
                <h2 class="form-section-title">Perfil profissional</h2>
                <?= partial('form-professional', ['v' => $v, 'categories' => $categories, 'selected' => $selected]) ?>
            </div>

            <?php if (!$google): ?>
                <h2 class="form-section-title">Senha de acesso</h2>
                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="senha">Senha</label>
                        <div class="input-password">
                            <input class="input<?= has_error('senha') ?>" id="senha" name="senha" type="password" autocomplete="new-password" required data-password>
                            <button type="button" class="input-icon-btn" data-toggle-password aria-label="Mostrar senha"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="senha_confirmacao">Confirmar senha</label>
                        <div class="input-password">
                            <input class="input" id="senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required data-password-confirm>
                            <button type="button" class="input-icon-btn" data-toggle-password aria-label="Mostrar senha"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="field-full">
                        <?= partial('password-rules') ?>
                        <?= error_for('senha') ?>
                    </div>
                </div>
            <?php endif; ?>

            <label class="check">
                <input type="checkbox" name="termos" value="1" <?= old('termos') ? 'checked' : '' ?> required>
                <span class="check-box"></span>
                <span class="check-label">Li e aceito os <a href="<?= url('/privacidade') ?>" target="_blank">termos de uso e a política de privacidade</a>.</span>
            </label>
            <?= error_for('termos') ?>

            <button class="btn btn-primary btn-block btn-lg" type="submit">Criar minha conta</button>
        </form>

        <p class="auth-switch">Já tem conta? <a href="<?= url('/entrar') ?>">Entrar</a></p>
    </section>
</div>

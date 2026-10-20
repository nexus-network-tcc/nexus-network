<?php
/**
 * Campos pessoais (cadastro e perfil).
 * @var array $v       valores atuais
 * @var bool  $locked  e-mail, CPF e nascimento não editáveis (perfil)
 * @var bool  $emailLocked e-mail vindo do Google
 */
$locked = $locked ?? false;
$emailLocked = $emailLocked ?? false;
?>
<div class="form-grid">
    <div class="field field-full">
        <label class="label" for="nome">Nome completo</label>
        <input class="input<?= has_error('nome') ?>" id="nome" name="nome" value="<?= e($v['nome']) ?>" autocomplete="name" required maxlength="120">
        <?= error_for('nome') ?>
    </div>

    <div class="field">
        <label class="label" for="email">E-mail</label>
        <input class="input<?= has_error('email') ?>" id="email" name="email" type="email" value="<?= e($v['email']) ?>" autocomplete="email" maxlength="150" <?= $locked || $emailLocked ? 'disabled' : 'required' ?>>
        <?= $locked ? '<p class="field-hint"><i class="fa-solid fa-lock"></i> O e-mail não pode ser alterado.</p>' : '' ?>
        <?= error_for('email') ?>
    </div>

    <div class="field">
        <label class="label" for="telefone">Telefone / WhatsApp</label>
        <input class="input<?= has_error('telefone') ?>" id="telefone" name="telefone" value="<?= e($v['telefone'] ? format_phone($v['telefone']) : '') ?>" inputmode="tel" autocomplete="tel" placeholder="(18) 99123-4567" required data-mask="phone">
        <?= error_for('telefone') ?>
    </div>

    <div class="field">
        <label class="label" for="cpf">CPF</label>
        <input class="input<?= has_error('cpf') ?>" id="cpf" name="cpf" value="<?= e($v['cpf'] ? format_cpf($v['cpf']) : '') ?>" inputmode="numeric" placeholder="000.000.000-00" <?= $locked ? 'disabled' : 'required data-mask="cpf"' ?>>
        <?= error_for('cpf') ?>
    </div>

    <div class="field">
        <label class="label" for="data_nasc">Data de nascimento</label>
        <input class="input<?= has_error('data_nasc') ?>" id="data_nasc" name="data_nasc" type="date" value="<?= e($v['data_nasc']) ?>" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" <?= $locked ? 'disabled' : 'required' ?>>
        <?= error_for('data_nasc') ?>
    </div>

    <div class="field">
        <label class="label" for="genero">Gênero</label>
        <select class="input<?= has_error('genero') ?>" id="genero" name="genero" required>
            <option value="">Selecione</option>
            <?php foreach (generos() as $g): ?>
                <option value="<?= e($g) ?>" <?= $v['genero'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
            <?php endforeach; ?>
        </select>
        <?= error_for('genero') ?>
    </div>

    <div class="field">
        <label class="label" for="estado">Estado</label>
        <select class="input<?= has_error('estado') ?>" id="estado" name="estado" required data-uf-select>
            <option value="">Selecione</option>
            <?php foreach (estados() as $uf => $name): ?>
                <option value="<?= $uf ?>" <?= $v['estado'] === $uf ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
        <?= error_for('estado') ?>
    </div>

    <div class="field">
        <label class="label" for="cidade">Cidade</label>
        <input class="input<?= has_error('cidade') ?>" id="cidade" name="cidade" value="<?= e($v['cidade']) ?>" list="cidades-lista" autocomplete="address-level2" required maxlength="100" data-city-input placeholder="Digite e escolha na lista">
        <datalist id="cidades-lista" data-city-list></datalist>
        <input type="hidden" name="latitude" data-lat>
        <input type="hidden" name="longitude" data-lon>
        <?= error_for('cidade') ?>
    </div>
</div>

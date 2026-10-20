<?php
/**
 * Campos do perfil profissional (cadastro e perfil do prestador).
 * @var array $v
 * @var array $categories
 * @var int[] $selected
 */
$aCombinar = !empty($v['preco_combinar']);
$money = static fn ($value) => $value === null || $value === '' ? '' : (is_numeric($value) ? number_format((float) $value, 2, ',', '.') : $value);
?>
<div class="form-grid">
    <div class="field field-full">
        <label class="label" for="profissao">Profissão</label>
        <input class="input<?= has_error('profissao') ?>" id="profissao" name="profissao" value="<?= e($v['profissao']) ?>" maxlength="80" placeholder="Ex.: Eletricista, Diarista, Professora de inglês" data-required-prestador>
        <?= error_for('profissao') ?>
    </div>

    <fieldset class="field field-full">
        <legend class="label">Categorias <span class="muted">(escolha uma ou mais)</span></legend>
        <div class="chip-select">
            <?php foreach ($categories as $c): ?>
                <label class="chip-option">
                    <input type="checkbox" name="categorias[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $selected, true) ? 'checked' : '' ?>>
                    <span><i class="fa-solid <?= e($c['icone']) ?>"></i> <?= e($c['nome']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= error_for('categorias') ?>
    </fieldset>

    <div class="field field-full">
        <label class="label" for="descricao">Descrição dos serviços <span class="muted">(opcional)</span></label>
        <textarea class="input<?= has_error('descricao') ?>" id="descricao" name="descricao" rows="5" maxlength="1000" data-counter placeholder="Conte sua experiência, os serviços que faz e a região que atende."><?= e($v['descricao']) ?></textarea>
        <?= error_for('descricao') ?>
    </div>

    <fieldset class="field field-full" data-price-group>
        <legend class="label">Faixa de preço</legend>
        <label class="switch">
            <input type="checkbox" name="preco_combinar" value="1" <?= $aCombinar ? 'checked' : '' ?> data-price-toggle>
            <span class="switch-track"></span>
            <span>Preço a combinar com o cliente</span>
        </label>
        <div class="price-fields">
            <div class="input-prefix">
                <span>R$</span>
                <input class="input<?= has_error('preco') ?>" name="preco_min" value="<?= e($money($v['preco_min'])) ?>" inputmode="decimal" placeholder="Mínimo" aria-label="Preço mínimo" data-mask="money" <?= $aCombinar ? 'disabled' : '' ?>>
            </div>
            <span class="muted">até</span>
            <div class="input-prefix">
                <span>R$</span>
                <input class="input<?= has_error('preco') ?>" name="preco_max" value="<?= e($money($v['preco_max'])) ?>" inputmode="decimal" placeholder="Máximo" aria-label="Preço máximo" data-mask="money" <?= $aCombinar ? 'disabled' : '' ?>>
            </div>
        </div>
        <?= error_for('preco') ?>
    </fieldset>
</div>

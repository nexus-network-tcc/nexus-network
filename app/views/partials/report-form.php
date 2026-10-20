<?php
/**
 * Formulário de denúncia recolhível (usa <details>, sem JavaScript).
 * @var string $action URL que recebe o POST
 * @var string $label  texto do botão que abre o formulário
 * @var string $id     sufixo único dos ids dos campos
 * @var bool   $small  versão discreta (link), usada dentro das avaliações
 */
$small = $small ?? false;
?>
<details class="report-box<?= $small ? ' report-box-small' : '' ?>">
    <summary class="<?= $small ? 'link-muted' : 'btn btn-ghost btn-block' ?>"><i class="fa-regular fa-flag"></i> <?= e($label) ?></summary>
    <form method="post" action="<?= e($action) ?>" class="form-stack report-form">
        <?= csrf_field() ?>
        <div class="field">
            <label class="label" for="motivo-<?= e($id) ?>">Motivo</label>
            <select class="input input-sm" id="motivo-<?= e($id) ?>" name="motivo" required>
                <option value="">Selecione…</option>
                <?php foreach (Report::REASONS as $key => $text): ?>
                    <option value="<?= $key ?>"><?= e($text) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label class="label" for="detalhes-<?= e($id) ?>">Detalhes <span class="muted">(opcional)</span></label>
            <textarea class="input" id="detalhes-<?= e($id) ?>" name="detalhes" rows="3" maxlength="500" placeholder="Conte o que aconteceu."></textarea>
        </div>
        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar denúncia</button>
    </form>
</details>

<form method="post" action="<?= url('/admin/contatos/' . $contact['id'] . '/responder') ?>" class="form-stack">
    <?= csrf_field() ?>
    <div class="field">
        <label class="label" for="resposta">Mensagem para <?= e($contact['email']) ?></label>
        <textarea class="input<?= has_error('resposta') ?>" id="resposta" name="resposta" rows="8" minlength="10" maxlength="5000" required data-counter
                  placeholder="Olá, <?= e(first_name($contact['nome'])) ?>! Obrigado pelo contato…"><?= e(old('resposta')) ?></textarea>
        <?= error_for('resposta') ?>
    </div>
    <p class="field-hint"><i class="fa-solid fa-circle-info"></i> A mensagem original vai citada no final do e-mail.</p>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar resposta</button>
    </div>
</form>

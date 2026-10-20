<?php $messages = Session::flashes(); ?>
<div class="toasts" aria-live="polite" data-toasts>
    <?php foreach ($messages as $flash): ?>
        <?php $icon = ['success' => 'fa-circle-check', 'error' => 'fa-circle-exclamation', 'info' => 'fa-circle-info'][$flash['type']] ?? 'fa-circle-info'; ?>
        <div class="toast toast-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
            <p><?= e($flash['message']) ?></p>
            <button type="button" class="toast-close" data-toast-close aria-label="Fechar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>

<?php
$assuntos = ['duvida' => 'Dúvida', 'problema' => 'Problema técnico', 'sugestao' => 'Sugestão', 'parceria' => 'Parceria', 'outro' => 'Outro'];
$statusLabel = ['novo' => 'Novo', 'lido' => 'Lido', 'respondido' => 'Respondido'];
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Contatos', 'section' => $section]) ?>

    <a class="back-link" href="<?= url('/admin/contatos') ?>"><i class="fa-solid fa-arrow-left"></i> Voltar para a caixa de entrada</a>

    <div class="admin-grid admin-grid-contact">
        <section class="card">
            <div class="contact-head">
                <?= avatar(null, $contact['nome'], 'lg') ?>
                <div>
                    <h2><?= e($contact['nome']) ?></h2>
                    <p class="muted">
                        <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a>
                        <?php if ($contact['telefone']): ?> · <?= e(format_phone($contact['telefone'])) ?><?php endif; ?>
                    </p>
                </div>
            </div>
            <ul class="meta-list">
                <li><i class="fa-solid fa-tag"></i> <?= e($assuntos[$contact['assunto']] ?? $contact['assunto']) ?></li>
                <li><i class="fa-regular fa-clock"></i> <?= format_date($contact['criado_em'], 'd/m/Y \à\s H:i') ?></li>
                <li><span class="status-dot status-<?= e($contact['status']) ?>"></span> <?= $statusLabel[$contact['status']] ?></li>
            </ul>
            <div class="message-box"><?= nl2br(e($contact['mensagem'])) ?></div>
        </section>

        <section class="card">
            <?php if ($contact['status'] === 'respondido' && $contact['resposta']): ?>
                <h2 class="card-title"><i class="fa-solid fa-circle-check text-green"></i> Respondido</h2>
                <p class="muted small">Enviado em <?= format_date($contact['respondido_em'], 'd/m/Y \à\s H:i') ?></p>
                <div class="message-box message-box-reply"><?= nl2br(e($contact['resposta'])) ?></div>
                <details class="reply-again">
                    <summary class="small">Enviar outra resposta</summary>
                    <?= View::capture('admin/_reply-form', ['contact' => $contact]) ?>
                </details>
            <?php else: ?>
                <h2 class="card-title">Responder por e-mail</h2>
                <?= View::capture('admin/_reply-form', ['contact' => $contact]) ?>
            <?php endif; ?>
        </section>
    </div>
</div>

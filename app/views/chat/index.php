<?php
$me = Auth::user();
$messages = $messages ?? [];
?>
<div class="container chat-page">
    <div class="chat-shell<?= $active ? ' has-active' : '' ?>">
        <aside class="chat-list" aria-label="Suas conversas">
            <div class="chat-list-head">
                <h1>Conversas</h1>
            </div>
            <?php if ($conversations): ?>
                <ul>
                    <?php foreach ($conversations as $c): ?>
                        <li>
                            <a class="chat-item<?= $active && (int) $active['id'] === (int) $c['id'] ? ' is-active' : '' ?>" href="<?= url('/conversas/' . $c['id']) ?>">
                                <?= avatar($c['outro_foto'], $c['outro_nome'], 'md') ?>
                                <div class="chat-item-body">
                                    <div class="chat-item-top">
                                        <strong><?= e($c['outro_nome']) ?></strong>
                                        <?php if ($c['ultima_data']): ?><span class="muted small"><?= e(time_ago($c['ultima_data'])) ?></span><?php endif; ?>
                                    </div>
                                    <p class="chat-item-preview">
                                        <?php if ($c['ultima_mensagem'] !== null): ?>
                                            <?= (int) $c['ultimo_remetente'] === (int) $me['id'] ? '<span class="muted">Você:</span> ' : '' ?><?= e(mb_strimwidth($c['ultima_mensagem'], 0, 60, '…')) ?>
                                        <?php else: ?>
                                            <span class="muted"><?= e($c['outro_profissao'] ?? 'Cliente') ?> · nova conversa</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if ((int) $c['nao_lidas'] > 0): ?><span class="badge-count"><?= (int) $c['nao_lidas'] ?></span><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state empty-state-sm">
                    <i class="fa-regular fa-comments"></i>
                    <p>Nenhuma conversa ainda.</p>
                    <?php if ($me['tipo'] === 'cliente'): ?>
                        <a class="btn btn-primary btn-sm" href="<?= url('/buscar') ?>">Encontrar um profissional</a>
                    <?php else: ?>
                        <p class="muted small">Quando um cliente enviar uma mensagem, ela aparecerá aqui.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </aside>

        <section class="chat-panel">
            <?php if ($active && $other): ?>
                <header class="chat-head">
                    <a class="icon-btn chat-back" href="<?= url('/conversas') ?>" aria-label="Voltar para conversas"><i class="fa-solid fa-arrow-left"></i></a>
                    <?= avatar($other['foto'], $other['nome'], 'md') ?>
                    <div class="chat-head-info">
                        <strong><?= e($other['nome']) ?></strong>
                        <span class="muted small"><?= $other['tipo'] === 'prestador' ? e($other['profissao']) . ' · ' : '' ?><?= e($other['cidade']) ?>, <?= e($other['estado']) ?></span>
                    </div>
                    <?php if ($other['tipo'] === 'prestador'): ?>
                        <a class="btn btn-ghost btn-sm" href="<?= url('/prestador/' . $other['id']) ?>">Ver perfil</a>
                    <?php endif; ?>
                </header>

                <div class="chat-messages" data-chat-messages
                     data-endpoint="<?= e(url('/api/conversas/' . $active['id'] . '/mensagens')) ?>"
                     data-last-id="<?= $messages ? (int) end($messages)['id'] : 0 ?>">
                    <?php if (!$messages): ?>
                        <div class="chat-empty" data-chat-empty>
                            <i class="fa-regular fa-hand"></i>
                            <p>Diga olá e conte do que você precisa.</p>
                        </div>
                    <?php endif; ?>
                    <?php $lastDate = null; foreach ($messages as $m): ?>
                        <?php if ($m['data'] !== $lastDate): $lastDate = $m['data']; ?>
                            <div class="chat-day" data-day="<?= e($m['data']) ?>"><span><?= e(day_label($m['data'])) ?></span></div>
                        <?php endif; ?>
                        <div class="msg<?= $m['minha'] ? ' msg-out' : ' msg-in' ?>" data-id="<?= $m['id'] ?>">
                            <p><?= nl2br(e($m['conteudo'])) ?></p>
                            <span class="msg-time"><?= e($m['hora']) ?><?php if ($m['minha']): ?> <i class="fa-solid fa-check<?= $m['lida'] ? '-double' : '' ?>"></i><?php endif; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="chat-composer" data-chat-form>
                    <label class="sr-only" for="mensagem">Mensagem</label>
                    <textarea id="mensagem" name="conteudo" rows="1" maxlength="2000" placeholder="Escreva sua mensagem…" required data-chat-input></textarea>
                    <button class="btn btn-primary btn-icon" type="submit" aria-label="Enviar"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            <?php else: ?>
                <div class="chat-placeholder">
                    <i class="fa-regular fa-comments"></i>
                    <h2>Suas conversas</h2>
                    <p class="muted">Selecione uma conversa para ver as mensagens.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php if ($active): ?><script src="<?= asset('js/chat.js') ?>" defer></script><?php endif; ?>

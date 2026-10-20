<?php $hasUnread = (bool) array_filter($items, static fn ($n) => $n['lida_em'] === null); ?>
<div class="container member-page">
    <header class="member-head member-head-row">
        <div>
            <h1>Notificações</h1>
            <p class="muted">Pedidos, respostas e avaliações em um só lugar.</p>
        </div>
        <?php if ($hasUnread): ?>
            <form method="post" action="<?= url('/notificacoes/ler') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-check-double"></i> Marcar todas como lidas</button>
            </form>
        <?php endif; ?>
    </header>

    <?php if ($items): ?>
        <section class="card card-flush">
            <ul class="inbox">
                <?php foreach ($items as $n): ?>
                    <li>
                        <a class="inbox-item<?= $n['lida_em'] === null ? ' is-unread' : '' ?>" href="<?= url('/notificacoes/' . $n['id']) ?>">
                            <span class="notif-icon"><i class="fa-solid <?= e(Notification::ICONS[$n['tipo']] ?? 'fa-bell') ?>"></i></span>
                            <div class="inbox-body">
                                <div class="inbox-top">
                                    <strong><?= e($n['titulo']) ?></strong>
                                    <span class="muted small inbox-date"><?= e(time_ago($n['criado_em'])) ?></span>
                                </div>
                                <?php if ($n['mensagem']): ?><p class="muted"><?= e($n['mensagem']) ?></p><?php endif; ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-bell"></i>
            <h2>Nenhuma notificação</h2>
            <p>Quando houver novidades sobre seus agendamentos e avaliações, elas aparecem aqui.</p>
        </div>
    <?php endif; ?>
</div>

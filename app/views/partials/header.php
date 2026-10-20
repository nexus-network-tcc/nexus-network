<?php
Appointment::remindDueThrottled();
$user = Auth::user();
$isAdmin = ($user['tipo'] ?? null) === 'admin';
$unread = $user && !$isAdmin ? Conversation::unreadCount((int) $user['id']) : 0;
$notifications = $user && !$isAdmin ? Notification::unreadCount((int) $user['id']) : 0;
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= url('/') ?>" aria-label="Nexus Network, página inicial">
            <img src="<?= asset('img/logo.png') ?>" alt="" width="40" height="40">
            <span class="brand-text">Nexus <span>Network</span></span>
        </a>

        <nav class="main-nav" id="menu-principal" data-nav aria-label="Menu principal">
            <a class="nav-link<?= is_active('/') ?>" href="<?= url('/') ?>">Início</a>
            <a class="nav-link<?= is_active('/buscar') ?>" href="<?= url('/buscar') ?>">Buscar profissionais</a>
            <a class="nav-link<?= is_active('/sobre') ?>" href="<?= url('/sobre') ?>">Sobre</a>
            <div class="dropdown" data-dropdown>
                <button class="nav-link dropdown-toggle<?= is_active('/suporte') . is_active('/contato') ?>" type="button" aria-expanded="false" data-dropdown-toggle>
                    Ajuda <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="<?= url('/#como-funciona') ?>"><i class="fa-solid fa-route"></i> Como funciona</a>
                    <a href="<?= url('/suporte') ?>"><i class="fa-solid fa-life-ring"></i> Suporte</a>
                    <a href="<?= url('/contato') ?>"><i class="fa-solid fa-envelope"></i> Contato</a>
                </div>
            </div>

            <?php if (!$user): ?>
                <div class="nav-mobile-actions">
                    <a class="btn btn-ghost btn-block" href="<?= url('/entrar') ?>">Entrar</a>
                    <a class="btn btn-primary btn-block" href="<?= url('/cadastro') ?>">Criar conta</a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" type="button" data-tts-toggle aria-pressed="false" title="Leitura em voz alta">
                <i class="fa-solid fa-volume-high" aria-hidden="true"></i>
                <span class="sr-only">Ativar leitura em voz alta</span>
            </button>

            <?php if ($isAdmin): ?>
                <a class="icon-btn" href="<?= url('/admin') ?>" title="Painel de administração">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <span class="sr-only">Painel de administração</span>
                </a>
            <?php elseif ($user): ?>
                <a class="icon-btn" href="<?= url('/conversas') ?>" title="Conversas">
                    <i class="fa-solid fa-comments" aria-hidden="true"></i>
                    <?php if ($unread > 0): ?><span class="badge-dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
                    <span class="sr-only">Conversas<?= $unread ? " ({$unread} não lidas)" : '' ?></span>
                </a>
                <a class="icon-btn" href="<?= url('/notificacoes') ?>" title="Notificações" data-bell>
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    <span class="badge-dot" data-bell-count<?= $notifications > 0 ? '' : ' hidden' ?>><?= $notifications > 9 ? '9+' : $notifications ?></span>
                    <span class="sr-only">Notificações</span>
                </a>

            <?php endif; ?>

            <?php if ($user): ?>
                <div class="dropdown dropdown-end" data-dropdown>
                    <button class="user-chip" type="button" aria-expanded="false" data-dropdown-toggle>
                        <?= avatar($user['foto'], $user['nome'], 'sm') ?>
                        <span class="user-chip-name"><?= e(first_name($user['nome'])) ?></span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <strong><?= e($user['nome']) ?></strong>
                            <span><?= ['prestador' => 'Prestador de serviços', 'cliente' => 'Cliente', 'admin' => 'Administrador'][$user['tipo']] ?></span>
                        </div>
                        <?php if ($isAdmin): ?>
                            <a href="<?= url('/admin') ?>"><i class="fa-solid fa-chart-pie"></i> Painel</a>
                            <a href="<?= url('/admin/usuarios') ?>"><i class="fa-solid fa-users"></i> Usuários</a>
                            <a href="<?= url('/admin/avaliacoes') ?>"><i class="fa-solid fa-star"></i> Avaliações</a>
                            <a href="<?= url('/admin/contatos') ?>"><i class="fa-solid fa-envelope"></i> Contatos</a>
                        <?php else: ?>
                        <a href="<?= url('/perfil') ?>"><i class="fa-solid fa-user"></i> Meu perfil</a>
                        <?php if ($user['tipo'] === 'prestador'): ?>
                            <a href="<?= url('/prestador/' . $user['id']) ?>"><i class="fa-solid fa-id-card"></i> Ver perfil público</a>
                        <?php else: ?>
                            <a href="<?= url('/buscar') ?>"><i class="fa-solid fa-magnifying-glass"></i> Buscar serviços</a>
                        <?php endif; ?>
                        <a href="<?= url('/agendamentos') ?>"><i class="fa-solid fa-calendar-check"></i> <?= $user['tipo'] === 'prestador' ? 'Solicitações' : 'Meus agendamentos' ?></a>
                        <?php if ($user['tipo'] === 'cliente'): ?>
                            <a href="<?= url('/favoritos') ?>"><i class="fa-solid fa-heart"></i> Favoritos</a>
                        <?php endif; ?>
                        <a href="<?= url('/conversas') ?>"><i class="fa-solid fa-comments"></i> Conversas
                            <?php if ($unread): ?><span class="pill pill-green"><?= $unread ?></span><?php endif; ?>
                        </a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('/sair') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm hide-mobile" href="<?= url('/entrar') ?>">Entrar</a>
                <a class="btn btn-primary btn-sm hide-mobile" href="<?= url('/cadastro') ?>">Criar conta</a>
            <?php endif; ?>

            <button class="icon-btn nav-toggle" type="button" data-nav-toggle aria-controls="menu-principal" aria-expanded="false">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                <span class="sr-only">Abrir menu</span>
            </button>
        </div>
    </div>
</header>

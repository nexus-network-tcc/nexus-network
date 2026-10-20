<?php
$query = array_filter($filters);
$pageUrl = static fn (int $n) => url('/admin/usuarios', $query + ['pagina' => $n]);
$tipoLabel = ['cliente' => 'Cliente', 'prestador' => 'Prestador', 'admin' => 'Admin'];
?>
<div class="container admin-page">
    <?= partial('admin-nav', ['title' => 'Usuários', 'section' => $section]) ?>

    <form class="admin-filters" method="get" action="<?= url('/admin/usuarios') ?>" data-autosubmit>
        <label class="search-field admin-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only">Buscar</span>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nome, e-mail ou cidade">
        </label>
        <select class="input input-sm" name="tipo" aria-label="Tipo">
            <option value="">Todos os tipos</option>
            <?php foreach ($tipoLabel as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filters['tipo'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <select class="input input-sm" name="status" aria-label="Situação">
            <option value="">Todas as situações</option>
            <option value="ativo" <?= $filters['status'] === 'ativo' ? 'selected' : '' ?>>Ativos</option>
            <option value="suspenso" <?= $filters['status'] === 'suspenso' ? 'selected' : '' ?>>Suspensos</option>
            <option value="verificado" <?= $filters['status'] === 'verificado' ? 'selected' : '' ?>>Verificados</option>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
    </form>

    <p class="muted small"><?= $total ?> <?= $total === 1 ? 'usuário' : 'usuários' ?></p>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Tipo</th>
                    <th>Cidade</th>
                    <th>Cadastro</th>
                    <th>Situação</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $u): ?>
                    <tr class="<?= $u['suspenso_em'] ? 'is-muted' : '' ?>">
                        <td>
                            <div class="table-user">
                                <?= avatar($u['foto'], $u['nome'], 'sm') ?>
                                <div>
                                    <?php if ($u['tipo'] === 'prestador'): ?>
                                        <a href="<?= url('/prestador/' . $u['id']) ?>"><strong><?= e($u['nome']) ?></strong></a><?= verified_badge($u['verificado_em'] ?? null) ?>
                                    <?php else: ?>
                                        <strong><?= e($u['nome']) ?></strong>
                                    <?php endif; ?>
                                    <span class="muted small"><?= e($u['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="pill <?= $u['tipo'] === 'prestador' ? 'pill-green' : '' ?>"><?= $tipoLabel[$u['tipo']] ?></span>
                            <?php if ($u['profissao']): ?><span class="muted small block"><?= e($u['profissao']) ?></span><?php endif; ?>
                        </td>
                        <td><?= $u['cidade'] ? e($u['cidade']) . ', ' . e($u['estado']) : '<span class="muted">–</span>' ?></td>
                        <td><?= format_date($u['criado_em']) ?></td>
                        <td>
                            <?php if ($u['suspenso_em']): ?>
                                <span class="pill pill-danger"><i class="fa-solid fa-ban"></i> Suspenso</span>
                                <span class="muted small block">desde <?= format_date($u['suspenso_em']) ?></span>
                            <?php else: ?>
                                <span class="pill pill-green"><i class="fa-solid fa-circle-check"></i> Ativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($u['tipo'] !== 'admin'): ?>
                                <div class="row-actions">
                                    <?php if ($u['tipo'] === 'prestador'): ?>
                                        <?php if ($u['verificado_em']): ?>
                                            <form method="post" action="<?= url('/admin/usuarios/' . $u['id'] . '/desverificar') ?>" data-confirm="Remover o selo de verificado de <?= e($u['nome']) ?>?">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-certificate"></i> Tirar selo</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?= url('/admin/usuarios/' . $u['id'] . '/verificar') ?>">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-circle-check"></i> Verificar</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($u['suspenso_em']): ?>
                                        <form method="post" action="<?= url('/admin/usuarios/' . $u['id'] . '/reativar') ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-rotate-left"></i> Reativar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('/admin/usuarios/' . $u['id'] . '/suspender') ?>" data-confirm="Suspender <?= e($u['nome']) ?>? A conta deixa de aparecer no site e não consegue entrar.">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-ban"></i> Suspender</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= url('/admin/usuarios/' . $u['id'] . '/excluir') ?>" data-confirm="Excluir definitivamente a conta de <?= e($u['nome']) ?>? Conversas e avaliações também serão apagadas.">
                                        <?= csrf_field() ?>
                                        <button class="icon-btn icon-btn-danger" type="submit" title="Excluir conta"><i class="fa-solid fa-trash"></i><span class="sr-only">Excluir</span></button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="muted small">–</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="center muted">Nenhum usuário encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= partial('pagination', compact('page', 'pages', 'pageUrl')) ?>
</div>

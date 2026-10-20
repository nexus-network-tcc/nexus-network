<?php
$isProvider = $user['tipo'] === 'prestador';
$val = static fn (string $key) => old($key, $user[$key] ?? '');
$v = [
    'nome'      => $val('nome'),
    'email'     => $user['email'],
    'telefone'  => $val('telefone'),
    'cpf'       => $user['cpf'],
    'data_nasc' => $user['data_nasc'],
    'genero'    => $val('genero'),
    'estado'    => $val('estado'),
    'cidade'    => $val('cidade'),
];
if ($isProvider) {
    $hasOld = old('profissao', null) !== null;
    $v += [
        'profissao'      => $val('profissao'),
        'descricao'      => $val('descricao'),
        'preco_min'      => $val('preco_min'),
        'preco_max'      => $val('preco_max'),
        'preco_combinar' => $hasOld ? old('preco_combinar') : ($user['preco_min'] === null && $user['preco_max'] === null),
    ];
    $selected = $hasOld ? array_map('intval', (array) old('categorias', [])) : $selected;
}
$hasPassword = $user['senha'] !== null;
?>
<div class="container profile-page">
    <section class="profile-header card">
        <form method="post" action="<?= url('/perfil/foto') ?>" enctype="multipart/form-data" class="profile-photo" data-photo-form>
            <?= csrf_field() ?>
            <?= avatar($user['foto'], $user['nome'], 'xl') ?>
            <label class="profile-photo-btn" for="foto" title="Alterar foto">
                <i class="fa-solid fa-camera"></i><span class="sr-only">Alterar foto</span>
            </label>
            <input class="sr-only" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif" data-autosubmit-file>
        </form>
        <div class="profile-header-info">
            <span class="pill <?= $isProvider ? 'pill-green' : '' ?>">
                <i class="fa-solid <?= $isProvider ? 'fa-briefcase' : 'fa-user' ?>"></i> <?= $isProvider ? 'Prestador de serviços' : 'Cliente' ?>
            </span>
            <h1><?= e($user['nome']) ?></h1>
            <p class="muted"><?= $isProvider ? e($user['profissao']) . ' · ' : '' ?><?= e($user['cidade']) ?>, <?= e($user['estado']) ?> · desde <?= format_date($user['criado_em'], 'm/Y') ?></p>
            <?php if ($user['foto']): ?>
                <form method="post" action="<?= url('/perfil/foto') ?>" data-confirm="Remover sua foto de perfil?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="remover" value="1">
                    <button class="link-muted small" type="submit"><i class="fa-solid fa-trash"></i> Remover foto</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="profile-header-actions">
            <?php if ($isProvider): ?>
                <a class="btn btn-ghost" href="<?= url('/prestador/' . $user['id']) ?>"><i class="fa-solid fa-eye"></i> Ver perfil público</a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= url('/buscar') ?>"><i class="fa-solid fa-magnifying-glass"></i> Buscar serviços</a>
                <a class="btn btn-ghost" href="<?= url('/favoritos') ?>"><i class="fa-solid fa-heart"></i> Favoritos</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= url('/agendamentos') ?>"><i class="fa-solid fa-calendar-check"></i> <?= $isProvider ? 'Solicitações' : 'Agendamentos' ?></a>
            <a class="btn btn-ghost" href="<?= url('/conversas') ?>"><i class="fa-solid fa-comments"></i> Conversas</a>
        </div>
    </section>

    <div class="profile-layout">
        <nav class="profile-nav" aria-label="Seções do perfil">
            <a href="#dados"><i class="fa-solid fa-id-card"></i> Meus dados</a>
            <?php if ($isProvider): ?>
                <a href="#horarios"><i class="fa-solid fa-clock"></i> Horários</a>
                <a href="#galeria"><i class="fa-solid fa-images"></i> Galeria</a>
            <?php endif; ?>
            <a href="#seguranca"><i class="fa-solid fa-shield-halved"></i> Segurança</a>
            <a href="#avaliacoes"><i class="fa-solid fa-star"></i> <?= $isProvider ? 'Avaliações recebidas' : 'Minhas avaliações' ?></a>
            <a href="#zona-de-perigo" class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Excluir conta</a>
        </nav>

        <div class="profile-sections">
            <section class="card" id="dados">
                <h2 class="card-title">Meus dados</h2>
                <form method="post" action="<?= url('/perfil/dados') ?>" class="form-stack">
                    <?= csrf_field() ?>
                    <?= partial('form-personal', ['v' => $v, 'locked' => true]) ?>
                    <?php if ($isProvider): ?>
                        <h3 class="form-section-title">Perfil profissional</h3>
                        <?= partial('form-professional', ['v' => $v, 'categories' => $categories, 'selected' => $selected]) ?>
                    <?php endif; ?>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar alterações</button>
                    </div>
                </form>
            </section>

            <?php if ($isProvider): ?>
                <?php
                $oldSchedule = old('horario', null);
                $status = Schedule::status($schedule);
                ?>
                <section class="card" id="horarios">
                    <h2 class="card-title">Horários de atendimento
                        <?php if ($schedule): ?>
                            <span class="pill <?= $status['aberto'] ? 'pill-green' : '' ?>"><span class="status-dot <?= $status['aberto'] ? 'status-novo' : 'status-lido' ?>"></span> <?= e($status['texto']) ?></span>
                        <?php endif; ?>
                    </h2>
                    <?php if (!$schedule): ?>
                        <p class="notice"><i class="fa-solid fa-circle-info"></i> Informe seus horários: clientes podem filtrar por quem <strong>atende hoje</strong> ou está <strong>atendendo agora</strong>.</p>
                    <?php endif; ?>
                    <form method="post" action="<?= url('/perfil/horarios') ?>" class="form-stack" data-schedule-form>
                        <?= csrf_field() ?>
                        <div class="schedule-tools">
                            <button type="button" class="btn btn-ghost btn-sm" data-schedule-preset="comercial"><i class="fa-solid fa-briefcase"></i> Seg–Sex 08–18</button>
                            <button type="button" class="btn btn-ghost btn-sm" data-schedule-preset="comercial-sab"><i class="fa-solid fa-calendar-week"></i> Seg–Sex 08–18 + Sáb 08–12</button>
                            <button type="button" class="btn btn-ghost btn-sm" data-schedule-preset="limpar"><i class="fa-solid fa-eraser"></i> Limpar</button>
                        </div>
                        <div class="schedule-grid">
                            <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $day): ?>
                                <?php
                                if ($oldSchedule !== null) {
                                    $row = $oldSchedule[$day] ?? [];
                                    $active = !empty($row['ativo']);
                                    $start = $row['inicio'] ?? '08:00';
                                    $end = $row['fim'] ?? '18:00';
                                } else {
                                    $active = isset($schedule[$day]);
                                    $start = $schedule[$day]['inicio'] ?? '08:00';
                                    $end = $schedule[$day]['fim'] ?? '18:00';
                                }
                                ?>
                                <div class="schedule-row<?= $active ? '' : ' is-off' ?>" data-schedule-row data-day="<?= $day ?>">
                                    <label class="switch">
                                        <input type="checkbox" name="horario[<?= $day ?>][ativo]" value="1" <?= $active ? 'checked' : '' ?> data-schedule-toggle>
                                        <span class="switch-track"></span>
                                        <span class="schedule-day"><?= Schedule::DIAS[$day] ?></span>
                                    </label>
                                    <div class="schedule-times">
                                        <input class="input input-sm" type="time" name="horario[<?= $day ?>][inicio]" value="<?= e($start) ?>" aria-label="Início <?= Schedule::DIAS[$day] ?>" <?= $active ? '' : 'disabled' ?>>
                                        <span class="muted">às</span>
                                        <input class="input input-sm" type="time" name="horario[<?= $day ?>][fim]" value="<?= e($end) ?>" aria-label="Fim <?= Schedule::DIAS[$day] ?>" <?= $active ? '' : 'disabled' ?>>
                                    </div>
                                    <span class="schedule-closed muted small">Não atende</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?= error_for('horario') ?>
                        <div class="form-actions">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar horários</button>
                        </div>
                    </form>
                </section>

                <section class="card" id="galeria">
                    <h2 class="card-title">Galeria de trabalhos <span class="muted small">(<?= count($gallery) ?>/<?= Gallery::MAX_PHOTOS ?>)</span></h2>
                    <p class="muted small">Mostre trabalhos anteriores no seu perfil público. Aceitamos JPG, PNG, WebP ou GIF de até 5 MB.</p>
                    <?php if ($gallery): ?>
                        <ul class="gallery-grid">
                            <?php foreach ($gallery as $photo): ?>
                                <li>
                                    <img src="<?= e(url($photo['caminho'])) ?>" alt="<?= e($photo['legenda'] ?: 'Foto da galeria') ?>" loading="lazy">
                                    <?php if ($photo['legenda']): ?><span class="gallery-caption"><?= e($photo['legenda']) ?></span><?php endif; ?>
                                    <form method="post" action="<?= url('/perfil/galeria/' . $photo['id'] . '/excluir') ?>" data-confirm="Remover esta foto da galeria?">
                                        <?= csrf_field() ?>
                                        <button class="gallery-remove" type="submit" aria-label="Remover foto"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (count($gallery) < Gallery::MAX_PHOTOS): ?>
                        <form method="post" action="<?= url('/perfil/galeria') ?>" enctype="multipart/form-data" class="form-stack gallery-upload">
                            <?= csrf_field() ?>
                            <div class="form-grid">
                                <div class="field">
                                    <label class="label" for="foto_galeria">Nova foto</label>
                                    <input class="input" type="file" id="foto_galeria" name="foto_galeria" accept="image/jpeg,image/png,image/webp,image/gif" required>
                                </div>
                                <div class="field">
                                    <label class="label" for="legenda">Legenda (opcional)</label>
                                    <input class="input" id="legenda" name="legenda" maxlength="120" placeholder="Ex.: Instalação elétrica residencial">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload"></i> Adicionar à galeria</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="notice"><i class="fa-solid fa-circle-info"></i> Limite de fotos atingido. Remova alguma para adicionar outra.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="card" id="seguranca">
                <h2 class="card-title"><?= $hasPassword ? 'Trocar senha' : 'Criar senha' ?></h2>
                <?php if ($user['google_id']): ?>
                    <p class="muted small"><i class="fa-brands fa-google"></i> Sua conta está vinculada ao Google (<?= e($user['email']) ?>).</p>
                <?php endif; ?>
                <?php if (!$hasPassword): ?>
                    <p class="muted">Você entra com o Google. Se quiser, crie uma senha para entrar também com e-mail.</p>
                <?php endif; ?>
                <form method="post" action="<?= url('/perfil/senha') ?>" class="form-stack">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <?php if ($hasPassword): ?>
                            <div class="field field-full">
                                <label class="label" for="senha_atual">Senha atual</label>
                                <div class="input-password">
                                    <input class="input<?= has_error('senha_atual') ?>" id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required>
                                    <button type="button" class="input-icon-btn" data-toggle-password aria-label="Mostrar senha"><i class="fa-solid fa-eye"></i></button>
                                </div>
                                <?= error_for('senha_atual') ?>
                            </div>
                        <?php endif; ?>
                        <div class="field">
                            <label class="label" for="nova_senha">Nova senha</label>
                            <input class="input<?= has_error('senha') ?>" id="nova_senha" name="senha" type="password" autocomplete="new-password" required data-password>
                        </div>
                        <div class="field">
                            <label class="label" for="nova_senha_confirmacao">Confirmar nova senha</label>
                            <input class="input" id="nova_senha_confirmacao" name="senha_confirmacao" type="password" autocomplete="new-password" required data-password-confirm>
                        </div>
                        <div class="field-full">
                            <?= partial('password-rules') ?>
                            <?= error_for('senha') ?>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-key"></i> <?= $hasPassword ? 'Trocar senha' : 'Criar senha' ?></button>
                    </div>
                </form>
            </section>

            <section class="card" id="avaliacoes">
                <h2 class="card-title">
                    <?= $isProvider ? 'Avaliações recebidas' : 'Minhas avaliações' ?>
                    <?php if ($isProvider && $summary['total']): ?>
                        <span class="rating-inline"><?= stars($summary['media']) ?> <?= number_format($summary['media'], 1, ',', '') ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($reviews): ?>
                    <ul class="review-list">
                        <?php foreach ($reviews as $r): ?>
                            <li class="review">
                                <?php if ($isProvider): ?>
                                    <?= avatar($r['cliente_foto'], $r['cliente_nome'], 'md') ?>
                                <?php else: ?>
                                    <?= avatar($r['prestador_foto'], $r['prestador_nome'], 'md') ?>
                                <?php endif; ?>
                                <div class="review-body">
                                    <div class="review-head">
                                        <?php if ($isProvider): ?>
                                            <strong><?= e($r['cliente_nome']) ?></strong>
                                        <?php else: ?>
                                            <a href="<?= url('/prestador/' . $r['prestador_id']) ?>"><strong><?= e($r['prestador_nome']) ?></strong></a>
                                            <span class="muted small"><?= e($r['profissao']) ?></span>
                                        <?php endif; ?>
                                        <span class="muted small"><?= e(time_ago($r['criado_em'])) ?></span>
                                    </div>
                                    <?= stars((float) $r['nota']) ?>
                                    <p><?= nl2br(e($r['comentario'])) ?></p>
                                    <?php if (!$isProvider): ?>
                                        <form method="post" action="<?= url('/avaliacoes/' . $r['id'] . '/excluir') ?>" data-confirm="Excluir esta avaliação?">
                                            <?= csrf_field() ?>
                                            <button class="link-danger" type="submit"><i class="fa-solid fa-trash"></i> Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted"><?= $isProvider ? 'Você ainda não recebeu avaliações.' : 'Você ainda não avaliou nenhum profissional.' ?></p>
                <?php endif; ?>
            </section>

            <section class="card card-danger" id="zona-de-perigo">
                <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation"></i> Excluir conta</h2>
                <p class="muted">Esta ação é permanente: seu perfil, conversas e avaliações serão apagados.</p>
                <details class="danger-details" <?= isset(Session::errors()['exclusao']) ? 'open' : '' ?>>
                    <summary class="btn btn-danger-outline">Quero excluir minha conta</summary>
                    <form method="post" action="<?= url('/perfil/excluir') ?>" class="form-stack" data-confirm="Tem certeza? Esta ação não pode ser desfeita.">
                        <?= csrf_field() ?>
                        <?php if ($hasPassword): ?>
                            <div class="field">
                                <label class="label" for="senha_exclusao">Confirme sua senha</label>
                                <input class="input<?= has_error('exclusao') ?>" id="senha_exclusao" name="senha_confirmacao_exclusao" type="password" autocomplete="current-password" required>
                            </div>
                        <?php else: ?>
                            <div class="field">
                                <label class="label" for="confirmacao">Digite <strong>EXCLUIR</strong> para confirmar</label>
                                <input class="input<?= has_error('exclusao') ?>" id="confirmacao" name="confirmacao" required autocomplete="off">
                            </div>
                        <?php endif; ?>
                        <?= error_for('exclusao') ?>
                        <div class="form-actions">
                            <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Excluir definitivamente</button>
                        </div>
                    </form>
                </details>
            </section>
        </div>
    </div>
</div>

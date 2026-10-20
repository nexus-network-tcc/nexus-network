<?php
$isProvider = $user['tipo'] === 'prestador';
$groups = ['pendente' => [], 'aceito' => [], 'historico' => []];
foreach ($appointments as $a) {
    $groups[in_array($a['status'], ['pendente', 'aceito'], true) ? $a['status'] : 'historico'][] = $a;
}
$titles = [
    'pendente'  => $isProvider ? 'Aguardando sua resposta' : 'Aguardando resposta do profissional',
    'aceito'    => 'Confirmados',
    'historico' => 'Histórico',
];
?>
<div class="container member-page">
    <header class="member-head">
        <h1><?= $isProvider ? 'Solicitações recebidas' : 'Meus agendamentos' ?></h1>
        <p class="muted"><?= $isProvider ? 'Aceite, recuse e conclua os pedidos de orçamento dos seus clientes.' : 'Acompanhe seus pedidos de orçamento e serviços agendados.' ?></p>
    </header>

    <?php if (!$appointments): ?>
        <div class="empty-state">
            <i class="fa-regular fa-calendar"></i>
            <h2>Nada por aqui ainda</h2>
            <?php if ($isProvider): ?>
                <p>Quando um cliente pedir um orçamento, ele aparece aqui. Mantenha seus horários de atendimento atualizados.</p>
                <a class="btn btn-primary" href="<?= url('/perfil#horarios') ?>">Ajustar horários</a>
            <?php else: ?>
                <p>Encontre um profissional e solicite um orçamento direto no perfil dele.</p>
                <a class="btn btn-primary" href="<?= url('/buscar') ?>">Buscar profissionais</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($groups as $key => $list): ?>
        <?php if (!$list) continue; ?>
        <section class="appt-group">
            <h2 class="appt-group-title"><?= e($titles[$key]) ?> <span class="muted">(<?= count($list) ?>)</span></h2>
            <ul class="appt-list">
                <?php foreach ($list as $a): ?>
                    <?php
                    $other = $isProvider
                        ? ['nome' => $a['cliente_nome'], 'foto' => $a['cliente_foto']]
                        : ['nome' => $a['prestador_nome'], 'foto' => $a['prestador_foto']];
                    $past = strtotime($a['data_hora']) <= time();
                    ?>
                    <li class="card appt appt-<?= e($a['status']) ?>" id="agendamento-<?= (int) $a['id'] ?>">
                        <div class="appt-head">
                            <?= avatar($other['foto'], $other['nome'], 'md') ?>
                            <div class="appt-who">
                                <?php if ($isProvider): ?>
                                    <strong><?= e($other['nome']) ?></strong>
                                <?php else: ?>
                                    <a href="<?= url('/prestador/' . $a['prestador_id']) ?>"><strong><?= e($other['nome']) ?></strong></a>
                                    <span class="muted small"><?= e($a['profissao']) ?></span>
                                <?php endif; ?>
                                <span class="appt-when"><i class="fa-regular fa-calendar"></i> <?= e(Schedule::DIAS[(int) date('w', strtotime($a['data_hora']))]) ?>, <?= format_date($a['data_hora'], 'd/m/Y') ?> · <?= format_date($a['data_hora'], 'H:i') ?></span>
                            </div>
                            <span class="pill appt-status appt-status-<?= e($a['status']) ?>"><?= e(Appointment::STATUSES[$a['status']]) ?></span>
                        </div>

                        <p class="appt-desc"><?= nl2br(e($a['descricao'])) ?></p>

                        <?php if ($a['orcamento'] !== null): ?>
                            <p class="appt-quote"><i class="fa-solid fa-tag"></i> Orçamento: <strong><?= e(money((float) $a['orcamento'])) ?></strong></p>
                        <?php endif; ?>
                        <?php if ($a['resposta']): ?>
                            <p class="appt-reply"><i class="fa-solid fa-reply"></i> <?= nl2br(e($a['resposta'])) ?></p>
                        <?php endif; ?>

                        <div class="appt-actions">
                            <?php if ($isProvider && $a['status'] === 'pendente'): ?>
                                <details class="appt-respond">
                                    <summary class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Responder</summary>
                                    <form method="post" action="<?= url('/agendamentos/' . $a['id'] . '/aceitar') ?>" class="form-stack">
                                        <?= csrf_field() ?>
                                        <div class="field">
                                            <label class="label" for="orc-<?= (int) $a['id'] ?>">Valor do orçamento (opcional)</label>
                                            <input class="input input-sm" id="orc-<?= (int) $a['id'] ?>" name="orcamento" inputmode="decimal" placeholder="Ex.: 150,00">
                                        </div>
                                        <div class="field">
                                            <label class="label" for="resp-<?= (int) $a['id'] ?>">Mensagem para o cliente (opcional)</label>
                                            <textarea class="input input-sm" id="resp-<?= (int) $a['id'] ?>" name="resposta" rows="2" maxlength="500"></textarea>
                                        </div>
                                        <div class="appt-btn-row">
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-check"></i> Aceitar</button>
                                            <button class="btn btn-danger-outline btn-sm" type="submit" formaction="<?= url('/agendamentos/' . $a['id'] . '/recusar') ?>" data-confirm-click="Recusar este pedido?"><i class="fa-solid fa-xmark"></i> Recusar</button>
                                        </div>
                                    </form>
                                </details>
                            <?php endif; ?>

                            <?php if ($isProvider && $a['status'] === 'aceito' && $past): ?>
                                <form method="post" action="<?= url('/agendamentos/' . $a['id'] . '/concluir') ?>" data-confirm="Marcar este serviço como concluído?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-circle-check"></i> Marcar como concluído</button>
                                </form>
                            <?php elseif ($isProvider && $a['status'] === 'aceito'): ?>
                                <span class="muted small"><i class="fa-regular fa-clock"></i> Poderá ser concluído a partir do horário combinado.</span>
                            <?php endif; ?>

                            <?php if (!$isProvider && $a['status'] === 'concluido' && !$a['avaliado']): ?>
                                <a class="btn btn-primary btn-sm" href="<?= url('/prestador/' . $a['prestador_id'] . '#avaliar') ?>"><i class="fa-solid fa-star"></i> Avaliar</a>
                            <?php endif; ?>

                            <?php if (!$isProvider && $a['status'] !== 'cancelado'): ?>
                                <form method="post" action="<?= url('/conversas/iniciar') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="prestador_id" value="<?= (int) $a['prestador_id'] ?>">
                                    <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-comment-dots"></i> Conversar</button>
                                </form>
                            <?php endif; ?>

                            <?php if (!$isProvider && in_array($a['status'], ['pendente', 'aceito'], true)): ?>
                                <?php $free = $slots[$a['prestador_id']] ?? []; ?>
                                <details class="appt-respond">
                                    <summary class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar-day"></i> Reagendar</summary>
                                    <?php if ($free): ?>
                                        <form method="post" action="<?= url('/agendamentos/' . $a['id'] . '/reagendar') ?>" class="form-stack">
                                            <?= csrf_field() ?>
                                            <div class="field">
                                                <label class="label" for="novo-<?= (int) $a['id'] ?>">Novo dia e horário</label>
                                                <select class="input input-sm" id="novo-<?= (int) $a['id'] ?>" name="horario" required>
                                                    <option value="">Escolha um horário livre</option>
                                                    <?php foreach ($free as $date => $times): ?>
                                                        <optgroup label="<?= e(Schedule::DIAS_CURTOS[(int) date('w', strtotime($date))] . ', ' . format_date($date, 'd/m')) ?>">
                                                            <?php foreach ($times as $time): ?>
                                                                <option value="<?= e($date . '|' . $time) ?>"><?= e($time) ?></option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <p class="muted small">O profissional precisará confirmar o novo horário.</p>
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-calendar-check"></i> Pedir novo horário</button>
                                        </form>
                                    <?php else: ?>
                                        <p class="muted small">Não há outros horários livres nos próximos <?= Appointment::DAYS_AHEAD ?> dias. Combine pelo chat.</p>
                                    <?php endif; ?>
                                </details>
                            <?php endif; ?>

                            <?php if (in_array($a['status'], ['pendente', 'aceito'], true)): ?>
                                <form method="post" action="<?= url('/agendamentos/' . $a['id'] . '/cancelar') ?>" data-confirm="Cancelar este agendamento?">
                                    <?= csrf_field() ?>
                                    <button class="link-danger" type="submit"><i class="fa-solid fa-ban"></i> Cancelar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>

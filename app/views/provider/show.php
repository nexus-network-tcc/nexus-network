<?php
$user = Auth::user();
$min = $provider['preco_min'] !== null ? (float) $provider['preco_min'] : null;
$max = $provider['preco_max'] !== null ? (float) $provider['preco_max'] : null;
$phone = $provider['telefone'];
?>
<div class="container provider-page">
    <nav class="breadcrumb" aria-label="Você está em">
        <a href="<?= url('/') ?>">Início</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="<?= url('/buscar') ?>">Profissionais</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span><?= e($provider['nome']) ?></span>
    </nav>

    <section class="provider-hero card">
        <div class="provider-hero-main">
            <?= avatar($provider['foto'], $provider['nome'], 'xl') ?>
            <div class="provider-hero-info">
                <h1><?= e($provider['nome']) ?><?= verified_badge($provider['verificado_em'] ?? null, true) ?></h1>
                <p class="provider-role"><?= e($provider['profissao']) ?></p>
                <div class="rating-line">
                    <?php if ($summary['total']): ?>
                        <?= stars($summary['media']) ?>
                        <strong><?= number_format($summary['media'], 1, ',', '') ?></strong>
                        <a class="muted" href="#avaliacoes">(<?= $summary['total'] ?> <?= $summary['total'] === 1 ? 'avaliação' : 'avaliações' ?>)</a>
                    <?php else: ?>
                        <span class="muted">Ainda sem avaliações</span>
                    <?php endif; ?>
                </div>
                <ul class="meta-list">
                    <li><i class="fa-solid fa-location-dot"></i> <?= e($provider['cidade']) ?>, <?= e($provider['estado']) ?>
                        <?php if ($distance !== null): ?><strong class="text-green">· a <?= distance_label($distance) ?> de você</strong><?php endif; ?>
                    </li>
                    <li><i class="fa-regular fa-calendar"></i> Na Nexus desde <?= format_date($provider['criado_em'], 'm/Y') ?></li>
                    <?php if ($openStatus['aberto'] !== null): ?>
                        <li><a href="#horarios" class="open-status <?= $openStatus['aberto'] ? 'is-open' : 'is-closed' ?>">
                            <span class="status-dot <?= $openStatus['aberto'] ? 'status-novo' : 'status-lido' ?>"></span> <?= e($openStatus['texto']) ?>
                        </a></li>
                    <?php endif; ?>
                </ul>
                <?php if ($categories): ?>
                    <div class="chip-row">
                        <?php foreach ($categories as $c): ?>
                            <a class="chip" href="<?= url('/buscar', ['categoria' => [$c['id']]]) ?>"><i class="fa-solid <?= e($c['icone']) ?>"></i> <?= e($c['nome']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="provider-hero-actions">
            <?php if ($isOwner): ?>
                <span class="pill pill-green"><i class="fa-solid fa-eye"></i> Este é o seu perfil público</span>
                <a class="btn btn-primary btn-block" href="<?= url('/perfil') ?>"><i class="fa-solid fa-pen"></i> Editar perfil</a>
            <?php elseif (!$user): ?>
                <a class="btn btn-primary btn-block" href="<?= url('/entrar') ?>"><i class="fa-solid fa-comment-dots"></i> Entrar para conversar</a>
            <?php elseif ($user['tipo'] === 'cliente'): ?>
                <form method="post" action="<?= url('/conversas/iniciar') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="prestador_id" value="<?= (int) $provider['id'] ?>">
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-comment-dots"></i> Enviar mensagem</button>
                </form>
                <a class="btn btn-primary btn-block" href="#agendar"><i class="fa-solid fa-calendar-plus"></i> Solicitar orçamento</a>
                <form method="post" action="<?= url('/prestador/' . $provider['id'] . '/favoritar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost btn-block<?= $favorite ? ' is-favorite' : '' ?>" type="submit" aria-pressed="<?= $favorite ? 'true' : 'false' ?>">
                        <i class="fa-<?= $favorite ? 'solid' : 'regular' ?> fa-heart"></i> <?= $favorite ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>
                    </button>
                </form>
            <?php else: ?>
                <p class="muted small">Somente clientes podem iniciar conversas com prestadores.</p>
            <?php endif; ?>

            <?php if ($user && !$isOwner && $user['tipo'] !== 'admin'): ?>
                <?= partial('report-form', ['action' => url('/prestador/' . $provider['id'] . '/denunciar'), 'label' => 'Denunciar perfil', 'id' => 'perfil']) ?>
            <?php endif; ?>

            <?php if ($canSeeContact): ?>
                <a class="btn btn-ghost btn-block" href="https://wa.me/55<?= e($phone) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?= e(format_phone($phone)) ?></a>
                <a class="btn btn-ghost btn-block" href="mailto:<?= e($provider['email']) ?>"><i class="fa-solid fa-envelope"></i> Enviar e-mail</a>
            <?php elseif (!$user): ?>
                <p class="muted small center"><i class="fa-solid fa-lock"></i> Entre como cliente para pedir orçamento e conversar.</p>
            <?php elseif ($user['tipo'] === 'cliente'): ?>
                <div class="contact-locked">
                    <p class="small"><i class="fa-solid fa-lock"></i> <strong>Telefone e e-mail</strong> são liberados quando o profissional aceita o seu pedido de orçamento.</p>
                    <p class="muted small">Até lá, use o chat ou solicite um horário.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="provider-layout">
        <div class="provider-main">
            <section class="card">
                <h2 class="card-title">Sobre o serviço</h2>
                <?php if ($provider['descricao']): ?>
                    <p class="prose"><?= nl2br(e($provider['descricao'])) ?></p>
                <?php else: ?>
                    <p class="muted">Este profissional ainda não escreveu uma descrição.</p>
                <?php endif; ?>
            </section>

            <?php if ($gallery): ?>
                <section class="card" id="galeria">
                    <h2 class="card-title">Galeria de trabalhos <span class="muted">(<?= count($gallery) ?>)</span></h2>
                    <ul class="gallery-grid">
                        <?php foreach ($gallery as $photo): ?>
                            <li>
                                <a href="<?= e(url($photo['caminho'])) ?>" target="_blank" rel="noopener" title="<?= e($photo['legenda'] ?? 'Ampliar foto') ?>">
                                    <img src="<?= e(url($photo['caminho'])) ?>" alt="<?= e($photo['legenda'] ?: 'Trabalho de ' . $provider['nome']) ?>" loading="lazy">
                                </a>
                                <?php if ($photo['legenda']): ?><span class="gallery-caption"><?= e($photo['legenda']) ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <section class="card" id="avaliacoes">
                <h2 class="card-title">Avaliações <span class="muted">(<?= $summary['total'] ?>)</span></h2>
                <?php if ($reviews): ?>
                    <ul class="review-list">
                        <?php foreach ($reviews as $r): ?>
                            <li class="review">
                                <?= avatar($r['cliente_foto'], $r['cliente_nome'], 'md') ?>
                                <div class="review-body">
                                    <div class="review-head">
                                        <strong><?= e($r['cliente_nome']) ?></strong>
                                        <span class="muted small"><?= e(time_ago($r['criado_em'])) ?></span>
                                    </div>
                                    <?= stars((float) $r['nota']) ?>
                                    <p><?= nl2br(e($r['comentario'])) ?></p>
                                    <?php if ($user && $user['tipo'] !== 'admin' && (int) $user['id'] !== (int) $r['cliente_id']): ?>
                                        <?= partial('report-form', ['action' => url('/avaliacoes/' . $r['id'] . '/denunciar'), 'label' => 'Denunciar', 'id' => 'avaliacao-' . $r['id'], 'small' => true]) ?>
                                    <?php endif; ?>
                                    <?php if ($user && (int) $user['id'] === (int) $r['cliente_id']): ?>
                                        <form method="post" action="<?= url('/avaliacoes/' . $r['id'] . '/excluir') ?>" data-confirm="Excluir sua avaliação?">
                                            <?= csrf_field() ?>
                                            <button class="link-danger" type="submit"><i class="fa-solid fa-trash"></i> Excluir minha avaliação</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state empty-state-sm">
                        <i class="fa-regular fa-star"></i>
                        <p>Nenhuma avaliação ainda. Já contratou? Seja o primeiro a avaliar!</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="provider-side">
            <section class="card price-card">
                <span class="muted small">Faixa de preço</span>
                <strong class="price"><?= e(price_range($min, $max)) ?></strong>
                <p class="muted small">Valor final combinado direto com o profissional.</p>
            </section>

            <?php if ($user && $user['tipo'] === 'cliente'): ?>
                <section class="card" id="agendar">
                    <h2 class="card-title">Solicitar orçamento</h2>
                    <?php if ($slots): ?>
                        <form method="post" action="<?= url('/prestador/' . $provider['id'] . '/agendar') ?>" class="form-stack">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label class="label" for="horario">Dia e horário</label>
                                <select class="input<?= has_error('horario') ?>" id="horario" name="horario" required>
                                    <option value="">Escolha um horário livre</option>
                                    <?php foreach ($slots as $date => $times): ?>
                                        <optgroup label="<?= e(Schedule::DIAS_CURTOS[(int) date('w', strtotime($date))] . ', ' . format_date($date, 'd/m')) ?>">
                                            <?php foreach ($times as $time): ?>
                                                <option value="<?= e($date . '|' . $time) ?>" <?= old('horario') === $date . '|' . $time ? 'selected' : '' ?>><?= e($time) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <?= error_for('horario') ?>
                            </div>
                            <div class="field">
                                <label class="label" for="descricao">O que você precisa?</label>
                                <textarea class="input<?= has_error('descricao') ?>" id="descricao" name="descricao" rows="4" maxlength="500" minlength="10" required data-counter placeholder="Descreva o serviço, local e detalhes importantes…"><?= e(old('descricao')) ?></textarea>
                                <?= error_for('descricao') ?>
                            </div>
                            <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-calendar-plus"></i> Enviar pedido</button>
                            <p class="muted small">Atendimentos de 1 hora. O profissional responde com o valor do orçamento.</p>
                        </form>
                    <?php elseif (!$schedule): ?>
                        <p class="muted small">Este profissional ainda não informou horários. Combine pelo chat.</p>
                    <?php else: ?>
                        <p class="muted small">Não há horários livres nos próximos <?= Appointment::DAYS_AHEAD ?> dias. Tente combinar pelo chat.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="card" id="horarios">
                <h2 class="card-title">Horários de atendimento</h2>
                <?php if ($schedule): ?>
                    <p class="open-status <?= $openStatus['aberto'] ? 'is-open' : 'is-closed' ?>">
                        <span class="status-dot <?= $openStatus['aberto'] ? 'status-novo' : 'status-lido' ?>"></span> <?= e($openStatus['texto']) ?>
                    </p>
                    <ul class="week-list">
                        <?php $today = (int) date('w'); foreach ([1, 2, 3, 4, 5, 6, 0] as $day): ?>
                            <li class="<?= $day === $today ? 'is-today' : '' ?><?= isset($schedule[$day]) ? '' : ' is-closed' ?>">
                                <span><?= Schedule::DIAS[$day] ?><?= $day === $today ? ' (hoje)' : '' ?></span>
                                <span><?= isset($schedule[$day]) ? e($schedule[$day]['inicio'] . ' – ' . $schedule[$day]['fim']) : 'Não atende' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted small">O profissional ainda não informou os horários. Combine pelo chat.</p>
                <?php endif; ?>
            </section>

            <?php if ($mapa): ?>
                <section class="card">
                    <h2 class="card-title">Região de atendimento</h2>
                    <div class="mini-map" data-mini-map data-lat="<?= e($provider['latitude']) ?>" data-lon="<?= e($provider['longitude']) ?>"
                         role="img" aria-label="Mapa da região aproximada de <?= e($provider['cidade']) ?>"></div>
                    <p class="muted small"><i class="fa-solid fa-shield-halved"></i> Região aproximada de <?= e($provider['cidade']) ?>, <?= e($provider['estado']) ?>. O endereço exato não é exibido.</p>
                </section>
            <?php endif; ?>

            <?php if ($summary['total']): ?>
                <section class="card">
                    <h2 class="card-title">Resumo das notas</h2>
                    <div class="rating-summary">
                        <div class="rating-big">
                            <strong><?= number_format($summary['media'], 1, ',', '') ?></strong>
                            <?= stars($summary['media']) ?>
                            <span class="muted small"><?= $summary['total'] ?> <?= $summary['total'] === 1 ? 'avaliação' : 'avaliações' ?></span>
                        </div>
                        <ul class="rating-bars">
                            <?php foreach ($summary['distribuicao'] as $star => $count): ?>
                                <li>
                                    <span><?= $star ?> <i class="fa-solid fa-star"></i></span>
                                    <span class="bar"><span style="width: <?= $summary['total'] ? round($count / $summary['total'] * 100) : 0 ?>%"></span></span>
                                    <span class="muted"><?= $count ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card" id="avaliar">
                <h2 class="card-title">Avaliar profissional</h2>
                <?php if ($canReview): ?>
                    <form method="post" action="<?= url('/prestador/' . $provider['id'] . '/avaliar') ?>" class="form-stack">
                        <?= csrf_field() ?>
                        <fieldset>
                            <legend class="label">Sua nota</legend>
                            <div class="star-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="nota" id="nota<?= $i ?>" value="<?= $i ?>" <?= (int) old('nota') === $i ? 'checked' : '' ?> required>
                                    <label for="nota<?= $i ?>" title="<?= $i ?> estrela<?= $i > 1 ? 's' : '' ?>"><i class="fa-solid fa-star"></i><span class="sr-only"><?= $i ?> estrelas</span></label>
                                <?php endfor; ?>
                            </div>
                            <?= error_for('nota') ?>
                        </fieldset>
                        <div class="field">
                            <label class="label" for="comentario">Comentário</label>
                            <textarea class="input<?= has_error('comentario') ?>" id="comentario" name="comentario" rows="4" maxlength="500" minlength="10" required data-counter placeholder="Como foi o serviço? Pontualidade, qualidade, atendimento…"><?= e(old('comentario')) ?></textarea>
                            <?= error_for('comentario') ?>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-paper-plane"></i> Publicar avaliação</button>
                    </form>
                <?php elseif (!$user): ?>
                    <p class="muted">Faça login como cliente para avaliar este profissional.</p>
                    <a class="btn btn-ghost btn-block" href="<?= url('/entrar') ?>">Entrar</a>
                <?php elseif ($user['tipo'] !== 'cliente'): ?>
                    <p class="muted">Somente clientes podem avaliar profissionais.</p>
                <?php elseif ($hasReviewed): ?>
                    <p class="muted"><i class="fa-solid fa-circle-check text-green"></i> Você já avaliou este profissional. Obrigado!</p>
                <?php else: ?>
                    <p class="muted"><i class="fa-solid fa-lock"></i> Você poderá avaliar depois que este profissional concluir um serviço agendado com você.</p>
                    <a class="btn btn-ghost btn-block" href="#agendar">Solicitar orçamento</a>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>

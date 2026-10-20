<?php
$popular = ['Eletricista', 'Diarista', 'Encanador', 'Pedreiro', 'Técnico em informática', 'Maquiadora'];
$highlight = $featured[0] ?? null;
?>
<section class="hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><i class="fa-solid fa-bolt"></i> Serviços perto de você</span>
            <h1>Conectamos você ao <span class="text-gradient">profissional ideal</span></h1>
            <p class="lead">Encontre eletricistas, diaristas, professores, técnicos e muito mais. Compare avaliações reais, veja a distância e converse direto com o profissional.</p>

            <form class="search-bar" action="<?= url('/buscar') ?>" method="get" data-location-form>
                <label class="search-field">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="sr-only">Serviço ou profissional</span>
                    <input type="search" name="q" placeholder="Que serviço você precisa?" autocomplete="off">
                </label>
                <label class="search-field search-field-location">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <span class="sr-only">Cidade</span>
                    <input type="text" name="local" placeholder="Cidade, UF" value="<?= e($origin['label'] ?? '') ?>" data-location-input>
                    <input type="hidden" name="lat" data-lat>
                    <input type="hidden" name="lon" data-lon>
                    <button type="button" class="locate-btn" data-locate title="Usar minha localização">
                        <i class="fa-solid fa-location-crosshairs"></i><span class="sr-only">Usar minha localização</span>
                    </button>
                </label>
                <button class="btn btn-primary btn-lg" type="submit">Buscar</button>
            </form>

            <div class="popular">
                <span>Mais procurados:</span>
                <?php foreach ($popular as $term): ?>
                    <a class="chip" href="<?= url('/buscar', ['q' => $term]) ?>"><?= e($term) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="float-card float-card-main">
                <?php if ($highlight): ?>
                    <div class="float-card-head">
                        <?= avatar($highlight['foto'], $highlight['nome'], 'lg') ?>
                        <div>
                            <strong><?= e($highlight['nome']) ?></strong>
                            <span><?= e($highlight['profissao']) ?></span>
                        </div>
                    </div>
                    <div class="rating-line"><?= stars((float) $highlight['media']) ?> <strong><?= number_format((float) $highlight['media'], 1, ',', '') ?></strong> <span class="muted">(<?= (int) $highlight['total_avaliacoes'] ?> <?= (int) $highlight['total_avaliacoes'] === 1 ? 'avaliação' : 'avaliações' ?>)</span></div>
                    <div class="float-card-meta">
                        <span><i class="fa-solid fa-location-dot"></i> <?= e($highlight['cidade']) ?></span>
                        <span><i class="fa-solid fa-tag"></i> <?= e(price_range($highlight['preco_min'] !== null ? (float) $highlight['preco_min'] : null, $highlight['preco_max'] !== null ? (float) $highlight['preco_max'] : null)) ?></span>
                    </div>
                <?php else: ?>
                    <div class="float-card-head">
                        <span class="avatar avatar-lg avatar-initials">NN</span>
                        <div><strong>Seu profissional</strong><span>Avaliado pela comunidade</span></div>
                    </div>
                    <div class="rating-line"><?= stars(5) ?></div>
                <?php endif; ?>
            </div>
            <div class="float-card float-card-chat">
                <span class="bubble bubble-in">Olá! Você atende hoje à tarde?</span>
                <span class="bubble bubble-out">Atendo sim, posso passar às 15h 👍</span>
            </div>
            <div class="float-card float-card-badge">
                <i class="fa-solid fa-shield-halved"></i>
                <div><strong>Avaliações reais</strong><span>de clientes da plataforma</span></div>
            </div>
        </div>
    </div>
</section>

<section class="stats-strip">
    <div class="container stats-grid">
        <div class="stat"><strong><?= (int) ($counts['prestadores'] ?? 0) ?></strong><span>profissionais cadastrados</span></div>
        <div class="stat"><strong><?= (int) ($counts['cidades'] ?? 0) ?></strong><span>cidades atendidas</span></div>
        <div class="stat"><strong><?= (int) ($counts['avaliacoes'] ?? 0) ?></strong><span>avaliações publicadas</span></div>
        <div class="stat"><strong><?= $counts['media'] ? number_format((float) $counts['media'], 1, ',', '') : '–' ?><i class="fa-solid fa-star"></i></strong><span>nota média</span></div>
    </div>
</section>

<section class="section" id="servicos">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Categorias</span>
            <h2>O que você precisa hoje?</h2>
            <p>Escolha uma categoria e veja os profissionais disponíveis na sua região.</p>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $c): ?>
                <a class="category-card" href="<?= url('/buscar', ['categoria' => [$c['id']]]) ?>">
                    <span class="category-icon"><i class="fa-solid <?= e($c['icone']) ?>"></i></span>
                    <h3><?= e($c['nome']) ?></h3>
                    <p><?= e($c['descricao']) ?></p>
                    <span class="category-count"><?= (int) $c['total'] ?> <?= (int) $c['total'] === 1 ? 'profissional' : 'profissionais' ?> <i class="fa-solid fa-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="como-funciona">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Como funciona</span>
            <h2>Do problema ao serviço feito em 3 passos</h2>
        </div>
        <ol class="steps">
            <li class="step">
                <span class="step-number">1</span>
                <i class="fa-solid fa-magnifying-glass-location step-icon"></i>
                <h3>Busque</h3>
                <p>Digite o serviço e sua cidade. Filtre por categoria, distância e ordene pelos melhor avaliados ou menor preço.</p>
            </li>
            <li class="step">
                <span class="step-number">2</span>
                <i class="fa-solid fa-comments step-icon"></i>
                <h3>Converse</h3>
                <p>Veja o perfil completo e chame o profissional no chat para combinar data, detalhes e valores.</p>
            </li>
            <li class="step">
                <span class="step-number">3</span>
                <i class="fa-solid fa-star step-icon"></i>
                <h3>Contrate e avalie</h3>
                <p>Depois do serviço, deixe sua avaliação. Ela ajuda outros clientes e valoriza quem trabalha bem.</p>
            </li>
        </ol>
    </div>
</section>

<?php if ($featured): ?>
<section class="section">
    <div class="container">
        <div class="section-head section-head-row">
            <div>
                <span class="eyebrow">Destaques</span>
                <h2>Profissionais bem avaliados</h2>
            </div>
            <a class="btn btn-ghost" href="<?= url('/buscar', ['ordem' => 'avaliacao']) ?>">Ver todos <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="provider-grid">
            <?php foreach ($featured as $p): ?>
                <?= partial('provider-card', ['p' => $p]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container split-card">
        <div>
            <span class="eyebrow">Para profissionais</span>
            <h2>Você presta serviços? Seja encontrado por quem precisa de você.</h2>
            <p>Chega de depender só de panfletos e indicações. Crie seu perfil gratuito e apareça para clientes da sua região.</p>
            <ul class="check-list">
                <li><i class="fa-solid fa-circle-check"></i> Perfil com descrição, categorias e faixa de preço (ou “a combinar”)</li>
                <li><i class="fa-solid fa-circle-check"></i> Apareça nas buscas por distância</li>
                <li><i class="fa-solid fa-circle-check"></i> Converse com os clientes pelo chat</li>
                <li><i class="fa-solid fa-circle-check"></i> Construa sua reputação com avaliações</li>
            </ul>
            <a class="btn btn-primary btn-lg" href="<?= url('/cadastro', ['tipo' => 'prestador']) ?>">Cadastrar meus serviços</a>
        </div>
        <div class="split-card-visual" aria-hidden="true">
            <img src="<?= asset('img/logo.png') ?>" alt="">
        </div>
    </div>
</section>

<?php if ($reviews): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Depoimentos</span>
            <h2>Quem usa, recomenda</h2>
        </div>
        <div class="testimonial-grid">
            <?php foreach ($reviews as $r): ?>
                <figure class="testimonial">
                    <?= stars((float) $r['nota']) ?>
                    <blockquote>“<?= e($r['comentario']) ?>”</blockquote>
                    <figcaption>
                        <?= avatar($r['cliente_foto'], $r['cliente_nome'], 'sm') ?>
                        <div>
                            <strong><?= e($r['cliente_nome']) ?></strong>
                            <span>contratou: <?= e($r['profissao']) ?></span>
                        </div>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$faqAll = require ROOT_PATH . '/app/views/partials/faq-data.php';
$homeFaq = [
    $faqAll['clientes'][2][2],       // como peço um orçamento
    $faqAll['clientes'][2][4],       // como funcionam as avaliações
    $faqAll['clientes'][2][1],       // preciso de conta
    $faqAll['profissionais'][2][1],  // taxa
    $faqAll['profissionais'][2][2],  // receber pedidos
    $faqAll['conta'][2][0],          // senha
];
?>
<section class="section" id="faq">
    <div class="container faq-home">
        <div class="section-head">
            <span class="eyebrow">Perguntas frequentes</span>
            <h2>Tire suas dúvidas</h2>
        </div>
        <div class="faq-list">
            <?php foreach ($homeFaq as [$question, $answer]): ?>
                <details class="faq-item">
                    <summary><?= e($question) ?> <i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
                    <p><?= e($answer) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
        <p class="center muted"><a href="<?= url('/suporte') ?>">Ver todas as perguntas <i class="fa-solid fa-arrow-right"></i></a></p>
    </div>
</section>

<section class="section">
    <div class="container cta-band">
        <div>
            <h2>Pronto para começar?</h2>
            <p>Crie sua conta grátis em menos de 2 minutos.</p>
        </div>
        <div class="actions-row">
            <a class="btn btn-dark btn-lg" href="<?= url('/buscar') ?>">Buscar profissionais</a>
            <a class="btn btn-outline-dark btn-lg" href="<?= url('/cadastro', ['tipo' => 'prestador']) ?>">Oferecer serviços</a>
        </div>
    </div>
</section>

<?php
$team = [
    ['nome' => 'Nicolas Sandoval Pereira',     'papel' => 'CEO e desenvolvedor',  'foto' => 'nicolas.png', 'bio' => 'Coordena a visão do produto e o planejamento do projeto, além de atuar no desenvolvimento da plataforma.'],
    ['nome' => 'Gustavo Henrique Antonio Castro', 'papel' => 'Desenvolvedor',     'foto' => 'gustavo.png', 'bio' => 'Atua no desenvolvimento full-stack, com foco na experiência de clientes e prestadores dentro da plataforma.'],
    ['nome' => 'João Pedro de Souza Santos',   'papel' => 'Desenvolvedor',        'foto' => 'joao.jpeg',   'bio' => 'Atua no desenvolvimento e nos testes, ajudando a garantir que cada funcionalidade funcione do jeito certo.'],
];
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <span class="eyebrow">Sobre nós</span>
        <h1>Sobre a <span class="text-gradient">Nexus Network</span></h1>
        <p class="lead">Nascemos como Trabalho de Conclusão de Curso na Etec Prof. Dr. Antônio Eufrásio de Toledo, em Presidente Prudente, com uma ideia simples: facilitar o encontro entre quem precisa de um serviço e quem sabe fazê-lo.</p>
    </div>
</section>

<section class="section">
    <div class="container about-grid">
        <div>
            <h2>O problema que resolvemos</h2>
            <p>Muitos profissionais autônomos ainda dependem de panfletos, adesivos no carro e indicações para conseguir clientes. Do outro lado, quem precisa de um eletricista, uma diarista ou uma aula particular perde tempo procurando alguém de confiança.</p>
            <p>A Nexus Network centraliza essas informações: o prestador cria um perfil com seus serviços, região e preços, e o cliente encontra profissionais próximos, compara avaliações e conversa direto pelo chat.</p>
        </div>
        <div class="mvv">
            <article class="mvv-card">
                <i class="fa-solid fa-bullseye"></i>
                <h3>Missão</h3>
                <p>Conectar clientes a profissionais qualificados de forma rápida, transparente e segura.</p>
            </article>
            <article class="mvv-card">
                <i class="fa-solid fa-eye"></i>
                <h3>Visão</h3>
                <p>Ser a forma mais fácil de contratar serviços na sua cidade, fortalecendo a economia local.</p>
            </article>
            <article class="mvv-card">
                <i class="fa-solid fa-handshake"></i>
                <h3>Valores</h3>
                <p>Confiança, transparência, respeito e compromisso com quem trabalha e com quem contrata.</p>
            </article>
        </div>
    </div>
</section>

<section class="stats-strip">
    <div class="container stats-grid">
        <div class="stat"><strong><?= (int) ($counts['prestadores'] ?? 0) ?></strong><span>profissionais</span></div>
        <div class="stat"><strong><?= (int) ($counts['clientes'] ?? 0) ?></strong><span>clientes</span></div>
        <div class="stat"><strong><?= (int) ($counts['avaliacoes'] ?? 0) ?></strong><span>avaliações</span></div>
        <div class="stat"><strong><?= $counts['media'] ? number_format((float) $counts['media'], 1, ',', '') : '–' ?><i class="fa-solid fa-star"></i></strong><span>nota média</span></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Equipe</span>
            <h2>Quem faz a Nexus Network</h2>
            <p>Orientação: Prof. Flávio Claudino de Araújo</p>
        </div>
        <div class="team-grid">
            <?php foreach ($team as $member): ?>
                <article class="team-card">
                    <img src="<?= asset('img/team/' . $member['foto']) ?>" alt="Foto de <?= e($member['nome']) ?>" loading="lazy">
                    <h3><?= e($member['nome']) ?></h3>
                    <span class="pill pill-green"><?= e($member['papel']) ?></span>
                    <p><?= e($member['bio']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container cta-band">
        <div>
            <h2>Faça parte da comunidade</h2>
            <p>Encontre o profissional ideal ou divulgue os seus serviços.</p>
        </div>
        <div class="actions-row">
            <a class="btn btn-dark btn-lg" href="<?= url('/buscar') ?>">Buscar profissionais</a>
            <a class="btn btn-outline-dark btn-lg" href="<?= url('/contato') ?>">Fale conosco</a>
        </div>
    </div>
</section>

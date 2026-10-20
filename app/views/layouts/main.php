<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= !empty($title) ? e($title) . ' · ' : '' ?>Nexus Network</title>
    <meta name="description" content="Nexus Network conecta clientes a profissionais qualificados da sua região: busque por serviço, compare avaliações e converse direto pelo chat.">
    <meta name="theme-color" content="#000000">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <link rel="icon" type="image/png" href="<?= asset('img/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?php if (!empty($mapa)): ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body data-base="<?= e(url('/')) ?>">
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <?= partial('header') ?>

    <main id="conteudo" class="site-main">
        <?= $content ?>
    </main>

    <?= partial('footer') ?>
    <?= partial('flash') ?>

    <!-- VLibras: tradução para Libras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js" defer></script>
    <script>window.addEventListener('load', function () { if (window.VLibras) new window.VLibras.Widget('https://vlibras.gov.br/app'); });</script>

    <script src="<?= asset('js/app.js') ?>" defer></script>
    <?php if (!empty($mapa)): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js" defer></script>
        <script src="<?= asset('js/map.js') ?>" defer></script>
    <?php endif; ?>
</body>
</html>

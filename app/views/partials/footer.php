<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="<?= url('/') ?>">
                <img src="<?= asset('img/logo.png') ?>" alt="" width="40" height="40">
                <span class="brand-text">Nexus <span>Network</span></span>
            </a>
            <p>Conectamos quem precisa de um serviço a profissionais da sua região, com avaliações reais e conversa direta.</p>
        </div>

        <div>
            <h3>Para clientes</h3>
            <a href="<?= url('/buscar') ?>">Buscar profissionais</a>
            <a href="<?= url('/#como-funciona') ?>">Como funciona</a>
            <a href="<?= url('/cadastro', ['tipo' => 'cliente']) ?>">Criar conta de cliente</a>
        </div>

        <div>
            <h3>Para profissionais</h3>
            <a href="<?= url('/cadastro', ['tipo' => 'prestador']) ?>">Cadastrar meus serviços</a>
            <a href="<?= url('/suporte') ?>#profissionais">Dúvidas de prestadores</a>
            <a href="<?= url('/perfil') ?>">Meu perfil</a>
        </div>

        <div>
            <h3>Nexus Network</h3>
            <a href="<?= url('/sobre') ?>">Sobre nós</a>
            <a href="<?= url('/suporte') ?>">Suporte</a>
            <a href="<?= url('/contato') ?>">Contato</a>
            <a href="mailto:<?= e(Config::get('support_email')) ?>"><?= e(Config::get('support_email')) ?></a>
        </div>
    </div>

    <div class="container footer-bottom">
        <p>© <?= date('Y') ?> Nexus Network</p>
        <a href="<?= url('/privacidade') ?>">Termos e privacidade</a>
    </div>
</footer>

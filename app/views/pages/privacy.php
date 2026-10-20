<section class="page-hero page-hero-compact">
    <div class="container">
        <h1>Termos de uso e privacidade</h1>
        <p class="lead">Última atualização: <?= date('m/Y') ?></p>
    </div>
</section>

<section class="section section-tight">
    <div class="container prose-page card">
        <h2>1. O que é a Nexus Network</h2>
        <p>A Nexus Network é uma plataforma que aproxima clientes e prestadores de serviços. Não somos parte do contrato entre eles: preço, prazo e forma de pagamento são combinados diretamente entre cliente e profissional.</p>

        <h2>2. Dados que coletamos</h2>
        <ul>
            <li><strong>Cadastro:</strong> nome, e-mail, telefone, CPF, data de nascimento, gênero, estado, cidade e foto (opcional).</li>
            <li><strong>Prestadores:</strong> profissão, categorias, descrição e faixa de preço.</li>
            <li><strong>Uso:</strong> mensagens trocadas no chat, avaliações publicadas e mensagens enviadas pelo formulário de contato.</li>
            <li><strong>Localização:</strong> coordenadas aproximadas da sua cidade e, se você permitir, a localização do navegador, usadas apenas para calcular distâncias.</li>
        </ul>

        <h2>3. Como usamos os dados</h2>
        <p>Usamos seus dados para criar sua conta, exibir perfis de prestadores nas buscas, permitir conversas e avaliações, enviar códigos de recuperação de senha e responder contatos. O CPF serve para evitar contas duplicadas e nunca é exibido publicamente.</p>

        <h2>4. O que é público</h2>
        <p>Do prestador: nome, foto, profissão, categorias, descrição, cidade, faixa de preço e avaliações. Telefone e e-mail só são liberados ao cliente depois que o prestador aceita um pedido de orçamento dele. Do cliente: nome, foto e cidade aparecem junto das avaliações que ele publica.</p>

        <h2>5. Segurança</h2>
        <p>Senhas e códigos de recuperação são armazenados de forma criptografada. Formulários são protegidos contra envio por sites de terceiros e o acesso às conversas é restrito aos participantes.</p>

        <h2>6. Seus direitos (LGPD)</h2>
        <p>Você pode consultar e corrigir seus dados em “Meu perfil” e excluir sua conta a qualquer momento, o que apaga também suas conversas e avaliações. Para outras solicitações, escreva para <a href="mailto:<?= e(Config::get('support_email')) ?>"><?= e(Config::get('support_email')) ?></a>.</p>

        <h2>7. Regras de uso</h2>
        <ul>
            <li>Informe dados verdadeiros e mantenha-os atualizados.</li>
            <li>Avaliações devem refletir experiências reais e não podem conter ofensas.</li>
            <li>É proibido usar o chat para spam, golpes ou conteúdo ilegal. Contas que violarem essas regras podem ser removidas.</li>
        </ul>
    </div>
</section>

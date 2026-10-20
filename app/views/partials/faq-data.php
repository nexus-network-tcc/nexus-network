<?php
/**
 * Perguntas frequentes (usadas na página de suporte e na home).
 * Formato: grupo => [rótulo, ícone, [[pergunta, resposta], ...]]
 */
return [
    'clientes' => ['Para clientes', 'fa-user', [
        ['Como encontro um profissional?', 'Na página inicial ou em "Buscar profissionais", digite o serviço que precisa (ex.: eletricista) e sua cidade. Você pode filtrar por categoria, distância, nota e preço e ordenar pelos mais próximos, melhor avaliados ou menor preço.'],
        ['Preciso de conta para buscar?', 'Não. A busca e os perfis são abertos. Para conversar pelo chat, pedir orçamento e avaliar, é preciso criar uma conta de cliente (é grátis). O telefone e o e-mail do profissional são liberados quando ele aceita o seu pedido de orçamento.'],
        ['Como peço um orçamento?', 'No perfil do prestador, escolha um horário livre em "Solicitar orçamento" e descreva o serviço. Ele aceita ou recusa, pode informar o valor do orçamento e você acompanha tudo em "Meus agendamentos".'],
        ['Como falo com o profissional?', 'No perfil do prestador, clique em "Enviar mensagem". A conversa fica salva em "Conversas" e você recebe as respostas sem precisar recarregar a página.'],
        ['Como funcionam as avaliações?', 'Depois que o profissional marcar o serviço agendado como concluído, você pode avaliá-lo uma vez, com nota de 1 a 5 estrelas e um comentário. Assim todas as avaliações vêm de contratações reais.'],
        ['Posso guardar profissionais para depois?', 'Sim. Toque no coração do card ou do perfil para adicionar aos favoritos e encontre todos em "Favoritos", no menu da sua conta.'],
        ['Como a distância é calculada?', 'Usamos a cidade do seu cadastro ou a localização do navegador (se você permitir) e calculamos a distância em linha reta até a cidade do profissional.'],
    ]],
    'profissionais' => ['Para profissionais', 'fa-briefcase', [
        ['Como me cadastro como prestador?', 'Clique em "Criar conta", escolha "Sou prestador" e preencha seus dados, profissão, categorias, descrição e faixa de preço. Seu perfil passa a aparecer nas buscas na hora.'],
        ['A plataforma cobra alguma taxa?', 'Não. O cadastro e o uso são gratuitos. O valor do serviço é combinado diretamente entre você e o cliente.'],
        ['Como recebo pedidos de orçamento?', 'Informe seus horários de atendimento em "Meu perfil". Os clientes escolhem horários livres nesses períodos e você responde em "Solicitações", aceitando (com o valor do orçamento) ou recusando. Você é avisado pelo sino e por e-mail.'],
        ['Posso mostrar meus trabalhos?', 'Sim. Em "Meu perfil", na seção "Galeria", envie até 12 fotos de trabalhos anteriores. Elas aparecem no seu perfil público.'],
        ['Posso deixar o preço em aberto?', 'Sim. Marque "Preço a combinar" no cadastro ou no seu perfil. Você também pode informar só um valor mínimo.'],
        ['Como apareço mais nas buscas?', 'Mantenha o perfil completo: foto, galeria, descrição detalhada, categorias certas e cidade correta. Bons atendimentos geram boas avaliações, que melhoram sua posição na ordenação por avaliação.'],
        ['Posso iniciar conversa com um cliente?', 'As conversas são iniciadas pelos clientes. Quando alguém enviar uma mensagem, ela aparecerá em "Conversas" e você poderá responder.'],
    ]],
    'conta' => ['Conta e segurança', 'fa-shield-halved', [
        ['Esqueci minha senha. E agora?', 'Na tela de login, clique em "Esqueci minha senha", informe seu e-mail e digite o código de 6 dígitos que enviaremos. O código vale por 10 minutos.'],
        ['Posso entrar com o Google?', 'Sim. Use "Continuar com Google". Se já tiver conta com o mesmo e-mail, ela será vinculada automaticamente.'],
        ['Como altero meus dados?', 'Em "Meu perfil" você altera nome, telefone, gênero, cidade, foto e senha. E-mail, CPF e data de nascimento não podem ser alterados.'],
        ['Como excluo minha conta?', 'Em "Meu perfil", na seção "Excluir conta", confirme com sua senha. Todos os seus dados, conversas, agendamentos e avaliações são apagados.'],
        ['Meus dados estão seguros?', 'Senhas são guardadas criptografadas, telefone e e-mail do prestador só são liberados ao cliente depois que ele aceita um pedido de orçamento, e seguimos os princípios da LGPD. Veja os detalhes em "Termos e privacidade".'],
    ]],
];

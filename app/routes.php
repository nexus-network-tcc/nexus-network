<?php

/** @var Router $router */

// Páginas públicas
$router->get('/', 'PageController@home');
$router->get('/sobre', 'PageController@about');
$router->get('/suporte', 'PageController@support');
$router->get('/privacidade', 'PageController@privacy');
$router->get('/contato', 'PageController@contact');
$router->post('/contato', 'PageController@sendContact');

// Autenticação
$router->get('/entrar', 'AuthController@showLogin', ['guest']);
$router->post('/entrar', 'AuthController@login', ['guest']);
$router->post('/sair', 'AuthController@logout', ['auth']);
$router->get('/cadastro', 'RegisterController@show', ['guest']);
$router->post('/cadastro', 'RegisterController@store', ['guest']);
$router->get('/auth/google', 'GoogleAuthController@redirect', ['guest']);
$router->get('/auth/google/callback', 'GoogleAuthController@callback', ['guest']);

// Recuperação de senha
$router->get('/recuperar-senha', 'PasswordResetController@showEmail', ['guest']);
$router->post('/recuperar-senha', 'PasswordResetController@sendCode', ['guest']);
$router->get('/recuperar-senha/codigo', 'PasswordResetController@showCode', ['guest']);
$router->post('/recuperar-senha/codigo', 'PasswordResetController@verifyCode', ['guest']);
$router->get('/recuperar-senha/nova', 'PasswordResetController@showNew', ['guest']);
$router->post('/recuperar-senha/nova', 'PasswordResetController@saveNew', ['guest']);

// Busca e prestadores
$router->get('/buscar', 'SearchController@index');
$router->post('/localizacao', 'SearchController@saveLocation');
$router->get('/prestador/{id}', 'ProviderController@show');
$router->post('/prestador/{id}/avaliar', 'ReviewController@store', ['cliente']);
$router->post('/avaliacoes/{id}/excluir', 'ReviewController@destroy', ['cliente']);
$router->post('/prestador/{id}/denunciar', 'ReportController@provider', ['membro']);
$router->post('/avaliacoes/{id}/denunciar', 'ReportController@review', ['membro']);

// Orçamentos e agendamentos
$router->get('/agendamentos', 'AppointmentController@index', ['membro']);
$router->post('/prestador/{id}/agendar', 'AppointmentController@store', ['cliente']);
$router->post('/agendamentos/{id}/reagendar', 'AppointmentController@reschedule', ['cliente']);
$router->post('/agendamentos/{id}/aceitar','AppointmentController@accept', ['prestador']);
$router->post('/agendamentos/{id}/recusar', 'AppointmentController@decline', ['prestador']);
$router->post('/agendamentos/{id}/concluir', 'AppointmentController@complete', ['prestador']);
$router->post('/agendamentos/{id}/cancelar', 'AppointmentController@cancel', ['membro']);

// Favoritos
$router->get('/favoritos', 'FavoriteController@index', ['cliente']);
$router->post('/prestador/{id}/favoritar', 'FavoriteController@toggle', ['cliente']);

// Notificações
$router->get('/notificacoes', 'NotificationController@index', ['membro']);
$router->get('/notificacoes/{id}', 'NotificationController@open', ['membro']);
$router->post('/notificacoes/ler', 'NotificationController@readAll', ['membro']);
$router->get('/api/notificacoes', 'NotificationController@count', ['membro']);

// Conversas
$router->get('/conversas', 'ChatController@index', ['membro']);
$router->post('/conversas/iniciar', 'ChatController@start', ['cliente']);
$router->get('/conversas/{id}', 'ChatController@show', ['membro']);
$router->get('/api/conversas/{id}/mensagens', 'ChatController@messages', ['membro']);
$router->post('/api/conversas/{id}/mensagens', 'ChatController@send', ['membro']);

// Meu perfil
$router->get('/perfil', 'ProfileController@show', ['membro']);
$router->post('/perfil/dados', 'ProfileController@updateData', ['membro']);
$router->post('/perfil/foto', 'ProfileController@updatePhoto', ['membro']);
$router->post('/perfil/horarios', 'ProfileController@updateSchedule', ['prestador']);
$router->post('/perfil/galeria', 'GalleryController@store', ['prestador']);
$router->post('/perfil/galeria/{id}/excluir', 'GalleryController@destroy', ['prestador']);
$router->post('/perfil/senha','ProfileController@updatePassword', ['membro']);
$router->post('/perfil/excluir', 'ProfileController@destroy', ['membro']);

// Painel de administração
$router->get('/admin', 'AdminController@dashboard', ['admin']);
$router->get('/admin/usuarios', 'AdminController@users', ['admin']);
$router->post('/admin/usuarios/{id}/suspender', 'AdminController@suspend', ['admin']);
$router->post('/admin/usuarios/{id}/reativar', 'AdminController@reactivate', ['admin']);
$router->post('/admin/usuarios/{id}/excluir', 'AdminController@deleteUser', ['admin']);
$router->post('/admin/usuarios/{id}/verificar', 'AdminController@verify', ['admin']);
$router->post('/admin/usuarios/{id}/desverificar', 'AdminController@unverify', ['admin']);
$router->get('/admin/avaliacoes', 'AdminController@reviews', ['admin']);
$router->post('/admin/avaliacoes/{id}/excluir', 'AdminController@deleteReview', ['admin']);
$router->get('/admin/denuncias', 'AdminController@reports', ['admin']);
$router->post('/admin/denuncias/{id}/resolver', 'AdminController@resolveReport', ['admin']);
$router->get('/admin/registro', 'AdminController@logs', ['admin']);
$router->get('/admin/contatos','AdminController@contacts', ['admin']);
$router->get('/admin/contatos/{id}', 'AdminController@contact', ['admin']);
$router->post('/admin/contatos/{id}/responder', 'AdminController@reply', ['admin']);

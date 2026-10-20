-- Dados fictícios extras do banco de teste: selos de verificado, buscas e denúncias.
-- Incluído por gerar-teste.php; também pode ser importado sozinho no banco nexus_network_teste.

UPDATE prestador_perfis SET verificado_em = NOW() - INTERVAL 10 DAY
 WHERE usuario_id IN (SELECT prestador_id FROM (
        SELECT prestador_id FROM avaliacoes GROUP BY prestador_id
        HAVING COUNT(*) >= 3 AND AVG(nota) >= 4.3 ORDER BY AVG(nota) DESC, COUNT(*) DESC LIMIT 12) t);

-- 400 buscas nos últimos 60 dias; as categorias 1 e 4 são as mais procuradas.
INSERT INTO buscas (categoria_id, termo, resultados, criado_em)
SELECT CASE WHEN n % 11 < 3 THEN 1 WHEN n % 11 < 5 THEN 4 WHEN n % 11 = 5 THEN 3 WHEN n % 11 = 6 THEN 5
            WHEN n % 11 = 7 THEN 6 WHEN n % 11 = 8 THEN 2 WHEN n % 11 = 9 THEN 7 ELSE NULL END,
       CASE WHEN n % 11 = 10 THEN ELT(1 + n % 5, 'eletricista', 'diarista', 'aula de inglês', 'pintor', 'manicure') ELSE NULL END,
       1 + n % 9,
       NOW() - INTERVAL (n * 37 % 60) DAY - INTERVAL (n * 53 % 1440) MINUTE
FROM (SELECT a.n + b.n * 10 + c.n * 100 AS n
      FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
           (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
           (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) c) nums
WHERE n < 400;

-- Denúncias de exemplo: 3 abertas (2 em avaliações, 1 em perfil) e 1 já descartada.
INSERT INTO denuncias (denunciante_id, prestador_id, avaliacao_id, motivo, detalhes, status, resolvido_em, criado_em)
SELECT p.id, NULL, a.id, 'ofensivo', 'O comentário usa palavras ofensivas.', 'aberta', NULL, NOW() - INTERVAL 1 DAY
FROM (SELECT id, prestador_id FROM avaliacoes ORDER BY id LIMIT 1 OFFSET 4) a
JOIN (SELECT id FROM usuarios WHERE tipo = 'cliente' ORDER BY id LIMIT 1) p;

INSERT INTO denuncias (denunciante_id, prestador_id, avaliacao_id, motivo, detalhes, status, resolvido_em, criado_em)
SELECT a.prestador_id, NULL, a.id, 'falso', 'Nunca prestei serviço para essa pessoa.', 'aberta', NULL, NOW() - INTERVAL 3 HOUR
FROM (SELECT id, prestador_id FROM avaliacoes ORDER BY id LIMIT 1 OFFSET 9) a;

INSERT INTO denuncias (denunciante_id, prestador_id, avaliacao_id, motivo, detalhes, status, resolvido_em, criado_em)
SELECT c.id, p.id, NULL, 'golpe', 'Pediu pagamento adiantado e não apareceu.', 'aberta', NULL, NOW() - INTERVAL 2 DAY
FROM (SELECT id FROM usuarios WHERE tipo = 'cliente' ORDER BY id LIMIT 1 OFFSET 2) c
JOIN (SELECT id FROM usuarios WHERE tipo = 'prestador' ORDER BY id LIMIT 1 OFFSET 6) p;

INSERT INTO denuncias (denunciante_id, prestador_id, avaliacao_id, motivo, detalhes, status, resolvido_em, criado_em)
SELECT c.id, p.id, NULL, 'spam', 'Perfil com divulgação repetida.', 'descartada', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 6 DAY
FROM (SELECT id FROM usuarios WHERE tipo = 'cliente' ORDER BY id LIMIT 1 OFFSET 5) c
JOIN (SELECT id FROM usuarios WHERE tipo = 'prestador' ORDER BY id LIMIT 1 OFFSET 9) p;

-- Agendamentos de exemplo entre cliente@teste.com (id 1) e prestador@teste.com (id 2), em vários status.
INSERT INTO agendamentos (cliente_id, prestador_id, data_hora, descricao, orcamento, resposta, status, criado_em)
SELECT c.id, p.id, d.data_hora, d.descricao, d.orcamento, d.resposta, d.status, d.criado_em
FROM (SELECT id FROM usuarios WHERE email = 'cliente@teste.com') c
JOIN (SELECT id FROM usuarios WHERE email = 'prestador@teste.com') p
JOIN (
    SELECT TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '10:00:00') AS data_hora, 'Preciso trocar a fiação da cozinha e instalar duas tomadas novas.' AS descricao,
           NULL AS orcamento, NULL AS resposta, 'pendente' AS status, NOW() - INTERVAL 3 HOUR AS criado_em
    UNION ALL
    SELECT TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '14:00:00'), 'Instalação de chuveiro elétrico no banheiro social.',
           180.00, 'Posso levar o material. O valor inclui a mão de obra.', 'aceito', NOW() - INTERVAL 1 DAY
    UNION ALL
    SELECT TIMESTAMP(CURDATE() - INTERVAL 6 DAY, '09:00:00'), 'Revisão geral do quadro de luz.',
           220.00, 'Serviço combinado, chego no horário.', 'concluido', NOW() - INTERVAL 9 DAY
    UNION ALL
    SELECT TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '16:00:00'), 'Troca de lâmpadas e instalação de ventilador de teto.',
           NULL, 'Nesse horário não consigo, desculpe.', 'recusado', NOW() - INTERVAL 4 DAY
    UNION ALL
    SELECT TIMESTAMP(CURDATE() + INTERVAL 5 DAY, '11:00:00'), 'Orçamento para instalação de câmeras de segurança.',
           NULL, NULL, 'cancelado', NOW() - INTERVAL 2 DAY
) d;

-- Favoritos do cliente de teste.
INSERT IGNORE INTO favoritos (cliente_id, prestador_id)
SELECT c.id, p.id
FROM (SELECT id FROM usuarios WHERE email = 'cliente@teste.com') c
JOIN (SELECT id FROM usuarios WHERE tipo = 'prestador' AND email <> 'prestador@teste.com' ORDER BY id LIMIT 3) p;

-- Notificações de exemplo.
INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, lida_em, criado_em)
SELECT c.id, 'agendamento', 'Pedido aceito', 'Prestador Teste aceitou o seu horário com orçamento de R$ 180,00.', '/agendamentos', NULL, NOW() - INTERVAL 1 DAY
FROM usuarios c WHERE c.email = 'cliente@teste.com';
INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, lida_em, criado_em)
SELECT c.id, 'agendamento', 'Serviço concluído', 'Como foi com Prestador Teste? Deixe sua avaliação.', '/agendamentos', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 6 DAY
FROM usuarios c WHERE c.email = 'cliente@teste.com';
INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, lida_em, criado_em)
SELECT p.id, 'agendamento', 'Novo pedido de orçamento', 'Cliente Teste quer agendar uma visita.', '/agendamentos', NULL, NOW() - INTERVAL 3 HOUR
FROM usuarios p WHERE p.email = 'prestador@teste.com';

-- Registro de ações do admin (exemplos).
INSERT INTO admin_logs (admin_id, acao, alvo, detalhes, ip, criado_em)
SELECT a.id, l.acao, l.alvo, l.detalhes, '127.0.0.1', NOW() - INTERVAL l.dias DAY
FROM (SELECT id FROM usuarios WHERE tipo = 'admin' ORDER BY id LIMIT 1) a
JOIN (
    SELECT 'verificar' AS acao, 'Perfil de exemplo (#3, prestador)' AS alvo, NULL AS detalhes, 6 AS dias
    UNION ALL SELECT 'descartar_denuncia', 'Denúncia #4', 'Spam ou propaganda', 5
    UNION ALL SELECT 'suspender', 'Conta de exemplo (#20, prestador)', 'Denúncia #2', 3
    UNION ALL SELECT 'reativar', 'Conta de exemplo (#20, prestador)', NULL, 1
) l;

-- Nexus Network: BANCO DE TESTE (dados fictícios)
-- Gerado por database/gerar-teste.php em 16/10/2026 02:46
-- Cria o banco nexus_network_teste do zero. Todas as contas usam a senha Nexus@123.
-- Contas fixas: cliente@teste.com, prestador@teste.com e admin@teste.com

DROP DATABASE IF EXISTS nexus_network_teste;
CREATE DATABASE nexus_network_teste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexus_network_teste;

-- Nexus Network: estrutura do banco de dados
-- Selecione o banco no phpMyAdmin antes de importar (as tabelas existentes são recriadas).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS mensagens, conversas, avaliacoes, prestador_horarios, prestador_categorias, prestador_perfis,
    redefinicoes_senha, categorias, contatos, geocode_cache, denuncias, buscas,
    agendamentos, favoritos, notificacoes, prestador_fotos, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- Clientes, prestadores e administradores ficam na mesma tabela; "tipo" define o papel.
-- Dados pessoais são opcionais no banco porque o admin não os possui (o site exige para os demais).
CREATE TABLE usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo          ENUM('cliente', 'prestador', 'admin') NOT NULL,
    nome          VARCHAR(120) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    senha         VARCHAR(255) NULL COMMENT 'NULL para contas criadas só com Google',
    cpf           CHAR(11) NULL,
    data_nasc     DATE NULL,
    genero        VARCHAR(30) NULL,
    telefone      VARCHAR(11) NULL,
    estado        CHAR(2) NULL,
    cidade        VARCHAR(100) NULL,
    latitude      DECIMAL(10, 8) NULL,
    longitude     DECIMAL(11, 8) NULL,
    foto          VARCHAR(500) NULL,
    google_id     VARCHAR(64) NULL,
    suspenso_em   DATETIME NULL COMMENT 'Preenchido quando um admin suspende a conta',
    criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_cpf (cpf),
    UNIQUE KEY uq_usuarios_google (google_id),
    KEY idx_usuarios_tipo (tipo),
    KEY idx_usuarios_local (latitude, longitude)
) ENGINE=InnoDB;

-- Dados profissionais (apenas para usuários do tipo prestador).
-- preco_min/preco_max NULL = preço "a combinar".
CREATE TABLE prestador_perfis (
    usuario_id  INT UNSIGNED PRIMARY KEY,
    profissao   VARCHAR(80) NOT NULL,
    descricao   TEXT NULL,
    preco_min   DECIMAL(10, 2) NULL,
    preco_max   DECIMAL(10, 2) NULL,
    verificado_em DATETIME NULL COMMENT 'Selo de verificado, concedido por um admin',
    CONSTRAINT fk_perfil_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT ck_perfil_precos CHECK (preco_min IS NULL OR preco_max IS NULL OR preco_min <= preco_max)
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome   VARCHAR(60) NOT NULL,
    slug   VARCHAR(60) NOT NULL,
    icone  VARCHAR(40) NOT NULL,
    descricao VARCHAR(160) NOT NULL,
    UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE prestador_categorias (
    prestador_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (prestador_id, categoria_id),
    CONSTRAINT fk_pc_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Horários de atendimento do prestador (dia_semana: 0 = domingo ... 6 = sábado).
-- Sem linhas = horários não informados.
CREATE TABLE prestador_horarios (
    prestador_id INT UNSIGNED NOT NULL,
    dia_semana   TINYINT UNSIGNED NOT NULL,
    inicio       TIME NOT NULL,
    fim          TIME NOT NULL,
    PRIMARY KEY (prestador_id, dia_semana),
    CONSTRAINT fk_horario_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT ck_horario_dia CHECK (dia_semana BETWEEN 0 AND 6),
    CONSTRAINT ck_horario_periodo CHECK (fim > inicio)
) ENGINE=InnoDB;

-- Uma avaliação por cliente para cada prestador.
CREATE TABLE avaliacoes (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id   INT UNSIGNED NOT NULL,
    prestador_id INT UNSIGNED NOT NULL,
    nota         TINYINT UNSIGNED NOT NULL,
    comentario   VARCHAR(500) NOT NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_avaliacao (cliente_id, prestador_id),
    KEY idx_avaliacoes_prestador (prestador_id),
    CONSTRAINT fk_avaliacao_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT ck_avaliacao_nota CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- Uma conversa por par cliente/prestador.
CREATE TABLE conversas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id   INT UNSIGNED NOT NULL,
    prestador_id INT UNSIGNED NOT NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversa (cliente_id, prestador_id),
    KEY idx_conversas_prestador (prestador_id),
    CONSTRAINT fk_conversa_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversa_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mensagens (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversa_id  INT UNSIGNED NOT NULL,
    remetente_id INT UNSIGNED NOT NULL,
    conteudo     TEXT NOT NULL,
    lida         TINYINT(1) NOT NULL DEFAULT 0,
    enviada_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mensagens_conversa (conversa_id, id),
    CONSTRAINT fk_mensagem_conversa FOREIGN KEY (conversa_id) REFERENCES conversas (id) ON DELETE CASCADE,
    CONSTRAINT fk_mensagem_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Códigos de recuperação de senha (guardados como hash, com limite de tentativas).
CREATE TABLE redefinicoes_senha (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    codigo_hash VARCHAR(255) NOT NULL,
    tentativas  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    usado       TINYINT(1) NOT NULL DEFAULT 0,
    expira_em   DATETIME NOT NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_redefinicoes_usuario (usuario_id),
    CONSTRAINT fk_redefinicao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mensagens enviadas pela página Contato.
CREATE TABLE contatos (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(120) NOT NULL,
    email     VARCHAR(150) NOT NULL,
    telefone  VARCHAR(11) NULL,
    assunto   ENUM('duvida', 'problema', 'sugestao', 'parceria', 'outro') NOT NULL,
    mensagem  TEXT NOT NULL,
    status    ENUM('novo', 'lido', 'respondido') NOT NULL DEFAULT 'novo',
    resposta  TEXT NULL,
    respondido_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contatos_status (status)
) ENGINE=InnoDB;

-- Cache de coordenadas por cidade (evita consultas repetidas ao Nominatim).
CREATE TABLE geocode_cache (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cidade    VARCHAR(100) NOT NULL,
    estado    CHAR(2) NOT NULL,
    latitude  DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_geocode_cidade (cidade, estado)
) ENGINE=InnoDB;

-- Denúncias de perfis e avaliações, analisadas pelo admin.
-- Exatamente um entre prestador_id (perfil denunciado) e avaliacao_id (avaliação denunciada) é preenchido.
CREATE TABLE denuncias (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    denunciante_id INT UNSIGNED NOT NULL,
    prestador_id   INT UNSIGNED NULL,
    avaliacao_id   INT UNSIGNED NULL,
    motivo         ENUM('spam', 'ofensivo', 'falso', 'golpe', 'outro') NOT NULL,
    detalhes       VARCHAR(500) NULL,
    status         ENUM('aberta', 'resolvida', 'descartada') NOT NULL DEFAULT 'aberta',
    resolvido_em   DATETIME NULL,
    criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_denuncias_status (status, criado_em),
    KEY idx_denuncias_prestador (prestador_id),
    KEY idx_denuncias_avaliacao (avaliacao_id),
    CONSTRAINT fk_denuncia_denunciante FOREIGN KEY (denunciante_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_denuncia_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_denuncia_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Registro das buscas feitas no site (alimenta o gráfico de categorias mais buscadas do painel).
CREATE TABLE buscas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NULL,
    termo        VARCHAR(100) NULL,
    resultados   INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_buscas_data (criado_em),
    KEY idx_buscas_categoria (categoria_id),
    CONSTRAINT fk_busca_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Pedidos de orçamento/agendamento: o cliente escolhe um horário livre do prestador, que aceita ou recusa.
CREATE TABLE agendamentos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id   INT UNSIGNED NOT NULL,
    prestador_id INT UNSIGNED NOT NULL,
    data_hora    DATETIME NOT NULL COMMENT 'Início do horário escolhido (blocos de 1 hora)',
    descricao    VARCHAR(500) NOT NULL,
    orcamento    DECIMAL(10,2) NULL COMMENT 'Valor informado pelo prestador ao aceitar',
    resposta     VARCHAR(500) NULL,
    status       ENUM('pendente', 'aceito', 'recusado', 'concluido', 'cancelado') NOT NULL DEFAULT 'pendente',
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_agendamentos_prestador (prestador_id, data_hora),
    KEY idx_agendamentos_cliente (cliente_id, data_hora),
    CONSTRAINT fk_agendamento_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_agendamento_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Prestadores favoritados pelos clientes.
CREATE TABLE favoritos (
    cliente_id   INT UNSIGNED NOT NULL,
    prestador_id INT UNSIGNED NOT NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cliente_id, prestador_id),
    KEY idx_favoritos_prestador (prestador_id),
    CONSTRAINT fk_favorito_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorito_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Avisos exibidos no sino do cabeçalho.
CREATE TABLE notificacoes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo       VARCHAR(30) NOT NULL,
    titulo     VARCHAR(120) NOT NULL,
    mensagem   VARCHAR(255) NULL,
    link       VARCHAR(255) NULL,
    lida_em    DATETIME NULL,
    criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notificacoes_usuario (usuario_id, lida_em, id),
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Galeria de trabalhos do prestador.
CREATE TABLE prestador_fotos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prestador_id INT UNSIGNED NOT NULL,
    caminho      VARCHAR(500) NOT NULL,
    legenda      VARCHAR(120) NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fotos_prestador (prestador_id, id),
    CONSTRAINT fk_foto_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Nexus Network: dados base (necessários em qualquer instalação)
-- Importe depois do schema.sql.

SET NAMES utf8mb4;

INSERT INTO categorias (id, nome, slug, icone, descricao) VALUES
(1, 'Reparos e manutenção', 'reparos',    'fa-screwdriver-wrench', 'Eletricistas, encanadores e consertos em geral'),
(2, 'Reformas e construção', 'reformas',  'fa-trowel-bricks',      'Pedreiros, pintores e acabamento'),
(3, 'Limpeza',               'limpeza',   'fa-broom',              'Diaristas, faxinas e limpeza pós-obra'),
(4, 'Tecnologia',            'tecnologia','fa-laptop-code',        'Suporte técnico, redes e desenvolvimento'),
(5, 'Aulas e cursos',        'aulas',     'fa-graduation-cap',     'Reforço escolar, idiomas e música'),
(6, 'Beleza e bem-estar',    'beleza',    'fa-spa',                'Cabelo, maquiagem, estética e personal'),
(7, 'Eventos',               'eventos',   'fa-champagne-glasses',  'Fotografia, buffet, decoração e DJ'),
(8, 'Automotivo',            'automotivo','fa-car',                'Mecânica, funilaria e lavagem');

INSERT INTO geocode_cache (cidade, estado, latitude, longitude) VALUES
('Presidente Prudente',   'SP', -22.12560000, -51.38890000),
('Álvares Machado',       'SP', -22.07640000, -51.47220000),
('Regente Feijó',         'SP', -22.22140000, -51.30280000),
('Pirapozinho',           'SP', -22.27530000, -51.50000000),
('Martinópolis',          'SP', -22.14580000, -51.17080000),
('Presidente Venceslau',  'SP', -21.87610000, -51.84360000),
('Presidente Epitácio',   'SP', -21.76330000, -52.11560000),
('Santo Anastácio',       'SP', -21.97750000, -51.65220000),
('Assis',                 'SP', -22.66110000, -50.41220000),
('Marília',               'SP', -22.21390000, -49.94580000),
('São Paulo',             'SP', -23.55050000, -46.63330000),
('Campinas',              'SP', -22.90560000, -47.06080000);

INSERT INTO usuarios (id, tipo, nome, email, senha, cpf, data_nasc, genero, telefone, estado, cidade, latitude, longitude, criado_em, suspenso_em) VALUES
(1, 'cliente', 'Cliente Teste', 'cliente@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10000791989', '1994-01-20', 'Feminino', '18991418707', 'SP', 'Presidente Prudente', -22.11440000, -51.38510000, NOW() - INTERVAL 30 DAY, NULL),
(2, 'prestador', 'Prestador Teste', 'prestador@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10001583816', '1980-04-10', 'Masculino', '18975091823', 'SP', 'Presidente Prudente', -22.13120000, -51.39380000, NOW() - INTERVAL 30 DAY, NULL),
(3, 'prestador', 'João Costa Pereira', 'joao.pereira3@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10002375753', '1985-02-17', 'Masculino', '18934777186', 'SP', 'Assis', -22.64070000, -50.43200000, NOW() - INTERVAL 269 DAY, NULL),
(4, 'prestador', 'Diego Rocha Ferreira', 'diego.ferreira4@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10003167690', '2000-01-24', 'Masculino', '18930618355', 'SP', 'Martinópolis', -22.14880000, -51.14700000, NOW() - INTERVAL 216 DAY, NULL),
(5, 'prestador', 'Mariana Ferreira Rodrigues', 'mariana.rodrigues5@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10003959520', '1977-02-01', 'Feminino', '18964262357', 'SP', 'Presidente Prudente', -22.10630000, -51.36700000, NOW() - INTERVAL 385 DAY, NULL),
(6, 'prestador', 'William Mendes Cardoso', 'william.cardoso6@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10004751400', '1987-01-16', 'Masculino', '18985057994', 'SP', 'Presidente Epitácio', -21.75050000, -52.09860000, NOW() - INTERVAL 315 DAY, NULL),
(7, 'prestador', 'Daniela Araújo Barbosa', 'daniela.barbosa7@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10005543339', '1972-03-26', 'Feminino', '18914187608', 'SP', 'Presidente Prudente', -22.14780000, -51.39570000, NOW() - INTERVAL 331 DAY, NULL),
(8, 'prestador', 'Daniela Barbosa Cardoso', 'daniela.cardoso8@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10006335276', '1966-03-05', 'Feminino', '18928329893', 'SP', 'Marília', -22.20580000, -49.93460000, NOW() - INTERVAL 286 DAY, NULL),
(9, 'prestador', 'Juliana Gomes Teixeira', 'juliana.teixeira9@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10007127103', '1988-08-26', 'Feminino', '18922329745', 'SP', 'Presidente Prudente', -22.12570000, -51.40400000, NOW() - INTERVAL 247 DAY, NULL),
(10, 'prestador', 'Samuel Martins Martins', 'samuel.martins10@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10007919042', '1966-08-12', 'Masculino', '18934230643', 'SP', 'Álvares Machado', -22.07550000, -51.49410000, NOW() - INTERVAL 162 DAY, NULL),
(11, 'prestador', 'Marcos Pereira Rodrigues', 'marcos.rodrigues11@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10008710961', '1988-04-13', 'Masculino', '18913346888', 'SP', 'Regente Feijó', -22.24470000, -51.32770000, NOW() - INTERVAL 171 DAY, NULL),
(12, 'prestador', 'Patrícia Santos Araújo', 'patricia.araujo12@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10009502807', '1980-09-20', 'Feminino', '18937642512', 'SP', 'Presidente Prudente', -22.10880000, -51.38250000, NOW() - INTERVAL 246 DAY, NULL),
(13, 'prestador', 'Fernanda Ribeiro Moreira', 'fernanda.moreira13@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10010294708', '2000-04-07', 'Feminino', '11948777097', 'SP', 'São Paulo', -23.53520000, -46.65150000, NOW() - INTERVAL 61 DAY, NULL),
(14, 'prestador', 'Marcos Martins Pereira', 'marcos.pereira14@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10011086637', '1980-11-28', 'Masculino', '11979623331', 'SP', 'São Paulo', -23.52640000, -46.65500000, NOW() - INTERVAL 373 DAY, NULL),
(15, 'prestador', 'Eduardo Silva Martins', 'eduardo.martins15@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10011878576', '1978-11-23', 'Masculino', '18936956156', 'SP', 'Presidente Venceslau', -21.89680000, -51.83700000, NOW() - INTERVAL 143 DAY, NULL),
(16, 'prestador', 'Paulo Gomes Teixeira', 'paulo.teixeira16@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10012670448', '1995-12-28', 'Masculino', '18963538350', 'SP', 'Santo Anastácio', -21.96500000, -51.64870000, NOW() - INTERVAL 342 DAY, NULL),
(17, 'prestador', 'Larissa Cardoso Ribeiro', 'larissa.ribeiro17@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10013462385', '1971-07-09', 'Feminino', '18977592868', 'SP', 'Presidente Prudente', -22.12810000, -51.40250000, NOW() - INTERVAL 179 DAY, NULL),
(18, 'prestador', 'Carlos Carvalho Mendes', 'carlos.mendes18@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10014254212', '1971-03-01', 'Masculino', '11912237316', 'SP', 'São Paulo', -23.56280000, -46.65110000, NOW() - INTERVAL 172 DAY, NULL),
(19, 'prestador', 'Carlos Rodrigues Araújo', 'carlos.araujo19@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10015046150', '1976-05-27', 'Masculino', '18955472553', 'SP', 'Presidente Venceslau', -21.89100000, -51.82330000, NOW() - INTERVAL 228 DAY, NULL),
(20, 'prestador', 'Rafael Almeida Oliveira', 'rafael.oliveira20@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10015838099', '1981-04-28', 'Masculino', '18971986163', 'SP', 'Santo Anastácio', -21.96520000, -51.66760000, NOW() - INTERVAL 23 DAY, NULL),
(21, 'prestador', 'Lucas Rodrigues Sandoval', 'lucas.sandoval21@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10016629973', '1976-03-19', 'Masculino', '18964500566', 'SP', 'Presidente Prudente', -22.11660000, -51.38650000, NOW() - INTERVAL 36 DAY, NULL),
(22, 'prestador', 'Thiago Moreira Nascimento', 'thiago.nascimento22@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10017421845', '1983-09-10', 'Masculino', '18986498180', 'SP', 'Presidente Prudente', -22.10300000, -51.39390000, NOW() - INTERVAL 373 DAY, NULL),
(23, 'prestador', 'Samuel Oliveira Lima', 'samuel.lima23@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10018213782', '1998-04-18', 'Masculino', '18990228172', 'SP', 'Regente Feijó', -22.22600000, -51.31630000, NOW() - INTERVAL 321 DAY, NULL),
(24, 'prestador', 'Gustavo Araújo Oliveira', 'gustavo.oliveira24@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10019005610', '1992-07-11', 'Masculino', '18996045265', 'SP', 'Assis', -22.68390000, -50.40200000, NOW() - INTERVAL 275 DAY, NULL),
(25, 'prestador', 'Yasmin Moreira Moreira', 'yasmin.moreira25@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10019797532', '1994-08-27', 'Feminino', '18915213262', 'SP', 'Álvares Machado', -22.08720000, -51.45380000, NOW() - INTERVAL 332 DAY, NULL),
(26, 'prestador', 'Vanessa Araújo Barbosa', 'vanessa.barbosa26@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10020589450', '2003-06-12', 'Feminino', '18914722512', 'SP', 'Presidente Prudente', -22.13470000, -51.39790000, NOW() - INTERVAL 15 DAY, NULL),
(27, 'prestador', 'Gustavo Oliveira Barbosa', 'gustavo.barbosa27@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10021381321', '1992-01-13', 'Masculino', '18995918659', 'SP', 'Presidente Prudente', -22.12350000, -51.38880000, NOW() - INTERVAL 121 DAY, NULL),
(28, 'prestador', 'Diego Pereira Pereira', 'diego.pereira28@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10022173269', '1975-02-16', 'Masculino', '18952303565', 'SP', 'Presidente Prudente', -22.10350000, -51.38340000, NOW() - INTERVAL 398 DAY, NULL),
(29, 'prestador', 'Ana Santos Martins', 'ana.martins29@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10022965106', '1984-11-21', 'Feminino', '18912424407', 'SP', 'Presidente Venceslau', -21.87370000, -51.83420000, NOW() - INTERVAL 301 DAY, NULL),
(30, 'prestador', 'Eduarda Ferreira Ribeiro', 'eduarda.ribeiro30@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10023757035', '1967-07-05', 'Feminino', '18952397619', 'SP', 'Presidente Prudente', -22.10890000, -51.40480000, NOW() - INTERVAL 106 DAY, NULL),
(31, 'prestador', 'Isabela Souza Araújo', 'isabela.araujo31@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10024548910', '1990-03-27', 'Feminino', '18936288411', 'SP', 'Presidente Prudente', -22.14600000, -51.38870000, NOW() - INTERVAL 300 DAY, NULL),
(32, 'prestador', 'André Costa Silva', 'andre.silva32@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10025340891', '1987-08-27', 'Masculino', '18933515826', 'SP', 'Assis', -22.67030000, -50.42550000, NOW() - INTERVAL 169 DAY, NULL),
(33, 'prestador', 'Larissa Almeida Nascimento', 'larissa.nascimento33@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10026132729', '1977-10-03', 'Feminino', '18973993199', 'SP', 'Pirapozinho', -22.29020000, -51.52500000, NOW() - INTERVAL 330 DAY, NULL),
(34, 'prestador', 'Juliana Castro Castro', 'juliana.castro34@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10026924668', '2005-11-26', 'Feminino', '18942626173', 'SP', 'Pirapozinho', -22.27170000, -51.48700000, NOW() - INTERVAL 131 DAY, NULL),
(35, 'prestador', 'Luana Martins Rodrigues', 'luana.rodrigues35@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10027716503', '1998-08-11', 'Feminino', '18964010953', 'SP', 'Presidente Epitácio', -21.78420000, -52.11640000, NOW() - INTERVAL 354 DAY, NULL),
(36, 'prestador', 'Otávio Oliveira Lima', 'otavio.lima36@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10028508432', '1965-04-07', 'Masculino', '18975948628', 'SP', 'Martinópolis', -22.12960000, -51.16250000, NOW() - INTERVAL 359 DAY, NULL),
(37, 'prestador', 'Vanessa Moreira Gomes', 'vanessa.gomes37@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10029300304', '2002-01-09', 'Feminino', '18911818733', 'SP', 'Presidente Venceslau', -21.89780000, -51.83620000, NOW() - INTERVAL 162 DAY, NULL),
(38, 'prestador', 'Camila Castro Santos', 'camila.santos38@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10030092205', '1977-04-20', 'Feminino', '18982489820', 'SP', 'Presidente Prudente', -22.14110000, -51.40840000, NOW() - INTERVAL 115 DAY, NULL),
(39, 'prestador', 'Patrícia Lima Souza', 'patricia.souza39@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10030884144', '1989-04-01', 'Feminino', '18921141877', 'SP', 'Presidente Prudente', -22.13930000, -51.40100000, NOW() - INTERVAL 281 DAY, NULL),
(40, 'prestador', 'Henrique Barbosa Cardoso', 'henrique.cardoso40@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10031676081', '1968-03-02', 'Masculino', '18960545003', 'SP', 'Presidente Prudente', -22.10450000, -51.37230000, NOW() - INTERVAL 163 DAY, NULL),
(41, 'prestador', 'Gustavo Mendes Santos', 'gustavo.santos41@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10032467966', '1998-08-03', 'Masculino', '11963934628', 'SP', 'São Paulo', -23.54130000, -46.65750000, NOW() - INTERVAL 376 DAY, NULL),
(42, 'prestador', 'Lucas Nascimento Carvalho', 'lucas.carvalho42@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10033259801', '1979-10-18', 'Masculino', '18988306609', 'SP', 'Presidente Prudente', -22.14690000, -51.39750000, NOW() - INTERVAL 111 DAY, NOW() - INTERVAL 7 DAY),
(43, 'cliente', 'Tatiane Cardoso Sandoval', 'tatiane.sandoval43@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10034051775', '2003-08-08', 'Feminino', '11974146571', 'SP', 'São Paulo', -23.56000000, -46.65250000, NOW() - INTERVAL 208 DAY, NULL),
(44, 'cliente', 'Natália Ribeiro Rocha', 'natalia.rocha44@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10034843604', '1966-06-27', 'Feminino', '18971490404', 'SP', 'Presidente Prudente', -22.12530000, -51.39350000, NOW() - INTERVAL 383 DAY, NULL),
(45, 'cliente', 'Patrícia Cardoso Teixeira', 'patricia.teixeira45@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10035635541', '1999-02-19', 'Feminino', '18950471229', 'SP', 'Presidente Prudente', -22.12140000, -51.37990000, NOW() - INTERVAL 180 DAY, NULL),
(46, 'cliente', 'Nicolas Nascimento Mendes', 'nicolas.mendes46@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10036427489', '1975-05-23', 'Masculino', '18999332338', 'SP', 'Presidente Prudente', -22.11560000, -51.39220000, NOW() - INTERVAL 301 DAY, NULL),
(47, 'cliente', 'Paulo Castro Nascimento', 'paulo.nascimento47@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10037219316', '2000-08-15', 'Masculino', '18915253568', 'SP', 'Álvares Machado', -22.09690000, -51.46850000, NOW() - INTERVAL 194 DAY, NULL),
(48, 'cliente', 'Luana Souza Teixeira', 'luana.teixeira48@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10038011298', '1994-09-10', 'Feminino', '18911007230', 'SP', 'Presidente Prudente', -22.12330000, -51.36940000, NOW() - INTERVAL 180 DAY, NULL),
(49, 'cliente', 'Sofia Gomes Araújo', 'sofia.araujo49@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10038803127', '1966-02-27', 'Feminino', '18992918905', 'SP', 'Presidente Epitácio', -21.77190000, -52.09630000, NOW() - INTERVAL 209 DAY, NULL),
(50, 'cliente', 'Ana Souza Rocha', 'ana.rocha50@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10039595048', '1966-02-05', 'Feminino', '11985388297', 'SP', 'São Paulo', -23.56570000, -46.63820000, NOW() - INTERVAL 153 DAY, NULL),
(51, 'cliente', 'Beatriz Nascimento Rocha', 'beatriz.rocha51@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10040386902', '1983-11-01', 'Feminino', '18976730235', 'SP', 'Presidente Prudente', -22.13380000, -51.41290000, NOW() - INTERVAL 327 DAY, NULL),
(52, 'cliente', 'Lucas Cardoso Almeida', 'lucas.almeida52@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10041178840', '2002-01-10', 'Masculino', '18978324695', 'SP', 'Marília', -22.19550000, -49.93420000, NOW() - INTERVAL 214 DAY, NULL),
(53, 'cliente', 'Rafaela Ferreira Castro', 'rafaela.castro53@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10041970713', '1995-08-18', 'Feminino', '18947678669', 'SP', 'Regente Feijó', -22.20100000, -51.29550000, NOW() - INTERVAL 92 DAY, NULL),
(54, 'cliente', 'Tatiane Sandoval Barbosa', 'tatiane.barbosa54@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10042762650', '1985-06-16', 'Feminino', '18957783854', 'SP', 'Martinópolis', -22.14380000, -51.18790000, NOW() - INTERVAL 380 DAY, NULL),
(55, 'cliente', 'Juliana Teixeira Barbosa', 'juliana.barbosa55@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10043554598', '1999-02-13', 'Feminino', '18913367546', 'SP', 'Presidente Prudente', -22.14680000, -51.40870000, NOW() - INTERVAL 363 DAY, NULL),
(56, 'cliente', 'Felipe Lima Costa', 'felipe.costa56@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10044346425', '2000-12-24', 'Masculino', '18948891095', 'SP', 'Presidente Prudente', -22.14910000, -51.41010000, NOW() - INTERVAL 193 DAY, NULL),
(57, 'cliente', 'Luana Gomes Martins', 'luana.martins57@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10045138362', '1979-02-04', 'Feminino', '18959625300', 'SP', 'Marília', -22.22750000, -49.95090000, NOW() - INTERVAL 135 DAY, NULL),
(58, 'cliente', 'Daniela Ferreira Ribeiro', 'daniela.ribeiro58@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10045930236', '1979-03-10', 'Feminino', '18997409082', 'SP', 'Presidente Epitácio', -21.74560000, -52.12880000, NOW() - INTERVAL 304 DAY, NULL),
(59, 'cliente', 'Beatriz Ferreira Mendes', 'beatriz.mendes59@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10046722173', '1969-12-02', 'Feminino', '18932181321', 'SP', 'Martinópolis', -22.13490000, -51.15830000, NOW() - INTERVAL 391 DAY, NULL),
(60, 'cliente', 'Vanessa Oliveira Gomes', 'vanessa.gomes60@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10047514000', '1973-12-22', 'Feminino', '18928903515', 'SP', 'Presidente Venceslau', -21.89770000, -51.84600000, NOW() - INTERVAL 316 DAY, NULL),
(61, 'cliente', 'Gustavo Ribeiro Rocha', 'gustavo.rocha61@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10048305995', '1984-02-21', 'Masculino', '18943298760', 'SP', 'Pirapozinho', -22.28070000, -51.50190000, NOW() - INTERVAL 281 DAY, NULL),
(62, 'cliente', 'Tatiane Nascimento Castro', 'tatiane.castro62@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10049097806', '1984-05-17', 'Feminino', '18956987984', 'SP', 'Presidente Prudente', -22.13720000, -51.38010000, NOW() - INTERVAL 275 DAY, NULL),
(63, 'cliente', 'Vinícius Lima Gomes', 'vinicius.gomes63@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10049889745', '1981-03-15', 'Masculino', '18944688902', 'SP', 'Presidente Prudente', -22.11840000, -51.38270000, NOW() - INTERVAL 284 DAY, NULL),
(64, 'cliente', 'Vinícius Cardoso Ferreira', 'vinicius.ferreira64@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10050681605', '1965-09-07', 'Masculino', '18990085032', 'SP', 'Presidente Prudente', -22.10080000, -51.38350000, NOW() - INTERVAL 15 DAY, NULL),
(65, 'cliente', 'Ana Silva Oliveira', 'ana.oliveira65@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10051473534', '1977-10-03', 'Feminino', '18965194857', 'SP', 'Pirapozinho', -22.26300000, -51.49430000, NOW() - INTERVAL 214 DAY, NULL),
(66, 'cliente', 'Igor Silva Costa', 'igor.costa66@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10052265471', '2002-11-12', 'Masculino', '18917797537', 'SP', 'Regente Feijó', -22.23250000, -51.28120000, NOW() - INTERVAL 219 DAY, NULL),
(67, 'cliente', 'Igor Pereira Pereira', 'igor.pereira67@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', '10053057309', '1981-08-09', 'Masculino', '18987672675', 'SP', 'Presidente Prudente', -22.10650000, -51.39410000, NOW() - INTERVAL 332 DAY, NOW() - INTERVAL 3 DAY),
(68, 'admin', 'Administrador Nexus', 'admin@teste.com', '$2y$10$ZI3NJmhbK02uNfJRXUZwCuE3AOImSw.aBkIr9LJtVqy7VTOq2AVlq', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW() - INTERVAL 400 DAY, NULL);

INSERT INTO prestador_perfis (usuario_id, profissao, descricao, preco_min, preco_max) VALUES
(2, 'Eletricista', 'Conta de teste do tipo prestador. Use para ver o painel e responder conversas.', 80, 250),
(3, 'Técnico de celular', 'Troca de tela, bateria e conector, além de recuperação de aparelhos molhados.', 58, 460),
(4, 'Marido de aluguel', 'Pequenos reparos em geral: montagem de móveis, instalação de prateleiras, cortinas, suportes de TV e ajustes em portas.', NULL, NULL),
(5, 'Personal trainer', 'Treinos personalizados em domicílio, academia ou ao ar livre.', 85, 159),
(6, 'DJ', 'Som e iluminação para festas, casamentos e formaturas.', 470, 1880),
(7, 'Lavador de carros', 'Lavagem completa, higienização interna e polimento, com atendimento no local.', NULL, NULL),
(8, 'Manicure', 'Manicure, pedicure, alongamento em gel e esmaltação em gel.', NULL, NULL),
(9, 'Professor de violão', 'Aulas de violão para iniciantes e intermediários, do básico às cifras.', 57, 86),
(10, 'Eletricista', 'Instalações e reparos elétricos residenciais e comerciais: tomadas, chuveiros, disjuntores, iluminação e quadros de energia.', 90, 336),
(11, 'Marido de aluguel', NULL, 55, 182),
(12, 'Professora de inglês', 'Inglês para todas as idades, presencial ou on-line, com foco em conversação.', 61, 101),
(13, 'DJ', 'Som e iluminação para festas, casamentos e formaturas.', 430, 1720),
(14, 'Pedreiro', 'Construção, reformas, contrapiso, reboco e assentamento de pisos. Orçamento sem compromisso no local.', NULL, NULL),
(15, 'Gesseiro', NULL, 452, 3390),
(16, 'Técnico de celular', NULL, 51, 404),
(17, 'Gesseiro', 'Forros, sancas, divisórias de drywall e molduras em gesso.', 348, 2610),
(18, 'Encanador', 'Conserto de vazamentos, desentupimentos, troca de registros e instalação de caixas d''água e aquecedores.', 91, 354),
(19, 'Mecânico', 'Mecânica geral, freios, suspensão e injeção eletrônica. Valor conforme diagnóstico.', NULL, NULL),
(20, 'Professor de matemática', 'Aulas particulares e reforço do ensino fundamental ao vestibular.', 59, 105),
(21, 'Limpeza pós-obra', NULL, 300, 900),
(22, 'Pintor', 'Pintura interna e externa, texturas, grafiato, massa corrida e acabamento fino.', 255, 2125),
(23, 'Personal trainer', NULL, 81, 152),
(24, 'Técnico em informática', 'Formatação, remoção de vírus, upgrade de peças e configuração de redes e impressoras.', 68, 243),
(25, 'Diarista', 'Limpeza residencial completa, organização e passadoria.', 144, 211),
(26, 'Cabeleireira', 'Cortes, escova, coloração e mechas. Atendimento em domicílio.', 54, 375),
(27, 'Lavador de carros', 'Lavagem completa, higienização interna e polimento, com atendimento no local.', 36, 225),
(28, 'Mecânico', 'Mecânica geral, freios, suspensão e injeção eletrônica. Valor conforme diagnóstico.', NULL, NULL),
(29, 'Maquiadora', 'Maquiagem social, para noivas e formaturas, com teste prévio.', 119, 396),
(30, 'Confeiteira', 'Bolos decorados, doces finos e mesas de doces para festas.', NULL, NULL),
(31, 'Maquiadora', 'Maquiagem social, para noivas e formaturas, com teste prévio.', NULL, NULL),
(32, 'Pintor', NULL, 288, 2400),
(33, 'Manicure', 'Manicure, pedicure, alongamento em gel e esmaltação em gel.', 35, 104),
(34, 'Desenvolvedor de sites', 'Sites institucionais, lojas virtuais e landing pages responsivas, com hospedagem e domínio.', 840, 5250),
(35, 'Professora de inglês', 'Inglês para todas as idades, presencial ou on-line, com foco em conversação.', 72, 120),
(36, 'Eletricista', 'Instalações e reparos elétricos residenciais e comerciais: tomadas, chuveiros, disjuntores, iluminação e quadros de energia.', 76, 285),
(37, 'Montador de móveis', NULL, 65, 233),
(38, 'Desenvolvedor de sites', 'Sites institucionais, lojas virtuais e landing pages responsivas, com hospedagem e domínio.', 960, 6000),
(39, 'Cabeleireira', 'Cortes, escova, coloração e mechas. Atendimento em domicílio.', NULL, NULL),
(40, 'Fotógrafo', 'Casamentos, aniversários, ensaios e eventos corporativos, com fotos tratadas em galeria on-line.', 388, 2425),
(41, 'Professor de violão', 'Aulas de violão para iniciantes e intermediários, do básico às cifras.', 64, 96),
(42, 'Montador de móveis', 'Montagem e desmontagem de móveis planejados e de loja, com cuidado e ferramentas próprias.', 71, 253);

INSERT INTO prestador_categorias (prestador_id, categoria_id) VALUES
(2, 1),
(3, 4),
(4, 1),
(4, 2),
(5, 6),
(6, 7),
(7, 8),
(8, 6),
(9, 5),
(10, 1),
(11, 1),
(11, 2),
(12, 5),
(13, 7),
(14, 2),
(15, 2),
(16, 4),
(17, 2),
(18, 1),
(19, 8),
(20, 5),
(21, 3),
(22, 2),
(23, 6),
(24, 4),
(25, 3),
(26, 6),
(27, 8),
(28, 8),
(29, 6),
(29, 7),
(30, 7),
(31, 6),
(31, 7),
(32, 2),
(33, 6),
(34, 4),
(35, 5),
(36, 1),
(37, 1),
(38, 4),
(39, 6),
(40, 7),
(41, 5),
(42, 1);

INSERT INTO prestador_horarios (prestador_id, dia_semana, inicio, fim) VALUES
(2, 1, '08:00', '18:00'),
(2, 2, '08:00', '18:00'),
(2, 3, '08:00', '18:00'),
(2, 4, '08:00', '18:00'),
(2, 5, '08:00', '18:00'),
(2, 6, '08:00', '12:00'),
(3, 0, '07:00', '22:00'),
(3, 1, '07:00', '22:00'),
(3, 2, '07:00', '22:00'),
(3, 3, '07:00', '22:00'),
(3, 4, '07:00', '22:00'),
(3, 5, '07:00', '22:00'),
(3, 6, '07:00', '22:00'),
(6, 5, '18:00', '23:00'),
(6, 6, '10:00', '23:00'),
(6, 0, '10:00', '18:00'),
(7, 1, '07:00', '12:00'),
(7, 2, '07:00', '12:00'),
(7, 3, '07:00', '12:00'),
(7, 4, '07:00', '12:00'),
(7, 5, '07:00', '12:00'),
(7, 6, '07:00', '12:00'),
(9, 1, '13:00', '21:00'),
(9, 2, '13:00', '21:00'),
(9, 3, '13:00', '21:00'),
(9, 4, '13:00', '21:00'),
(9, 5, '13:00', '21:00'),
(10, 1, '08:00', '18:00'),
(10, 2, '08:00', '18:00'),
(10, 3, '08:00', '18:00'),
(10, 4, '08:00', '18:00'),
(10, 5, '08:00', '18:00'),
(11, 1, '08:00', '18:00'),
(11, 2, '08:00', '18:00'),
(11, 3, '08:00', '18:00'),
(11, 4, '08:00', '18:00'),
(11, 5, '08:00', '18:00'),
(11, 6, '08:00', '12:00'),
(12, 0, '07:00', '22:00'),
(12, 1, '07:00', '22:00'),
(12, 2, '07:00', '22:00'),
(12, 3, '07:00', '22:00'),
(12, 4, '07:00', '22:00'),
(12, 5, '07:00', '22:00'),
(12, 6, '07:00', '22:00'),
(13, 5, '18:00', '23:00'),
(13, 6, '10:00', '23:00'),
(13, 0, '10:00', '18:00'),
(14, 1, '08:00', '18:00'),
(14, 2, '08:00', '18:00'),
(14, 3, '08:00', '18:00'),
(14, 4, '08:00', '18:00'),
(14, 5, '08:00', '18:00'),
(15, 1, '08:00', '18:00'),
(15, 2, '08:00', '18:00'),
(15, 3, '08:00', '18:00'),
(15, 4, '08:00', '18:00'),
(15, 5, '08:00', '18:00'),
(16, 1, '08:00', '18:00'),
(16, 2, '08:00', '18:00'),
(16, 3, '08:00', '18:00'),
(16, 4, '08:00', '18:00'),
(16, 5, '08:00', '18:00'),
(17, 1, '08:00', '18:00'),
(17, 2, '08:00', '18:00'),
(17, 3, '08:00', '18:00'),
(17, 4, '08:00', '18:00'),
(17, 5, '08:00', '18:00'),
(19, 5, '18:00', '23:00'),
(19, 6, '10:00', '23:00'),
(19, 0, '10:00', '18:00'),
(20, 0, '07:00', '22:00'),
(20, 1, '07:00', '22:00'),
(20, 2, '07:00', '22:00'),
(20, 3, '07:00', '22:00'),
(20, 4, '07:00', '22:00'),
(20, 5, '07:00', '22:00'),
(20, 6, '07:00', '22:00'),
(21, 1, '07:00', '12:00'),
(21, 2, '07:00', '12:00'),
(21, 3, '07:00', '12:00'),
(21, 4, '07:00', '12:00'),
(21, 5, '07:00', '12:00'),
(21, 6, '07:00', '12:00'),
(22, 1, '07:00', '12:00'),
(22, 2, '07:00', '12:00'),
(22, 3, '07:00', '12:00'),
(22, 4, '07:00', '12:00'),
(22, 5, '07:00', '12:00'),
(22, 6, '07:00', '12:00'),
(23, 1, '07:00', '12:00'),
(23, 2, '07:00', '12:00'),
(23, 3, '07:00', '12:00'),
(23, 4, '07:00', '12:00'),
(23, 5, '07:00', '12:00'),
(23, 6, '07:00', '12:00'),
(24, 1, '08:00', '18:00'),
(24, 2, '08:00', '18:00'),
(24, 3, '08:00', '18:00'),
(24, 4, '08:00', '18:00'),
(24, 5, '08:00', '18:00'),
(26, 1, '13:00', '21:00'),
(26, 2, '13:00', '21:00'),
(26, 3, '13:00', '21:00'),
(26, 4, '13:00', '21:00'),
(26, 5, '13:00', '21:00'),
(27, 1, '13:00', '21:00'),
(27, 2, '13:00', '21:00'),
(27, 3, '13:00', '21:00'),
(27, 4, '13:00', '21:00'),
(27, 5, '13:00', '21:00'),
(28, 5, '18:00', '23:00'),
(28, 6, '10:00', '23:00'),
(28, 0, '10:00', '18:00'),
(29, 5, '18:00', '23:00'),
(29, 6, '10:00', '23:00'),
(29, 0, '10:00', '18:00'),
(32, 1, '08:00', '18:00'),
(32, 2, '08:00', '18:00'),
(32, 3, '08:00', '18:00'),
(32, 4, '08:00', '18:00'),
(32, 5, '08:00', '18:00'),
(33, 1, '07:00', '12:00'),
(33, 2, '07:00', '12:00'),
(33, 3, '07:00', '12:00'),
(33, 4, '07:00', '12:00'),
(33, 5, '07:00', '12:00'),
(33, 6, '07:00', '12:00'),
(34, 0, '07:00', '22:00'),
(34, 1, '07:00', '22:00'),
(34, 2, '07:00', '22:00'),
(34, 3, '07:00', '22:00'),
(34, 4, '07:00', '22:00'),
(34, 5, '07:00', '22:00'),
(34, 6, '07:00', '22:00'),
(35, 1, '08:00', '18:00'),
(35, 2, '08:00', '18:00'),
(35, 3, '08:00', '18:00'),
(35, 4, '08:00', '18:00'),
(35, 5, '08:00', '18:00'),
(36, 1, '07:00', '12:00'),
(36, 2, '07:00', '12:00'),
(36, 3, '07:00', '12:00'),
(36, 4, '07:00', '12:00'),
(36, 5, '07:00', '12:00'),
(36, 6, '07:00', '12:00'),
(37, 1, '08:00', '18:00'),
(37, 2, '08:00', '18:00'),
(37, 3, '08:00', '18:00'),
(37, 4, '08:00', '18:00'),
(37, 5, '08:00', '18:00'),
(39, 1, '08:00', '18:00'),
(39, 2, '08:00', '18:00'),
(39, 3, '08:00', '18:00'),
(39, 4, '08:00', '18:00'),
(39, 5, '08:00', '18:00'),
(39, 6, '08:00', '12:00'),
(40, 5, '18:00', '23:00'),
(40, 6, '10:00', '23:00'),
(40, 0, '10:00', '18:00'),
(42, 1, '07:00', '12:00'),
(42, 2, '07:00', '12:00'),
(42, 3, '07:00', '12:00'),
(42, 4, '07:00', '12:00'),
(42, 5, '07:00', '12:00'),
(42, 6, '07:00', '12:00');

INSERT INTO avaliacoes (cliente_id, prestador_id, nota, comentario, criado_em) VALUES
(48, 3, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 177 DAY),
(59, 3, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 51 DAY),
(60, 3, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 177 DAY),
(46, 4, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 68 DAY),
(62, 4, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 17 DAY),
(47, 5, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 100 DAY),
(53, 5, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 48 DAY),
(54, 5, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 106 DAY),
(56, 5, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 33 DAY),
(59, 5, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 25 DAY),
(63, 5, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 135 DAY),
(64, 5, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 1 DAY),
(65, 5, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 28 DAY),
(46, 6, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 157 DAY),
(48, 6, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 90 DAY),
(62, 6, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 28 DAY),
(67, 6, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 5 DAY),
(56, 7, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 40 DAY),
(58, 7, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 96 DAY),
(61, 7, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 104 DAY),
(66, 7, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 121 DAY),
(49, 9, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 118 DAY),
(57, 9, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 16 DAY),
(51, 10, 5, 'Serviço impecável, recomendo de olhos fechados!', NOW() - INTERVAL 135 DAY),
(53, 10, 5, 'Serviço impecável, recomendo de olhos fechados!', NOW() - INTERVAL 94 DAY),
(55, 10, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 122 DAY),
(56, 10, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 143 DAY),
(64, 10, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 116 DAY),
(66, 10, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 73 DAY),
(47, 11, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 70 DAY),
(54, 11, 5, 'Serviço impecável, recomendo de olhos fechados!', NOW() - INTERVAL 51 DAY),
(62, 11, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 143 DAY),
(64, 11, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 58 DAY),
(66, 11, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 87 DAY),
(61, 12, 5, 'Resolveu tudo rapidamente e com preço justo.', NOW() - INTERVAL 112 DAY),
(52, 13, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 178 DAY),
(64, 13, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 179 DAY),
(49, 14, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 144 DAY),
(64, 14, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 121 DAY),
(1, 15, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 174 DAY),
(44, 15, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 17 DAY),
(60, 15, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 136 DAY),
(67, 15, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 111 DAY),
(44, 16, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 174 DAY),
(45, 16, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 159 DAY),
(47, 16, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 178 DAY),
(51, 16, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 155 DAY),
(52, 16, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 97 DAY),
(54, 16, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 139 DAY),
(45, 17, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 84 DAY),
(54, 17, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 104 DAY),
(56, 17, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 159 DAY),
(66, 17, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 22 DAY),
(67, 17, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 34 DAY),
(61, 18, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 98 DAY),
(1, 19, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 60 DAY),
(43, 19, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 12 DAY),
(59, 19, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 60 DAY),
(60, 19, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 18 DAY),
(1, 20, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 85 DAY),
(67, 20, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 52 DAY),
(1, 21, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 159 DAY),
(45, 21, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 97 DAY),
(46, 21, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 49 DAY),
(56, 21, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 100 DAY),
(62, 21, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 138 DAY),
(54, 22, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 97 DAY),
(56, 22, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 174 DAY),
(60, 22, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 89 DAY),
(65, 22, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 114 DAY),
(53, 23, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 19 DAY),
(58, 23, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 128 DAY),
(62, 23, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 85 DAY),
(63, 23, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 138 DAY),
(65, 23, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 111 DAY),
(66, 23, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 173 DAY),
(51, 24, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 137 DAY),
(67, 24, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 10 DAY),
(47, 25, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 87 DAY),
(50, 25, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 21 DAY),
(51, 25, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 111 DAY),
(52, 25, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 43 DAY),
(64, 25, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 3 DAY),
(67, 25, 2, 'Não ficou como combinamos e foi difícil de remarcar.', NOW() - INTERVAL 119 DAY),
(47, 26, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 40 DAY),
(1, 27, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 102 DAY),
(61, 27, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 179 DAY),
(64, 27, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 162 DAY),
(46, 28, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 149 DAY),
(51, 28, 5, 'Resolveu tudo rapidamente e com preço justo.', NOW() - INTERVAL 108 DAY),
(53, 28, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 51 DAY),
(60, 28, 5, 'Serviço impecável, recomendo de olhos fechados!', NOW() - INTERVAL 62 DAY),
(62, 28, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 130 DAY),
(67, 28, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 103 DAY),
(47, 29, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 76 DAY),
(53, 29, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 63 DAY),
(57, 29, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 177 DAY),
(43, 30, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 140 DAY),
(44, 30, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 7 DAY),
(54, 30, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 92 DAY),
(56, 30, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 68 DAY),
(57, 30, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 86 DAY),
(60, 30, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 39 DAY),
(65, 30, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 144 DAY),
(54, 31, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 180 DAY),
(58, 32, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 154 DAY),
(45, 33, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 134 DAY),
(53, 33, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 71 DAY),
(59, 33, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 69 DAY),
(60, 33, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 103 DAY),
(66, 33, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 20 DAY),
(44, 34, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 164 DAY),
(45, 34, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 46 DAY),
(54, 34, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 178 DAY),
(58, 34, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 23 DAY),
(66, 34, 5, 'Resolveu tudo rapidamente e com preço justo.', NOW() - INTERVAL 132 DAY),
(67, 34, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 159 DAY),
(45, 35, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 133 DAY),
(49, 35, 3, 'Resolveu o problema, porém precisou voltar uma segunda vez.', NOW() - INTERVAL 65 DAY),
(54, 35, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 94 DAY),
(56, 35, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 125 DAY),
(66, 35, 3, 'Atendimento razoável, esperava um pouco mais.', NOW() - INTERVAL 39 DAY),
(45, 36, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 162 DAY),
(47, 36, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 107 DAY),
(49, 36, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 124 DAY),
(62, 36, 4, 'Gostei bastante, preço dentro do combinado.', NOW() - INTERVAL 175 DAY),
(64, 36, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 151 DAY),
(47, 37, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 11 DAY),
(43, 38, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 70 DAY),
(54, 38, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 28 DAY),
(61, 38, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 34 DAY),
(63, 38, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 147 DAY),
(65, 38, 5, 'Atendimento excelente do começo ao fim. Já indiquei para a família.', NOW() - INTERVAL 71 DAY),
(51, 39, 5, 'Serviço impecável, recomendo de olhos fechados!', NOW() - INTERVAL 28 DAY),
(53, 39, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 100 DAY),
(55, 39, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 50 DAY),
(57, 39, 5, 'Resolveu tudo rapidamente e com preço justo.', NOW() - INTERVAL 125 DAY),
(62, 39, 5, 'Resolveu tudo rapidamente e com preço justo.', NOW() - INTERVAL 171 DAY),
(46, 40, 5, 'Muito profissional, pontual e caprichoso.', NOW() - INTERVAL 49 DAY),
(1, 41, 4, 'Atencioso e organizado, recomendo.', NOW() - INTERVAL 123 DAY),
(53, 41, 3, 'Serviço ok, mas demorou mais do que o combinado.', NOW() - INTERVAL 148 DAY),
(54, 42, 4, 'Trabalho bem feito, poderia ter limpado melhor depois.', NOW() - INTERVAL 82 DAY),
(57, 42, 4, 'Bom serviço, só atrasou um pouco para chegar.', NOW() - INTERVAL 103 DAY),
(61, 42, 5, 'Superou as expectativas, voltarei a contratar com certeza.', NOW() - INTERVAL 47 DAY);

INSERT INTO conversas (id, cliente_id, prestador_id, criado_em) VALUES
(1, 1, 3, NOW() - INTERVAL 19 DAY - INTERVAL 1 HOUR),
(2, 1, 4, NOW() - INTERVAL 15 DAY - INTERVAL 1 HOUR),
(3, 1, 5, NOW() - INTERVAL 2 DAY - INTERVAL 1 HOUR),
(4, 43, 2, NOW() - INTERVAL 13 DAY - INTERVAL 1 HOUR),
(5, 44, 2, NOW() - INTERVAL 11 DAY - INTERVAL 1 HOUR),
(6, 45, 2, NOW() - INTERVAL 8 DAY - INTERVAL 1 HOUR),
(7, 66, 33, NOW() - INTERVAL 17 DAY - INTERVAL 1 HOUR),
(8, 59, 15, NOW() - INTERVAL 7 DAY - INTERVAL 1 HOUR),
(9, 44, 14, NOW() - INTERVAL 4 DAY - INTERVAL 1 HOUR),
(10, 45, 36, NOW() - INTERVAL 3 DAY - INTERVAL 1 HOUR),
(11, 43, 30, NOW() - INTERVAL 10 DAY - INTERVAL 1 HOUR),
(12, 64, 4, NOW() - INTERVAL 3 DAY - INTERVAL 1 HOUR),
(13, 44, 9, NOW() - INTERVAL 11 DAY - INTERVAL 1 HOUR),
(14, 54, 9, NOW() - INTERVAL 11 DAY - INTERVAL 1 HOUR),
(15, 63, 10, NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR),
(16, 61, 32, NOW() - INTERVAL 7 DAY - INTERVAL 1 HOUR),
(17, 58, 6, NOW() - INTERVAL 12 DAY - INTERVAL 1 HOUR);

INSERT INTO mensagens (conversa_id, remetente_id, conteudo, lida, enviada_em) VALUES
(1, 1, 'Olá! Vi seu perfil aqui na Nexus. Você atende na minha região?', 1, NOW() - INTERVAL 19 DAY - INTERVAL 35 MINUTE),
(1, 3, 'Olá! Atendo sim. Do que você precisa?', 1, NOW() - INTERVAL 19 DAY - INTERVAL 28 MINUTE),
(1, 1, 'Preciso de um orçamento para esta semana. Qual seria o valor?', 1, NOW() - INTERVAL 19 DAY - INTERVAL 21 MINUTE),
(1, 3, 'Consigo passar aí amanhã para avaliar e já te passo o valor certinho.', 1, NOW() - INTERVAL 19 DAY - INTERVAL 14 MINUTE),
(1, 1, 'Perfeito, combinado!', 0, NOW() - INTERVAL 19 DAY - INTERVAL 7 MINUTE),
(2, 1, 'Boa tarde! Você tem horário disponível no sábado?', 1, NOW() - INTERVAL 15 DAY - INTERVAL 21 MINUTE),
(2, 4, 'Boa tarde! Tenho às 9h ou às 14h.', 1, NOW() - INTERVAL 15 DAY - INTERVAL 14 MINUTE),
(2, 1, 'Pode ser às 9h. Obrigado!', 0, NOW() - INTERVAL 15 DAY - INTERVAL 7 MINUTE),
(3, 1, 'Boa tarde! Você tem horário disponível no sábado?', 1, NOW() - INTERVAL 2 DAY - INTERVAL 21 MINUTE),
(3, 5, 'Boa tarde! Tenho às 9h ou às 14h.', 1, NOW() - INTERVAL 2 DAY - INTERVAL 14 MINUTE),
(3, 1, 'Pode ser às 9h. Obrigado!', 0, NOW() - INTERVAL 2 DAY - INTERVAL 7 MINUTE),
(4, 43, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 13 DAY - INTERVAL 14 MINUTE),
(4, 2, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 13 DAY - INTERVAL 7 MINUTE),
(5, 44, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 14 MINUTE),
(5, 2, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 11 DAY - INTERVAL 7 MINUTE),
(6, 45, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 8 DAY - INTERVAL 14 MINUTE),
(6, 2, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 8 DAY - INTERVAL 7 MINUTE),
(7, 66, 'Boa tarde! Você tem horário disponível no sábado?', 1, NOW() - INTERVAL 17 DAY - INTERVAL 21 MINUTE),
(7, 33, 'Boa tarde! Tenho às 9h ou às 14h.', 1, NOW() - INTERVAL 17 DAY - INTERVAL 14 MINUTE),
(7, 66, 'Pode ser às 9h. Obrigado!', 0, NOW() - INTERVAL 17 DAY - INTERVAL 7 MINUTE),
(8, 59, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 7 DAY - INTERVAL 14 MINUTE),
(8, 15, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 7 DAY - INTERVAL 7 MINUTE),
(9, 44, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 4 DAY - INTERVAL 14 MINUTE),
(9, 14, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 4 DAY - INTERVAL 7 MINUTE),
(10, 45, 'Olá! Vi seu perfil aqui na Nexus. Você atende na minha região?', 1, NOW() - INTERVAL 3 DAY - INTERVAL 35 MINUTE),
(10, 36, 'Olá! Atendo sim. Do que você precisa?', 1, NOW() - INTERVAL 3 DAY - INTERVAL 28 MINUTE),
(10, 45, 'Preciso de um orçamento para esta semana. Qual seria o valor?', 1, NOW() - INTERVAL 3 DAY - INTERVAL 21 MINUTE),
(10, 36, 'Consigo passar aí amanhã para avaliar e já te passo o valor certinho.', 1, NOW() - INTERVAL 3 DAY - INTERVAL 14 MINUTE),
(10, 45, 'Perfeito, combinado!', 0, NOW() - INTERVAL 3 DAY - INTERVAL 7 MINUTE),
(11, 43, 'Boa tarde! Você tem horário disponível no sábado?', 1, NOW() - INTERVAL 10 DAY - INTERVAL 21 MINUTE),
(11, 30, 'Boa tarde! Tenho às 9h ou às 14h.', 1, NOW() - INTERVAL 10 DAY - INTERVAL 14 MINUTE),
(11, 43, 'Pode ser às 9h. Obrigado!', 0, NOW() - INTERVAL 10 DAY - INTERVAL 7 MINUTE),
(12, 64, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 3 DAY - INTERVAL 14 MINUTE),
(12, 4, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 3 DAY - INTERVAL 7 MINUTE),
(13, 44, 'Olá! Vi seu perfil aqui na Nexus. Você atende na minha região?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 35 MINUTE),
(13, 9, 'Olá! Atendo sim. Do que você precisa?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 28 MINUTE),
(13, 44, 'Preciso de um orçamento para esta semana. Qual seria o valor?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 21 MINUTE),
(13, 9, 'Consigo passar aí amanhã para avaliar e já te passo o valor certinho.', 1, NOW() - INTERVAL 11 DAY - INTERVAL 14 MINUTE),
(13, 44, 'Perfeito, combinado!', 0, NOW() - INTERVAL 11 DAY - INTERVAL 7 MINUTE),
(14, 54, 'Olá! Vi seu perfil aqui na Nexus. Você atende na minha região?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 35 MINUTE),
(14, 9, 'Olá! Atendo sim. Do que você precisa?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 28 MINUTE),
(14, 54, 'Preciso de um orçamento para esta semana. Qual seria o valor?', 1, NOW() - INTERVAL 11 DAY - INTERVAL 21 MINUTE),
(14, 9, 'Consigo passar aí amanhã para avaliar e já te passo o valor certinho.', 1, NOW() - INTERVAL 11 DAY - INTERVAL 14 MINUTE),
(14, 54, 'Perfeito, combinado!', 0, NOW() - INTERVAL 11 DAY - INTERVAL 7 MINUTE),
(15, 63, 'Olá! Vi seu perfil aqui na Nexus. Você atende na minha região?', 1, NOW() - INTERVAL 1 DAY - INTERVAL 35 MINUTE),
(15, 10, 'Olá! Atendo sim. Do que você precisa?', 1, NOW() - INTERVAL 1 DAY - INTERVAL 28 MINUTE),
(15, 63, 'Preciso de um orçamento para esta semana. Qual seria o valor?', 1, NOW() - INTERVAL 1 DAY - INTERVAL 21 MINUTE),
(15, 10, 'Consigo passar aí amanhã para avaliar e já te passo o valor certinho.', 1, NOW() - INTERVAL 1 DAY - INTERVAL 14 MINUTE),
(15, 63, 'Perfeito, combinado!', 0, NOW() - INTERVAL 1 DAY - INTERVAL 7 MINUTE),
(16, 61, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 7 DAY - INTERVAL 14 MINUTE),
(16, 32, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 7 DAY - INTERVAL 7 MINUTE),
(17, 58, 'Oi, tudo bem? Quanto você cobra pelo serviço?', 1, NOW() - INTERVAL 12 DAY - INTERVAL 14 MINUTE),
(17, 6, 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?', 0, NOW() - INTERVAL 12 DAY - INTERVAL 7 MINUTE);

INSERT INTO contatos (nome, email, telefone, assunto, mensagem, status, resposta, respondido_em, criado_em) VALUES
('Visitante Teste', 'visitante@teste.com', NULL, 'duvida', 'Como faço para aparecer nas buscas de outra cidade?', 'novo', NULL, NULL, NOW() - INTERVAL 2 HOUR),
('Roberta Lima', 'roberta@teste.com', '18991112222', 'problema', 'Não consigo alterar minha foto de perfil, aparece um erro.', 'novo', NULL, NULL, NOW() - INTERVAL 1 DAY),
('Marcos Vieira', 'marcos@teste.com', NULL, 'parceria', 'Tenho uma loja de materiais de construção e gostaria de anunciar no site.', 'lido', NULL, NULL, NOW() - INTERVAL 3 DAY),
('Clara Duarte', 'clara@teste.com', NULL, 'sugestao', 'Seria ótimo poder salvar profissionais favoritos.', 'respondido', 'Olá, Clara! Obrigado pela sugestão, ela já está na nossa lista de melhorias.', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 5 DAY);

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

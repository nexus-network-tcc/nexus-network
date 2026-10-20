-- Nexus Network: estrutura do banco de dados
-- Selecione o banco no phpMyAdmin antes de importar (as tabelas existentes são recriadas).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS mensagens, conversas, avaliacoes, prestador_horarios, prestador_categorias, prestador_perfis,
    redefinicoes_senha, categorias, contatos, geocode_cache, denuncias, buscas,
    agendamentos, favoritos, notificacoes, prestador_fotos, tentativas_login, admin_logs, usuarios;
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
    lembrete_em  DATETIME NULL COMMENT 'Quando o lembrete de 24h foi enviado',
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

-- Tentativas de login que falharam (limite por e-mail e por IP).
CREATE TABLE tentativas_login (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email     VARCHAR(190) NULL,
    ip        VARCHAR(45) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas_email (email, criado_em),
    KEY idx_tentativas_ip (ip, criado_em)
) ENGINE=InnoDB;

-- Registro das ações feitas pelos administradores no painel.
CREATE TABLE admin_logs (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id  INT UNSIGNED NULL,
    acao      VARCHAR(40) NOT NULL,
    alvo      VARCHAR(190) NOT NULL COMMENT 'Descrição do alvo no momento da ação (o registro pode ser excluído depois)',
    detalhes  VARCHAR(255) NULL,
    ip        VARCHAR(45) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_logs_data (criado_em),
    KEY idx_admin_logs_acao (acao, criado_em),
    CONSTRAINT fk_admin_log_admin FOREIGN KEY (admin_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB;

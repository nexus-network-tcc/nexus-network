-- Migração 003: agendamentos/orçamentos, favoritos, notificações e galeria de fotos do prestador.
-- Aplique em bancos criados antes desta versão (phpMyAdmin > selecione o banco > Importar).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS agendamentos (
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

CREATE TABLE IF NOT EXISTS favoritos (
    cliente_id   INT UNSIGNED NOT NULL,
    prestador_id INT UNSIGNED NOT NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cliente_id, prestador_id),
    KEY idx_favoritos_prestador (prestador_id),
    CONSTRAINT fk_favorito_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorito_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notificacoes (
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

CREATE TABLE IF NOT EXISTS prestador_fotos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prestador_id INT UNSIGNED NOT NULL,
    caminho      VARCHAR(500) NOT NULL,
    legenda      VARCHAR(120) NULL,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fotos_prestador (prestador_id, id),
    CONSTRAINT fk_foto_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB;

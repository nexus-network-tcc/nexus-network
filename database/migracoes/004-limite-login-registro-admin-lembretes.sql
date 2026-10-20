-- Migração 004: limite de tentativas de login, registro de ações do admin e lembretes de agendamento.
-- Aplique em bancos criados antes desta versão (phpMyAdmin > selecione o banco > Importar).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tentativas_login (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email     VARCHAR(190) NULL,
    ip        VARCHAR(45) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas_email (email, criado_em),
    KEY idx_tentativas_ip (ip, criado_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_logs (
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

ALTER TABLE agendamentos
    ADD COLUMN lembrete_em DATETIME NULL COMMENT 'Quando o lembrete de 24h foi enviado' AFTER resposta;

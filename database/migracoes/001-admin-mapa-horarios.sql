-- Migração 001: administrador, suspensão de contas, horários de atendimento e resposta a contatos.
-- Aplique em bancos criados antes desta versão (phpMyAdmin > selecione o banco > Importar).

SET NAMES utf8mb4;

ALTER TABLE usuarios
    MODIFY tipo      ENUM('cliente', 'prestador', 'admin') NOT NULL,
    MODIFY cpf       CHAR(11) NULL,
    MODIFY data_nasc DATE NULL,
    MODIFY genero    VARCHAR(30) NULL,
    MODIFY telefone  VARCHAR(11) NULL,
    MODIFY estado    CHAR(2) NULL,
    MODIFY cidade    VARCHAR(100) NULL,
    ADD COLUMN suspenso_em DATETIME NULL COMMENT 'Preenchido quando um admin suspende a conta' AFTER google_id;

CREATE TABLE IF NOT EXISTS prestador_horarios (
    prestador_id INT UNSIGNED NOT NULL,
    dia_semana   TINYINT UNSIGNED NOT NULL,
    inicio       TIME NOT NULL,
    fim          TIME NOT NULL,
    PRIMARY KEY (prestador_id, dia_semana),
    CONSTRAINT fk_horario_prestador FOREIGN KEY (prestador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT ck_horario_dia CHECK (dia_semana BETWEEN 0 AND 6),
    CONSTRAINT ck_horario_periodo CHECK (fim > inicio)
) ENGINE=InnoDB;

ALTER TABLE contatos
    ADD COLUMN resposta TEXT NULL AFTER status,
    ADD COLUMN respondido_em DATETIME NULL AFTER resposta;

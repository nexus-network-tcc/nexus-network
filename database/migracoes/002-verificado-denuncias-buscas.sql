-- Migração 002: selo de verificado, denúncias e registro de buscas.
-- Aplique em bancos criados antes desta versão (phpMyAdmin > selecione o banco > Importar).

SET NAMES utf8mb4;

ALTER TABLE prestador_perfis
    ADD COLUMN verificado_em DATETIME NULL COMMENT 'Selo de verificado, concedido por um admin' AFTER preco_max;

CREATE TABLE IF NOT EXISTS denuncias (
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

CREATE TABLE IF NOT EXISTS buscas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NULL,
    termo        VARCHAR(100) NULL,
    resultados   INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_buscas_data (criado_em),
    KEY idx_buscas_categoria (categoria_id),
    CONSTRAINT fk_busca_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE SET NULL
) ENGINE=InnoDB;

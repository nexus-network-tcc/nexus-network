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

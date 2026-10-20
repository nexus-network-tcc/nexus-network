<?php

/**
 * Gera database/teste.sql: um banco separado (nexus_network_teste)
 * com estrutura, dados base e muitos usuários fictícios para testes.
 *
 * Uso: php database/gerar-teste.php
 * Todas as contas geradas usam a senha Nexus@123.
 */

if (PHP_SAPI !== 'cli') {
    exit('Execute pela linha de comando: php database/gerar-teste.php');
}

const DB_NAME = 'nexus_network_teste';
const SENHA = 'Nexus@123';

mt_srand(2025); // mesma semente = mesmos dados a cada geração

$cidades = [
    // cidade => [lat, lon, peso]
    'Presidente Prudente'  => [-22.1256, -51.3889, 10],
    'Álvares Machado'      => [-22.0764, -51.4722, 3],
    'Regente Feijó'        => [-22.2214, -51.3028, 2],
    'Pirapozinho'          => [-22.2753, -51.5000, 2],
    'Martinópolis'         => [-22.1458, -51.1708, 2],
    'Presidente Venceslau' => [-21.8761, -51.8436, 2],
    'Presidente Epitácio'  => [-21.7633, -52.1156, 1],
    'Santo Anastácio'      => [-21.9775, -51.6522, 1],
    'Assis'                => [-22.6611, -50.4122, 2],
    'Marília'              => [-22.2139, -49.9458, 2],
    'São Paulo'            => [-23.5505, -46.6333, 2],
    'Campinas'             => [-22.9056, -47.0608, 1],
];

$nomesF = ['Ana', 'Beatriz', 'Camila', 'Daniela', 'Eduarda', 'Fernanda', 'Gabriela', 'Helena', 'Isabela', 'Juliana', 'Larissa', 'Mariana', 'Natália', 'Patrícia', 'Rafaela', 'Sofia', 'Tatiane', 'Vanessa', 'Yasmin', 'Luana'];
$nomesM = ['André', 'Bruno', 'Carlos', 'Diego', 'Eduardo', 'Felipe', 'Gustavo', 'Henrique', 'Igor', 'João', 'Lucas', 'Marcos', 'Nicolas', 'Otávio', 'Paulo', 'Rafael', 'Samuel', 'Thiago', 'Vinícius', 'William'];
$sobrenomes = ['Silva', 'Souza', 'Oliveira', 'Santos', 'Lima', 'Pereira', 'Ferreira', 'Costa', 'Rodrigues', 'Almeida', 'Nascimento', 'Carvalho', 'Ribeiro', 'Martins', 'Rocha', 'Mendes', 'Barbosa', 'Gomes', 'Teixeira', 'Moreira', 'Castro', 'Sandoval', 'Araújo', 'Cardoso'];

// profissão => [categorias, preço mín, preço máx (null = a combinar), descrição]
$profissoes = [
    'Eletricista'             => [[1], 80, 300, 'Instalações e reparos elétricos residenciais e comerciais: tomadas, chuveiros, disjuntores, iluminação e quadros de energia.'],
    'Encanador'               => [[1], 90, 350, 'Conserto de vazamentos, desentupimentos, troca de registros e instalação de caixas d\'água e aquecedores.'],
    'Marido de aluguel'       => [[1, 2], 60, 200, 'Pequenos reparos em geral: montagem de móveis, instalação de prateleiras, cortinas, suportes de TV e ajustes em portas.'],
    'Montador de móveis'      => [[1], 70, 250, 'Montagem e desmontagem de móveis planejados e de loja, com cuidado e ferramentas próprias.'],
    'Pedreiro'                => [[2], null, null, 'Construção, reformas, contrapiso, reboco e assentamento de pisos. Orçamento sem compromisso no local.'],
    'Pintor'                  => [[2], 300, 2500, 'Pintura interna e externa, texturas, grafiato, massa corrida e acabamento fino.'],
    'Gesseiro'                => [[2], 400, 3000, 'Forros, sancas, divisórias de drywall e molduras em gesso.'],
    'Diarista'                => [[3], 150, 220, 'Limpeza residencial completa, organização e passadoria.'],
    'Limpeza pós-obra'        => [[3], 300, 900, 'Limpeza pesada após reformas, com remoção de respingos de tinta e cimento.'],
    'Técnico em informática'  => [[4], 70, 250, 'Formatação, remoção de vírus, upgrade de peças e configuração de redes e impressoras.'],
    'Desenvolvedor de sites'  => [[4], 800, 5000, 'Sites institucionais, lojas virtuais e landing pages responsivas, com hospedagem e domínio.'],
    'Técnico de celular'      => [[4], 50, 400, 'Troca de tela, bateria e conector, além de recuperação de aparelhos molhados.'],
    'Professor de matemática' => [[5], 50, 90, 'Aulas particulares e reforço do ensino fundamental ao vestibular.'],
    'Professora de inglês'    => [[5], 60, 100, 'Inglês para todas as idades, presencial ou on-line, com foco em conversação.'],
    'Professor de violão'     => [[5], 60, 90, 'Aulas de violão para iniciantes e intermediários, do básico às cifras.'],
    'Cabeleireira'            => [[6], 50, 350, 'Cortes, escova, coloração e mechas. Atendimento em domicílio.'],
    'Manicure'                => [[6], 30, 90, 'Manicure, pedicure, alongamento em gel e esmaltação em gel.'],
    'Maquiadora'              => [[6, 7], 120, 400, 'Maquiagem social, para noivas e formaturas, com teste prévio.'],
    'Personal trainer'        => [[6], 80, 150, 'Treinos personalizados em domicílio, academia ou ao ar livre.'],
    'Fotógrafo'               => [[7], 400, 2500, 'Casamentos, aniversários, ensaios e eventos corporativos, com fotos tratadas em galeria on-line.'],
    'DJ'                      => [[7], 500, 2000, 'Som e iluminação para festas, casamentos e formaturas.'],
    'Confeiteira'             => [[7], 150, 900, 'Bolos decorados, doces finos e mesas de doces para festas.'],
    'Mecânico'                => [[8], null, null, 'Mecânica geral, freios, suspensão e injeção eletrônica. Valor conforme diagnóstico.'],
    'Lavador de carros'       => [[8], 40, 250, 'Lavagem completa, higienização interna e polimento, com atendimento no local.'],
];

$comentarios = [
    5 => ['Serviço impecável, recomendo de olhos fechados!', 'Muito profissional, pontual e caprichoso.', 'Resolveu tudo rapidamente e com preço justo.', 'Atendimento excelente do começo ao fim. Já indiquei para a família.', 'Superou as expectativas, voltarei a contratar com certeza.'],
    4 => ['Bom serviço, só atrasou um pouco para chegar.', 'Trabalho bem feito, poderia ter limpado melhor depois.', 'Gostei bastante, preço dentro do combinado.', 'Atencioso e organizado, recomendo.'],
    3 => ['Serviço ok, mas demorou mais do que o combinado.', 'Resolveu o problema, porém precisou voltar uma segunda vez.', 'Atendimento razoável, esperava um pouco mais.'],
    2 => ['Não ficou como combinamos e foi difícil de remarcar.', 'Atrasou bastante e o acabamento deixou a desejar.'],
    1 => ['Não compareceu no horário marcado e não avisou.'],
];

$conversaModelos = [
    ['Olá! Vi seu perfil aqui na Nexus. Você atende na minha região?', 'Olá! Atendo sim. Do que você precisa?', 'Preciso de um orçamento para esta semana. Qual seria o valor?', 'Consigo passar aí amanhã para avaliar e já te passo o valor certinho.', 'Perfeito, combinado!'],
    ['Boa tarde! Você tem horário disponível no sábado?', 'Boa tarde! Tenho às 9h ou às 14h.', 'Pode ser às 9h. Obrigado!'],
    ['Oi, tudo bem? Quanto você cobra pelo serviço?', 'Tudo ótimo! Depende do tamanho, mas costuma ficar dentro da faixa do meu perfil. Pode me mandar mais detalhes?'],
];

// ---------- utilitários ----------

function q(?string $v): string
{
    return $v === null ? 'NULL' : "'" . str_replace(["\\", "'"], ["\\\\", "''"], $v) . "'";
}

function cpf(int $base): string
{
    $c = str_pad((string) $base, 9, '0', STR_PAD_LEFT);
    for ($t = 9; $t < 11; $t++) {
        $s = 0;
        for ($i = 0; $i < $t; $i++) {
            $s += (int) $c[$i] * (($t + 1) - $i);
        }
        $c .= ((10 * $s) % 11) % 10;
    }
    return $c;
}

function slug(string $s): string
{
    $s = strtr(mb_strtolower($s), ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
    return preg_replace('/[^a-z]/', '', $s);
}

function pick(array $a): mixed
{
    return $a[array_rand($a)];
}

function weightedCity(array $cidades): string
{
    $total = array_sum(array_column($cidades, 2));
    $r = mt_rand(1, $total);
    foreach ($cidades as $nome => [, , $peso]) {
        if (($r -= $peso) <= 0) {
            return $nome;
        }
    }
    return array_key_first($cidades);
}

function coord(float $base): string
{
    return number_format($base + mt_rand(-250, 250) / 10000, 8, '.', '');
}

// ---------- geração ----------

$hash = password_hash(SENHA, PASSWORD_DEFAULT);
$usuarios = [];
$perfis = [];
$categorias = [];
$usados = [];
$id = 0;

$novoUsuario = static function (string $tipo, string $genero) use (&$id, &$usados, $nomesF, $nomesM, $sobrenomes, $cidades, $hash): array {
    do {
        $primeiro = $genero === 'Feminino' ? pick($nomesF) : pick($nomesM);
        $nome = $primeiro . ' ' . pick($sobrenomes) . ' ' . pick($sobrenomes);
    } while (isset($usados[$nome]));
    $usados[$nome] = true;

    $id++;
    $cidade = weightedCity($cidades);
    [$lat, $lon] = $cidades[$cidade];
    $partes = explode(' ', $nome);

    return [
        'id'        => $id,
        'tipo'      => $tipo,
        'nome'      => $nome,
        'email'     => slug($partes[0]) . '.' . slug(end($partes)) . $id . '@teste.com',
        'senha'     => $hash,
        'cpf'       => cpf(100000000 + $id * 7919),
        'data_nasc' => sprintf('%d-%02d-%02d', mt_rand(1965, 2005), mt_rand(1, 12), mt_rand(1, 28)),
        'genero'    => $genero,
        'telefone'  => ($cidade === 'São Paulo' ? '11' : ($cidade === 'Campinas' ? '19' : '18')) . '9' . mt_rand(10000000, 99999999),
        'estado'    => 'SP',
        'cidade'    => $cidade,
        'latitude'  => coord($lat),
        'longitude' => coord($lon),
        'dias'      => mt_rand(5, 400),
    ];
};

// Contas fixas, fáceis de lembrar
$fixas = [
    ['cliente', 'Cliente Teste', 'cliente@teste.com', 'Feminino', 'Presidente Prudente'],
    ['prestador', 'Prestador Teste', 'prestador@teste.com', 'Masculino', 'Presidente Prudente'],
];
foreach ($fixas as [$tipo, $nome, $email, $genero, $cidade]) {
    $u = $novoUsuario($tipo, $genero);
    $u['nome'] = $nome;
    $u['email'] = $email;
    $u['cidade'] = $cidade;
    $u['latitude'] = coord($cidades[$cidade][0]);
    $u['longitude'] = coord($cidades[$cidade][1]);
    $u['dias'] = 30;
    $usuarios[] = $u;
}
$perfis[2] = ['Eletricista', 'Conta de teste do tipo prestador. Use para ver o painel e responder conversas.', 80, 250];
$categorias[2] = [1];

// Prestadores: todas as profissões, algumas repetidas
$listaProfissoes = array_merge(array_keys($profissoes), array_rand($profissoes, 16));
shuffle($listaProfissoes);
foreach ($listaProfissoes as $profissao) {
    [$cats, $min, $max, $descricao] = $profissoes[$profissao];
    $feminina = in_array($profissao, ['Cabeleireira', 'Manicure', 'Maquiadora', 'Confeiteira', 'Professora de inglês', 'Diarista'], true)
        || mt_rand(0, 3) === 0;
    $u = $novoUsuario('prestador', $feminina ? 'Feminino' : 'Masculino');
    $usuarios[] = $u;

    $variacao = $min === null || mt_rand(0, 6) === 0 ? null : 1 + mt_rand(-15, 20) / 100;
    $perfis[$u['id']] = [
        $profissao,
        mt_rand(0, 8) === 0 ? null : $descricao,
        $variacao ? round($min * $variacao) : null,
        $variacao ? round($max * $variacao) : null,
    ];
    $categorias[$u['id']] = $cats;
}

// Clientes
for ($i = 0; $i < 25; $i++) {
    $usuarios[] = $novoUsuario('cliente', mt_rand(0, 1) ? 'Feminino' : 'Masculino');
}

$clientes = array_values(array_filter($usuarios, static fn ($u) => $u['tipo'] === 'cliente'));
$prestadores = array_values(array_filter($usuarios, static fn ($u) => $u['tipo'] === 'prestador'));

// Administrador (sem dados pessoais)
$id++;
$usuarios[] = [
    'id' => $id, 'tipo' => 'admin', 'nome' => 'Administrador Nexus', 'email' => 'admin@teste.com', 'senha' => $hash,
    'cpf' => null, 'data_nasc' => null, 'genero' => null, 'telefone' => null, 'estado' => null, 'cidade' => null,
    'latitude' => null, 'longitude' => null, 'dias' => 400,
];

// Duas contas suspensas (um cliente e um prestador), para testar a moderação
$suspensos = [
    $clientes[array_key_last($clientes)]['id']       => 3,
    $prestadores[array_key_last($prestadores)]['id'] => 7,
];

// Horários de atendimento (dia_semana: 0 = domingo)
$modelosHorario = [
    'comercial'     => [[1, 2, 3, 4, 5], '08:00', '18:00'],
    'comercial_sab' => [[1, 2, 3, 4, 5], '08:00', '18:00', [6], '08:00', '12:00'],
    'manha'         => [[1, 2, 3, 4, 5, 6], '07:00', '12:00'],
    'tarde_noite'   => [[1, 2, 3, 4, 5], '13:00', '21:00'],
    'fim_de_semana' => [[5], '18:00', '23:00', [6], '10:00', '23:00', [0], '10:00', '18:00'],
    'todos_os_dias' => [[0, 1, 2, 3, 4, 5, 6], '07:00', '22:00'],
];
$horarios = [];
foreach ($prestadores as $p) {
    if ($p['email'] === 'prestador@teste.com') {
        $modelo = $modelosHorario['comercial_sab'];
    } elseif (mt_rand(1, 100) <= 15) {
        continue; // sem horários informados
    } else {
        $modelo = in_array(7, $categorias[$p['id']], true) ? $modelosHorario['fim_de_semana'] : pick($modelosHorario);
    }
    foreach (array_chunk($modelo, 3) as [$dias, $inicio, $fim]) {
        foreach ($dias as $dia) {
            $horarios[] = [$p['id'], $dia, $inicio, $fim];
        }
    }
}

// Avaliações: cada prestador recebe de 0 a 8 avaliações, com "qualidade" própria
$avaliacoes = [];
foreach ($prestadores as $p) {
    if ($p['email'] === 'prestador@teste.com') {
        continue;
    }
    $qualidade = mt_rand(25, 50) / 10;
    $avaliadores = array_rand($clientes, mt_rand(1, 8));
    foreach ((array) $avaliadores as $ci) {
        if (mt_rand(0, 5) === 0) {
            continue;
        }
        $nota = max(1, min(5, (int) round($qualidade + mt_rand(-10, 10) / 10)));
        $avaliacoes[] = [$clientes[$ci]['id'], $p['id'], $nota, pick($comentarios[$nota]), mt_rand(1, 180)];
    }
}

// Conversas: o cliente de teste conversa com 3 prestadores e o prestador de teste recebe de 3 clientes
$conversas = [];
$clienteTeste = $usuarios[0]['id'];
$prestadorTeste = $usuarios[1]['id'];
foreach (array_slice($prestadores, 1, 3) as $p) {
    $conversas[] = [$clienteTeste, $p['id']];
}
foreach (array_slice($clientes, 1, 3) as $c) {
    $conversas[] = [$c['id'], $prestadorTeste];
}
for ($i = 0; $i < 12; $i++) {
    $par = [pick($clientes)['id'], pick($prestadores)['id']];
    if (!in_array($par, $conversas, true)) {
        $conversas[] = $par;
    }
}

// ---------- escrita do SQL ----------

$dir = __DIR__;
$out = [];
$out[] = "-- Nexus Network: BANCO DE TESTE (dados fictícios)";
$out[] = "-- Gerado por database/gerar-teste.php em " . date('d/m/Y H:i');
$out[] = "-- Cria o banco " . DB_NAME . " do zero. Todas as contas usam a senha " . SENHA . ".";
$out[] = "-- Contas fixas: cliente@teste.com, prestador@teste.com e admin@teste.com";
$out[] = '';
$out[] = 'DROP DATABASE IF EXISTS ' . DB_NAME . ';';
$out[] = 'CREATE DATABASE ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
$out[] = 'USE ' . DB_NAME . ';';
$out[] = '';
$out[] = file_get_contents("{$dir}/schema.sql");
$out[] = file_get_contents("{$dir}/dados-base.sql");

$rows = [];
foreach ($usuarios as $u) {
    $rows[] = sprintf(
        '(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW() - INTERVAL %d DAY, %s)',
        $u['id'], q($u['tipo']), q($u['nome']), q($u['email']), q($u['senha']), q($u['cpf']), q($u['data_nasc']),
        q($u['genero']), q($u['telefone']), q($u['estado']), q($u['cidade']),
        $u['latitude'] ?? 'NULL', $u['longitude'] ?? 'NULL', $u['dias'],
        isset($suspensos[$u['id']]) ? 'NOW() - INTERVAL ' . $suspensos[$u['id']] . ' DAY' : 'NULL'
    );
}
$out[] = "INSERT INTO usuarios (id, tipo, nome, email, senha, cpf, data_nasc, genero, telefone, estado, cidade, latitude, longitude, criado_em, suspenso_em) VALUES\n"
    . implode(",\n", $rows) . ";\n";

$rows = [];
foreach ($perfis as $uid => [$profissao, $descricao, $min, $max]) {
    $rows[] = sprintf('(%d, %s, %s, %s, %s)', $uid, q($profissao), q($descricao), $min ?? 'NULL', $max ?? 'NULL');
}
$out[] = "INSERT INTO prestador_perfis (usuario_id, profissao, descricao, preco_min, preco_max) VALUES\n" . implode(",\n", $rows) . ";\n";

$rows = [];
foreach ($categorias as $uid => $cats) {
    foreach ($cats as $cat) {
        $rows[] = "({$uid}, {$cat})";
    }
}
$out[] = "INSERT INTO prestador_categorias (prestador_id, categoria_id) VALUES\n" . implode(",\n", $rows) . ";\n";

$rows = [];
foreach ($horarios as [$pid, $dia, $inicio, $fim]) {
    $rows[] = "({$pid}, {$dia}, '{$inicio}', '{$fim}')";
}
$out[] = "INSERT INTO prestador_horarios (prestador_id, dia_semana, inicio, fim) VALUES\n" . implode(",\n", $rows) . ";\n";

$rows = [];
foreach ($avaliacoes as [$cli, $pres, $nota, $comentario, $dias]) {
    $rows[] = sprintf('(%d, %d, %d, %s, NOW() - INTERVAL %d DAY)', $cli, $pres, $nota, q($comentario), $dias);
}
$out[] = "INSERT INTO avaliacoes (cliente_id, prestador_id, nota, comentario, criado_em) VALUES\n" . implode(",\n", $rows) . ";\n";

$convRows = [];
$msgRows = [];
foreach ($conversas as $i => [$cli, $pres]) {
    $cid = $i + 1;
    $dias = mt_rand(0, 20);
    $convRows[] = "({$cid}, {$cli}, {$pres}, NOW() - INTERVAL {$dias} DAY - INTERVAL 1 HOUR)";
    $modelo = pick($conversaModelos);
    foreach ($modelo as $j => $texto) {
        $remetente = $j % 2 === 0 ? $cli : $pres;
        $ultima = $j === count($modelo) - 1;
        $msgRows[] = sprintf(
            '(%d, %d, %s, %d, NOW() - INTERVAL %d DAY - INTERVAL %d MINUTE)',
            $cid, $remetente, q($texto), $ultima ? 0 : 1, $dias, (count($modelo) - $j) * 7
        );
    }
}
$out[] = "INSERT INTO conversas (id, cliente_id, prestador_id, criado_em) VALUES\n" . implode(",\n", $convRows) . ";\n";
$out[] = "INSERT INTO mensagens (conversa_id, remetente_id, conteudo, lida, enviada_em) VALUES\n" . implode(",\n", $msgRows) . ";\n";

$out[] = "INSERT INTO contatos (nome, email, telefone, assunto, mensagem, status, resposta, respondido_em, criado_em) VALUES\n"
    . "('Visitante Teste', 'visitante@teste.com', NULL, 'duvida', 'Como faço para aparecer nas buscas de outra cidade?', 'novo', NULL, NULL, NOW() - INTERVAL 2 HOUR),\n"
    . "('Roberta Lima', 'roberta@teste.com', '18991112222', 'problema', 'Não consigo alterar minha foto de perfil, aparece um erro.', 'novo', NULL, NULL, NOW() - INTERVAL 1 DAY),\n"
    . "('Marcos Vieira', 'marcos@teste.com', NULL, 'parceria', 'Tenho uma loja de materiais de construção e gostaria de anunciar no site.', 'lido', NULL, NULL, NOW() - INTERVAL 3 DAY),\n"
    . "('Clara Duarte', 'clara@teste.com', NULL, 'sugestao', 'Seria ótimo poder salvar profissionais favoritos.', 'respondido', "
    . "'Olá, Clara! Obrigado pela sugestão, ela já está na nossa lista de melhorias.', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 5 DAY);\n";

$out[] = file_get_contents("{$dir}/dados-teste-extra.sql");

file_put_contents("{$dir}/teste.sql", implode("\n", $out));

printf(
    "teste.sql gerado: %d clientes, %d prestadores, %d avaliações, %d conversas, %d mensagens.\n",
    count($clientes), count($prestadores), count($avaliacoes), count($conversas), count($msgRows)
);

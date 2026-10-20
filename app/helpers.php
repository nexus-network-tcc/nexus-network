<?php

/** Escapa texto para HTML. Os dados são guardados crus e escapados só na saída. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/** Caminho base da aplicação no servidor, ex.: "/Nexus Network Company". */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = rtrim(preg_replace('#/public$#', '', dirname($script)), '/');
    }
    return $base;
}

/** Caminho da requisição atual, relativo à aplicação ("/buscar"). */
function request_path(): string
{
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = preg_replace('#^/public(?=/|$)#', '', $uri);
    $uri = preg_replace('#^/index\.php(?=/|$)#', '', $uri);
    return '/' . ltrim($uri, '/');
}

function url(string $path = '/', array $query = []): string
{
    $encodedBase = implode('/', array_map('rawurlencode', explode('/', base_path())));
    $url = $encodedBase . '/' . ltrim($path, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

function absolute_url(string $path = '/'): string
{
    $configured = Config::get('app.url');
    if ($configured) {
        return $configured . '/' . ltrim($path, '/');
    }
    $scheme = is_https() ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

function asset(string $path): string
{
    $file = ROOT_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $version;
}

function redirect(string $path, array $query = []): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path, $query);
    header('Location: ' . $target);
    exit;
}

function back(string $fallback = '/'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $host) {
        header('Location: ' . $referer);
        exit;
    }
    redirect($fallback);
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function abort(int $status, ?string $message = null): never
{
    // 419 (sessão expirada) não é um código padrão; o Apache o trocaria por 500
    http_response_code($status === 419 ? 403 : $status);
    $titles = [
        403 => 'Acesso negado',
        404 => 'Página não encontrada',
        405 => 'Ação não permitida',
        419 => 'Sessão expirada',
        500 => 'Algo deu errado',
    ];
    $data = [
        'title'   => $titles[$status] ?? 'Erro',
        'status'  => $status,
        'message' => $message ?? ($status === 404
            ? 'O endereço que você procurou não existe ou foi removido.'
            : 'Não foi possível completar a ação.'),
    ];
    try {
        View::render('errors/error', $data);
    } catch (Throwable $e) {
        // Sem banco não dá para montar o layout completo
        echo '<!doctype html><meta charset="utf-8"><title>' . e($data['title']) . '</title>'
            . '<body style="background:#000;color:#fff;font-family:sans-serif;padding:48px">'
            . '<h1 style="color:#20cd8d">' . e($data['title']) . '</h1><p>' . e($data['message']) . '</p></body>';
    }
    exit;
}

function csrf_field(): string
{
    return Csrf::field();
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

function error_for(string $field): string
{
    $errors = Session::errors();
    return isset($errors[$field]) ? '<p class="field-error">' . e($errors[$field]) . '</p>' : '';
}

function has_error(string $field): string
{
    return isset(Session::errors()[$field]) ? ' is-invalid' : '';
}

function input(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function is_active(string $path): string
{
    $current = request_path();
    $match = $path === '/' ? $current === '/' : str_starts_with($current, $path);
    return $match ? ' is-active' : '';
}

/** URL da foto do usuário (local, externa do Google ou avatar padrão). */
function avatar_url(?string $foto): string
{
    if ($foto) {
        if (preg_match('#^https://#', $foto)) {
            return $foto;
        }
        if (is_file(ROOT_PATH . '/public/' . $foto)) {
            return url($foto);
        }
    }
    return asset('img/default-avatar.png');
}

/** Foto do usuário ou, se não houver, um círculo com as iniciais. Tamanhos: sm, md, lg, xl. */
function avatar(?string $foto, string $name, string $size = 'md'): string
{
    $class = 'avatar avatar-' . $size;
    if ($foto && (preg_match('#^https://#', $foto) || is_file(ROOT_PATH . '/public/' . $foto))) {
        return '<img class="' . $class . '" src="' . e(avatar_url($foto)) . '" alt="Foto de ' . e($name) . '" loading="lazy" referrerpolicy="no-referrer">';
    }
    return '<span class="' . $class . ' avatar-initials" aria-hidden="true">' . e(initials($name)) . '</span>';
}

function first_name(string $name): string
{
    return explode(' ', trim($name))[0] ?? $name;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function money(?float $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function price_range(mixed $min, mixed $max): string
{
    if ($min === null && $max === null) {
        return 'A combinar';
    }
    if ($min !== null && $max !== null && (float) $min !== (float) $max) {
        return money((float) $min) . ' – ' . money((float) $max);
    }
    return 'A partir de ' . money((float) ($min ?? $max));
}

function distance_label(?float $km): ?string
{
    if ($km === null) {
        return null;
    }
    if ($km < 1) {
        return 'menos de 1 km';
    }
    return number_format($km, $km < 10 ? 1 : 0, ',', '.') . ' km';
}

function format_phone(string $digits): string
{
    $d = preg_replace('/\D/', '', $digits);
    if (strlen($d) === 11) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
    }
    if (strlen($d) === 10) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
    }
    return $digits;
}

function format_cpf(string $digits): string
{
    $d = preg_replace('/\D/', '', $digits);
    return strlen($d) === 11
        ? sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9))
        : $digits;
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    return $date ? date($format, strtotime($date)) : '';
}

function day_label(string $date): string
{
    return match ($date) {
        date('Y-m-d') => 'Hoje',
        date('Y-m-d', strtotime('-1 day')) => 'Ontem',
        default => format_date($date),
    };
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'agora';
    }
    if ($diff < 3600) {
        return 'há ' . floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return 'há ' . floor($diff / 3600) . ' h';
    }
    if ($diff < 86400 * 7) {
        $days = floor($diff / 86400);
        return 'há ' . $days . ($days == 1 ? ' dia' : ' dias');
    }
    return date('d/m/Y', strtotime($datetime));
}

/** Estrelas (cheias, meias e vazias) para uma nota de 0 a 5. */
/** Selo de profissional verificado (concedido pelo admin). $text mostra a legenda ao lado do ícone. */
function verified_badge(?string $verifiedAt, bool $text = false): string
{
    if (!$verifiedAt) {
        return '';
    }
    return ' <span class="verified-badge" title="Profissional verificado pela equipe Nexus"><i class="fa-solid fa-circle-check"></i>'
        . ($text ? ' Verificado' : '<span class="sr-only">Verificado</span>') . '</span>';
}

function stars(float $rating): string
{
    $html = '<span class="stars" aria-label="' . e(number_format($rating, 1, ',', '')) . ' de 5 estrelas">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fa-solid fa-star"></i>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return $html . '</span>';
}

function partial(string $name, array $data = []): string
{
    return View::capture('partials/' . $name, $data);
}

function estados(): array
{
    return [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
        'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];
}

function generos(): array
{
    return ['Masculino', 'Feminino', 'Outro', 'Prefiro não informar'];
}

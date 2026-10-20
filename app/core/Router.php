<?php

final class Router
{
    private array $routes = [];

    public function get(string $pattern, string $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, string $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, string $handler, array $middleware): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[0-9]+)', rtrim($pattern, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'middleware');
    }

    public function dispatch(string $method, string $path): void
    {
        $path = rtrim($path, '/') ?: '/';
        $allowed = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $allowed = true;
            if ($route['method'] !== $method) {
                continue;
            }

            if ($method === 'POST' && !Csrf::valid()) {
                $this->fail(419, 'Sua sessão expirou. Recarregue a página e tente novamente.');
                return;
            }

            foreach ($route['middleware'] as $rule) {
                if (!$this->passes($rule)) {
                    return;
                }
            }

            $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $action] = explode('@', $route['handler']);
            (new $class())->$action(...array_values($params));
            return;
        }

        $allowed ? $this->fail(405, 'Método não permitido.') : $this->fail(404);
    }

    private function passes(string $rule): bool
    {
        $isApi = str_starts_with(request_path(), '/api/');

        switch ($rule) {
            case 'guest':
                if (Auth::check()) {
                    redirect('/');
                }
                return true;

            case 'auth':
            case 'membro':
            case 'cliente':
            case 'prestador':
            case 'admin':
                if (!Auth::check()) {
                    if ($isApi) {
                        json_response(['erro' => 'Faça login para continuar.'], 401);
                    }
                    Session::put('intended', $_SERVER['REQUEST_URI'] ?? null);
                    Session::flash('info', 'Entre na sua conta para continuar.');
                    redirect('/entrar');
                }
                $tipo = Auth::user()['tipo'];
                // "membro" = cliente ou prestador; o admin usa o painel
                if ($rule === 'membro' && $tipo === 'admin' && !$isApi) {
                    redirect('/admin');
                }
                $allowed = match ($rule) {
                    'auth'   => true,
                    'membro' => $tipo !== 'admin',
                    default  => $tipo === $rule,
                };
                if (!$allowed) {
                    $this->fail(403, match ($rule) {
                        'cliente'   => 'Esta área é exclusiva para clientes.',
                        'prestador' => 'Esta área é exclusiva para prestadores.',
                        'admin'     => 'Esta área é exclusiva para administradores.',
                        default     => 'Você não tem acesso a esta área.',
                    });
                    return false;
                }
                return true;
        }

        throw new LogicException("Middleware desconhecido: {$rule}");
    }

    private function fail(int $status, ?string $message = null): void
    {
        if (str_starts_with(request_path(), '/api/')) {
            json_response(['erro' => $message ?? 'Não encontrado.'], $status === 419 ? 403 : $status);
        }
        abort($status, $message);
    }
}

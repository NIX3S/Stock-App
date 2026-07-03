<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, array{handler: callable, middlewares: array}>> */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, callable $handler, array $middlewares): void
    {
        $this->routes[$method][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        // Routes statiques d'abord
        if (isset($this->routes[$method][$path])) {
            $this->run($this->routes[$method][$path], $request, []);
            return;
        }

        // Routes avec paramètres dynamiques {param}
        foreach ($this->routes[$method] ?? [] as $routePath => $route) {
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->run($route, $request, $matches);
                return;
            }
        }

        http_response_code(404);
        Response::view('errors/404', [], 'guest');
    }

    private function run(array $route, Request $request, array $params): void
    {
        foreach ($route['middlewares'] as $middleware) {
            $middleware->handle($request);
        }
        call_user_func_array($route['handler'], array_merge([$request], $params));
    }
}

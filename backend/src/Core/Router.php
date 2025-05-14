<?php

namespace Src\Core;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, callable|string $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes[$method] ?? [] as $routePath => $handler) {
            $routePattern = preg_replace('/\{[^\/]+\}/', '([^/]+)', $routePath);
            $routePattern = '#^' . $routePattern . '$#';
            if (preg_match($routePattern, $path, $matches)) {
                array_shift($matches); // Remove the full match
                $this->executeHandler($handler, $matches);
                return;
            }
        }

        Response::notFound();
    }

    private function executeHandler(callable|string $handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } else {
            [$class, $method] = explode('@', $handler);
            $controller = new ("Src\\Controllers\\$class")();
            call_user_func_array([$controller, $method], $params);
        }
    }
}

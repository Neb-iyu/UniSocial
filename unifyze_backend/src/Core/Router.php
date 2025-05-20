<?php

namespace Src\Core;

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        if ($basePath !== null) {
            $this->basePath = $basePath;
        } elseif (!empty($_ENV['ROUTER_BASE_PATH'])) {
            $this->basePath = $_ENV['ROUTER_BASE_PATH'];
        } else {
            $this->basePath = '/';
        }
    }

    public function addRoute(string $method, string $path, callable|string $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strips base path from the request URI only if basePath is not '/'
        if ($this->basePath !== '/' && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }

        // Ensure path starts with a forward slash
        if (empty($path)) {
            $path = '/';
        }

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

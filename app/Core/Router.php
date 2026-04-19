<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function add($method, $path, $handler, $middlewares = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch($uri, $requestMethod) {
        $parsedUrl = parse_url($uri);
        $path = $parsedUrl['path'];

        $basePath = '/public';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '' || $path === false) {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['path'] === $path && $route['method'] === $requestMethod) {
                
                foreach ($route['middlewares'] as $middleware) {
                    call_user_func([$middleware, 'protect']);
                }

                $controllerName = $route['handler'][0];
                $methodName = $route['handler'][1];

                $controller = new $controllerName();
                $controller->$methodName();
                return;
            }
        }

        self::abort(404, 'Endpoint nao encontrado.');
    }

    private static function abort($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
}
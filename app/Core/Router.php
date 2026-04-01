<?php
namespace App\Core;

use Exception;

class Router
{
    private $routes = [];
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function add($path, $controllerAction, $middlewares = [])
    {
        $this->routes[$path] = [
            'type' => 'action',
            'action' => $controllerAction,
            'middlewares' => $middlewares
        ];
    }

    public function addView($path, $viewFile, $middlewares = [])
    {
        $this->routes[$path] = [
            'type' => 'view',
            'file' => $viewFile,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch($url)
    {
        $url = urldecode(parse_url($url, PHP_URL_PATH));
        $url = str_replace('\\', '/', $url); // Normalizar barras para Windows

        $scriptPath = urldecode($_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('\\', '/', rtrim(dirname($scriptPath), '/\\'));

        // Remover el prefijo del directorio base (sin importar el caso)
        if (!empty($baseDir) && $baseDir !== '/') {
            if (stripos($url, $baseDir) === 0) {
                $url = substr($url, strlen($baseDir));
            }
        }

        // Limpiar en caso de que quede /public/ o .php
        $url = str_ireplace('/public', '', $url);
        $url = str_ireplace('.php', '', $url);
        $url = rtrim($url, '/');

        if (empty($url) || $url === 'index') {
            $url = '/';
        }

        if (array_key_exists($url, $this->routes)) {
            $route = $this->routes[$url];

            // Ejecutar Middlewares
            foreach ($route['middlewares'] as $middleware) {
                $this->executeMiddleware($middleware);
            }

            if (isset($route['type']) && $route['type'] === 'view') {
                $this->executeView($route['file']);
            } else {
                $this->executeAction($route['action']);
            }
        } else {
            // Si la ruta no existe, mostrar 404
            http_response_code(404);
            echo "<h1>404 Not Found</h1>";
            return false;
        }
        return true;
    }

    private function executeMiddleware($middleware)
    {
        if (is_string($middleware)) {
            $middlewareClass = "App\\Middlewares\\" . $middleware;
            $instance = new $middlewareClass();
            $instance->handle();
        } elseif (is_array($middleware)) {
            // Soporte para middleware con parámetros, ej: ['RoleMiddleware' => ['admin']]
            $class = key($middleware);
            $params = current($middleware);
            $middlewareClass = "App\\Middlewares\\" . $class;
            $instance = new $middlewareClass($params);
            $instance->handle();
        }
    }

    private function executeAction($controllerAction)
    {
        list($controllerName, $action) = explode('@', $controllerAction);

        $fullControllerName = "App\\Controllers\\" . $controllerName;

        if (class_exists($fullControllerName)) {
            $controller = new $fullControllerName($this->pdo);
            if (method_exists($controller, $action)) {
                $controller->$action();
            } else {
                throw new Exception("Acción $action no encontrada en $controllerName");
            }
        } else {
            throw new Exception("Controlador $controllerName no encontrado");
        }
    }

    private function executeView($file)
    {
        $path = __DIR__ . '/../../views/pages/' . $file;
        if (file_exists($path)) {
            global $pdo;
            require_once $path;
        } else {
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>Vista $file no encontrada.</p>";
        }
    }
}

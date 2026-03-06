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
            'action' => $controllerAction,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch($url)
    {
        // Limpiar la URL de parámetros GET si existen
        $url = parse_url($url, PHP_URL_PATH);

        // Remover el directorio base si el proyecto no está en la raíz
        // Para este proyecto asumimos que public es la raíz o se maneja vía .htaccess

        if (array_key_exists($url, $this->routes)) {
            $route = $this->routes[$url];

            // Ejecutar Middlewares
            foreach ($route['middlewares'] as $middleware) {
                $this->executeMiddleware($middleware);
            }

            $this->executeAction($route['action']);
        } else {
            // Si la ruta no existe en el Router, por ahora permitimos que el sistema siga
            // su flujo normal para mantener compatibilidad con las páginas .php actuales.
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
}

<?php
namespace App\Core;

class ErrorHandler
{
    /**
     * Determina si la petición actual es una API (devuelve JSON).
     */
    private static function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false || strpos($uri, '/api') === 0;
    }

    /**
     * Maneja una excepción centralizando el registro y la respuesta.
     * API (/api/*) → JSON. Web → vista HTML.
     *
     * @param \Exception|\Throwable $e
     */
    public static function handle($e)
    {
        // 1. Crear carpeta de logs si no existe
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // 2. Registrar el error en logs/app.log
        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();

        $logEntry = "[$timestamp] ERROR: $message in $file on line $line" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        $showDetails = (getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? '') === 'development';

        if (self::isApiRequest() && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
            $payload = [
                'status' => 'error',
                'message' => $showDetails ? $message : 'Ha ocurrido un error inesperado.'
            ];
            if ($showDetails) {
                $payload['file'] = $file;
                $payload['line'] = $line;
            }
            echo json_encode($payload);
            exit();
        }

        // Vista HTML para peticiones web
        if (!headers_sent()) {
            // Intentamos encontrar la ruta a la vista de error
            // Dependiendo de dónde se llame, la ruta relativa puede variar.
            // Lo más seguro es usar una ruta relativa al document root o una absoluta.
            // Dado que estamos en un entorno web, una redirección a /views/errors/error.php podría funcionar si el servidor está configurado así,
            // pero en este proyecto parece que entramos por /public/.

            // Si estalló en un API o controlador, redirigimos a la vista.
            // Ajustamos la ruta para que sea accesible desde el navegador.
            // Generalmente las vistas no son accesibles directamente si están fuera de public, 
            // pero en este proyecto parece que algunas cosas se incluyen.

            // Cargar vista de error
            // Pero si el usuario pide "Redirigir a views/errors/error.php", tal vez se refiera a una URL o a cargar el archivo.
            // Si es redirección (header Location), el archivo debe ser accesible vía HTTP.

            // Si views/ no es accesible vía HTTP, tendríamos que usar un archivo en public/ que la incluya.
            // Pero seguiré la instrucción lo más literal posible: "Redirigir a views/errors/error.php"
            // Aunque probablemente necesite un archivo puente en public o ajustar la ruta.

            // Vamos a verificar si hay un archivo de error ya existente o cómo se sirven las páginas.
            // Según public/dashboard.php, las páginas están en public/.

            http_response_code(500);
            require_once __DIR__ . '/../../views/errors/error.php';
            exit();
        } else {
            // Si ya se enviaron cabeceras, mostramos un mensaje simple.
            echo "Ha ocurrido un error inesperado. Por favor, contacte al administrador.";
            if ($showDetails) {
                echo "<br><pre>" . htmlspecialchars((string) $e) . "</pre>";
            }
            exit();
        }
    }
}

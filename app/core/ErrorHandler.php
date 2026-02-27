<?php
namespace App\Core;

class ErrorHandler
{
    /**
     * Maneja una excepción centralizando el registro y la redirección.
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

        // 3. Determinar si mostrar detalles (puedes usar una constante o variable de entorno)
        // Por ahora, asumimos que no mostramos detalles técnicos en producción.
        $showDetails = defined('APP_ENV') && APP_ENV === 'development';

        // 4. Redirigir a vista de error amigable
        // Usamos una ruta absoluta o relativa confiable
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

            // La instrucción dice: Redirigir a views/errors/error.php
            // Sin embargo, views/ está fuera de public/. 
            // Normalmente se cargaría a través de un controlador o se incluiría.
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

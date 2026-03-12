<?php
namespace App\Helpers;

class UrlHelper
{
    /**
     * Genera la URL absoluta para una ruta interna del proyecto.
     * Ej: url('login') => /HospitAll V1/public/login
     * Ej: url('dashboard') => /HospitAll V1/public/dashboard
     */
    public static function url(string $path = ''): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $path = ltrim($path, '/');
        return $basePath . '/' . $path;
    }

    /**
     * Redirige a una ruta interna del proyecto y termina la ejecución.
     */
    public static function redirect(string $path, array $params = []): void
    {
        $url = self::url($path);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        header("Location: " . $url);
        exit();
    }
}

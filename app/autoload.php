<?php
/**
 * HospitAll Autoloader
 * Carga automáticamente clases de controllers, services y helpers.
 */
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';

    // Directorios donde buscar clases
    $directories = [
        'controllers/',
        'services/',
        'helpers/'
    ];

    foreach ($directories as $dir) {
        $file = $baseDir . $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

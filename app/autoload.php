<?php
/**
 * HospitAll Autoloader
 * Carga automáticamente clases de controllers, services y helpers.
 */
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';

    // 1. Soporte PSR-4 para namespace raíz "App\"
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2. Fallback para clases sin namespace (sistema actual)
    $directories = [
        'Controllers/',
        'Services/',
        'helpers/',
        'Core/'
    ];

    foreach ($directories as $dir) {
        $file = $baseDir . $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

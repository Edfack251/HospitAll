<?php
namespace App\Helpers;

use App\Services\LogService;

class AuthHelper
{
    public static function checkRole($allowedRoles)
    {
        self::requireLogin();

        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            // Auditoría: Intento de acceso no autorizado
            global $pdo;
            if (isset($pdo)) {
                $logService = new LogService($pdo);
                $logService->register(
                    $_SESSION['user_id'] ?? null,
                    'Intento acceso no autorizado',
                    'Seguridad',
                    $_SERVER['REQUEST_URI'],
                    'WARNING'
                );
            }

            UrlHelper::redirect('dashboard', ['error' => 'unauthorized']);
        }
    }

    public static function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Tiempo de inactividad máximo (15 minutos)
        $timeout_duration = 15 * 60;

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
            session_unset();
            session_destroy();
            UrlHelper::redirect('login', ['error' => 'session_expired']);
        }

        // Actualizar el tiempo de última actividad
        $_SESSION['last_activity'] = time();

        if (!isset($_SESSION['user_id'])) {
            UrlHelper::redirect('login');
        }

        // Cabeceras para evitar el caché del navegador (Problema del botón "atrás")
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
    }

    public static function requireRole($role)
    {
        self::requireLogin();

        if (($_SESSION['user_role'] ?? '') !== $role) {
            UrlHelper::redirect('dashboard', ['error' => 'unauthorized']);
        }
    }
}

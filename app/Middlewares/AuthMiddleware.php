<?php
namespace App\Middlewares;

use App\Helpers\AuthHelper;

class AuthMiddleware
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Detectar y limpiar sesiones inconsistentes (Fix loop redirecciones)
        if (isset($_SESSION['user_id']) && (!isset($_SESSION['user_role']) || !isset($_SESSION['user']))) {
            session_unset();
            session_destroy();
            \App\Helpers\UrlHelper::redirect('login', ['error' => 'session_inconsistent']);
        }

        AuthHelper::requireLogin();
    }
}

<?php
namespace App\Middlewares;

use App\Helpers\CsrfHelper;
use Exception;

class CsrfMiddleware
{
    public function handle()
    {
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

            if (!CsrfHelper::validateToken($token)) {
                http_response_code(403);
                throw new Exception("Error de Seguridad: Token CSRF inválido o ausente.");
            }
        }
    }
}

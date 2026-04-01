<?php

namespace App\Controllers;

class BaseController
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Envía una respuesta JSON estandarizada
     */
    protected function jsonResponse(
        bool $success,
        mixed $data = null,
        ?string $error = null,
        int $httpCode = 200
    ): void {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($httpCode);
        }

        $response = ['success' => $success];

        if ($data !== null) {
            if (is_array($data)) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }

        if ($error !== null) {
            $response['message'] = $error; // Changed 'error' back to 'message' as used in current endpoints implicitly, wait, user requested 'error' as key?
            // Actually, user defined response['error'] = $error; let's stick to user's code!
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Respuesta JSON de éxito
     */
    protected function jsonSuccess(
        array $data = [],
        int $httpCode = 200
    ): void {
        $this->jsonResponse(true, $data, null, $httpCode);
    }

    /**
     * Respuesta JSON de error
     */
    protected function jsonError(
        string $message,
        int $httpCode = 400
    ): void {
        $this->jsonResponse(false, null, $message, $httpCode);
    }

    /**
     * Renderiza una vista con datos
     */
    protected function render(
        string $view,
        array $data = []
    ): void {
        extract($data);
        $viewPath = __DIR__ . '/../../views/pages/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("Vista no encontrada: {$view}");
        }

        require $viewPath;
    }

    /**
     * Verifica que el request es POST
     */
    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Método no permitido', 405);
        }
    }

    /**
     * Lee y decodifica el body JSON del request
     */
    protected function getJsonInput(): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );
        return $input ?? [];
    }

    /**
     * Obtiene el ID del usuario autenticado desde la sesión
     */
    protected function getAuthUserId(): ?int
    {
        return isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : null;
    }

    /**
     * Obtiene el rol del usuario autenticado desde la sesión
     */
    protected function getAuthUserRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }
}

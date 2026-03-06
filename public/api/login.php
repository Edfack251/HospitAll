<?php
session_start();
require_once '../../app/autoload.php';

use App\Config\Database;
use App\Controllers\AuthController;
use App\Helpers\CsrfHelper;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
}

$pdo = Database::getConnection();
$controller = new AuthController($pdo);
$controller->login();
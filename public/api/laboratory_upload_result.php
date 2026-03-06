<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\LaboratoryController;
use App\Helpers\CsrfHelper;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
    $controller = new LaboratoryController($pdo);
    $controller->uploadResult($_POST, $_FILES);
}
?>
<?php
require_once '../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Services\AuthService;

$authService = new AuthService($pdo);
$authService->logout();
?>
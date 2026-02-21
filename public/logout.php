<?php
require_once '../app/config/database.php';
require_once '../app/services/AuthService.php';

$authService = new AuthService($pdo);
$authService->logout();
?>
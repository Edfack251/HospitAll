<?php
use App\Services\AuthService;

$authService = new AuthService($pdo);
$authService->logout();
?>
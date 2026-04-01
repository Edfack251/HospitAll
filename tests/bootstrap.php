<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

if (!isset($_ENV['DB_HOST'])) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['last_activity'] = time();

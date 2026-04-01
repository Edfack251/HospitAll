<?php
$_SERVER['REQUEST_URI'] = '/queue_portal';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_ENV['APP_ENV'] = 'development';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['user_name'] = 'Admin';
$_SESSION['csrf_token'] = 'dummy';

require __DIR__ . '/index.php';

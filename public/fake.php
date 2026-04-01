<?php
$_SERVER['REQUEST_URI'] = '/pharmacy';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';

// Mock session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['user_name'] = 'Admin';
$_SESSION['csrf_token'] = 'dummy';

require __DIR__ . '/index.php';

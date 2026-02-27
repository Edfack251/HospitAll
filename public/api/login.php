<?php
session_start();
require_once '../../app/autoload.php';

use App\Config\Database;
use App\Controllers\AuthController;

$pdo = Database::getConnection();
$controller = new AuthController($pdo);
$controller->login();
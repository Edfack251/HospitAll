<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\RegisterController;

$controller = new RegisterController($pdo);
$controller->register();
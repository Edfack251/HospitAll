<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/autoload.php';

$controller = new AuthController($pdo);
$controller->login();
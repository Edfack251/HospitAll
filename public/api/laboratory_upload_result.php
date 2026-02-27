<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\LaboratoryController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new LaboratoryController($pdo);
    $controller->uploadResult($_POST, $_FILES);
}
?>
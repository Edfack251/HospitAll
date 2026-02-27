<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new LaboratoryController($pdo);
    $controller->uploadResult($_POST, $_FILES);
}
?>
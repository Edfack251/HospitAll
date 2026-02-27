<?php
require_once __DIR__ . '/../app/autoload.php';
use App\Core\ErrorHandler;

header('Content-Type: text/plain');

echo "VERIFICATION START\n";

if (class_exists('App\Core\ErrorHandler')) {
    echo "PSR-4_SUCCESS\n";
} else {
    echo "PSR-4_FAILURE\n";
}

if (class_exists('LaboratoryController')) {
    echo "LEGACY_SUCCESS\n";
} else {
    echo "LEGACY_FAILURE\n";
}

echo "VERIFICATION END\n";

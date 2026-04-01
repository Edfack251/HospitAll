<?php
require __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    $pdo = \App\Config\Database::getConnection();

    echo "Testing QueueController...\n";
    $c = new \App\Controllers\QueueController($pdo);
    echo "Queue OK\n";

    echo "Testing PharmacyController...\n";
    $c2 = new \App\Controllers\PharmacyController($pdo);
    echo "Pharmacy OK\n";

} catch (\Throwable $t) {
    echo "Error: " . $t->getMessage() . "\n" . $t->getTraceAsString();
}

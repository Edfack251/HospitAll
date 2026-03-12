<?php
require __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Services\PatientsService;
use App\Services\PharmacyService;
use App\Services\UsersService;

try {
    $pdo = Database::getConnection();
    echo "DB connected.\n";

    // Simulate session for AuthHelper and LogService
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['rol'] = 'administrador'; // needed for PharmacyService

    // Test UsersService
    $userService = new UsersService($pdo);
    $users = $userService->getAll(1);
    echo "Users fetched: " . count($users) . "\n";

    // Test PharmacyService
    $pharmacyService = new PharmacyService($pdo);
    $inventory = $pharmacyService->getInventory();
    echo "Inventory fetched: " . count($inventory) . "\n";

    // Test PatientsService
    $patientsService = new PatientsService($pdo);
    $patients = $patientsService->searchByIdentification('0000000000');
    echo "Patients fetched: " . count($patients) . "\n";

    echo "Basic Repository operations passed.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

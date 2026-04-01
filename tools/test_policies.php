<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Policies\PolicyManager;
use App\Core\Exceptions\AuthorizationException;

echo "--- Iniciando verificación de Políticas ---\n";

$admin = ['id' => 1, 'role' => 'administrador'];
$recep = ['id' => 2, 'role' => 'recepcionista'];
$medico = ['id' => 3, 'role' => 'medico'];

function testPermission($user, $action, $expected)
{
    $can = PolicyManager::can($user, $action);
    $status = ($can === $expected) ? "PASSED" : "FAILED";
    echo "User [{$user['role']}] can [$action]: Expected " . ($expected ? 'YES' : 'NO') . " | Result " . ($can ? 'YES' : 'NO') . " | $status\n";
}

testPermission($admin, 'create_user', true);
testPermission($admin, 'create_patient', true);
testPermission($recep, 'create_user', false);
testPermission($recep, 'create_patient', true);
testPermission($medico, 'create_user', false);
testPermission($medico, 'create_clinical_history', true);

echo "\n--- Probando Exception Throwing ---\n";

try {
    echo "Admin intentando create_user: ";
    PolicyManager::authorize($admin, 'create_user');
    echo "OK\n";
} catch (AuthorizationException $e) {
    echo "ERROR (No esperado): " . $e->getMessage() . "\n";
}

try {
    echo "Recepcionista intentando create_user: ";
    PolicyManager::authorize($recep, 'create_user');
    echo "FAILED (No capturado)\n";
} catch (AuthorizationException $e) {
    echo "OK (Capturado correctamente): " . $e->getMessage() . "\n";
}

echo "\n--- Verificación completada ---\n";

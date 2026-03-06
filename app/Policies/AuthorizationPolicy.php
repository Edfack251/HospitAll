<?php
namespace App\Policies;

use App\Config\Database;
use App\Services\LogService;

class AuthorizationPolicy
{
    private static $permissions = [
        'administrador' => [
            'create_user',
            'edit_user',
            'delete_user',
            'view_logs'
        ],
        'recepcionista' => [
            'create_patient',
            'edit_patient',
            'schedule_appointment'
        ],
        'medico' => [
            'create_clinical_history',
            'view_patient_history'
        ],
        'tecnico_laboratorio' => [
            'upload_lab_result'
        ],
        'paciente' => [
            'view_patient_portal'
        ]
    ];

    /**
     * Verifica si el usuario actual tiene permiso para realizar una acción.
     * Si no lo tiene, registra el intento, devuelve un HTTP 403 y detiene la ejecución.
     *
     * @param string $action Nombre de la acción a verificar
     * @return void
     */
    public static function authorize(string $action)
    {
        $role = $_SESSION['user_role'] ?? 'invitado';
        $role = strtolower($role);

        $hasPermission = false;

        if (array_key_exists($role, self::$permissions)) {
            if (in_array($action, self::$permissions[$role])) {
                $hasPermission = true;
            }
        }

        if (!$hasPermission) {
            $pdo = Database::getConnection();
            $logService = new LogService($pdo);
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_id'] ?? null;

            $logService->register($userId, 'Acceso denegado', 'Autorización', "Intento de acción no autorizada: $action por el rol: $role", 'ERROR');

            http_response_code(403);
            die("Error 403: No tienes permisos para realizar la acción solicitada.");
        }
    }
}

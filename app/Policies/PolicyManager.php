<?php
namespace App\Policies;

use App\Core\Exceptions\AuthorizationException;
use App\Config\Database;
use App\Services\LogService;

class PolicyManager
{
    private static $permissions = [
        'administrador' => [
            'create_user',
            'edit_user',
            'delete_user',
            'view_logs',
            'create_patient',
            'edit_patient',
            'delete_patient',
            'delete_doctor',
            'delete_medicine',
            'delete_lab_order',
            'delete_prescription',
            'view_patient',
            'restore_patient',
            'restore_user',
            'restore_doctor',
            'restore_medicine',
            'restore_lab_order',
            'restore_prescription',
            'create_invoice',
            'view_billing',
            'dispense_medicine',
            'manage_inventory',
            'view_laboratory',
            'manage_appointments',
            'view_inventory',
            'view_admin_dashboard',
            'view_imagenes',
            'upload_imagen_result',
            'register_emergency',
            'assign_doctor_emergency',
            'update_emergency_status',
            'create_emergency_patient',
            'schedule_appointment',
            'attend_emergency',
            'close_emergency',
            'create_clinical_history',
            'view_patient_history',
            'view_doctor_dashboard',
            'upload_lab_result',
            'register_vital_signs',
            'register_nursing_observation',
            'generate_turno',
            'call_turno',
            'manage_turnos',
            'internar_paciente',
            'registrar_ronda',
            'registrar_evolucion',
            'dar_alta',
            'view_hospitalizacion'
        ],
        'recepcionista' => [
            'create_patient',
            'edit_patient',
            'view_patient',
            'schedule_appointment',
            'create_invoice',
            'view_billing',
            'generate_turno',
            'manage_turnos',
            'view_laboratory',
            'view_hospitalizacion',
            'view_imagenes',
            'call_turno'
        ],
        'medico' => [
            'create_clinical_history',
            'view_patient_history',
            'schedule_appointment',
            'view_patient',
            'view_laboratory',
            'view_doctor_dashboard',
            'attend_emergency',
            'close_emergency',
            'call_turno',
            'internar_paciente',
            'registrar_evolucion',
            'dar_alta',
            'view_hospitalizacion',
            'view_imagenes'
        ],
        'farmaceutico' => [
            'dispense_medicine',
            'view_inventory',
            'manage_inventory',
            'view_billing',
            'call_turno'
        ],
        'tecnico_laboratorio' => [
            'upload_lab_result',
            'view_laboratory',
            'view_patient',
            'call_turno'
        ],
        'tecnico_imagenes' => [
            'view_imagenes',
            'upload_imagen_result',
            'view_patient',
            'call_turno'
        ],
        'enfermera' => [
            'view_patient',
            'register_vital_signs',
            'register_nursing_observation',
            'register_emergency',
            'assign_doctor_emergency',
            'update_emergency_status',
            'create_emergency_patient',
            'internar_paciente',
            'registrar_ronda',
            'view_hospitalizacion'
        ],
        'paciente' => [
            'view_own_patient_portal'
        ]
    ];

    /**
     * Verifica si el usuario tiene permiso para realizar una acción.
     * 
     * @param array|object $user El objeto de usuario o array con la información del rol.
     * @param string $action El nombre del permiso a verificar.
     * @return bool
     */
    public static function can($user, string $action): bool
    {
        $role = is_array($user) ? ($user['user_role'] ?? $user['role'] ?? $user['rol'] ?? null) : ($user->user_role ?? $user->role ?? $user->rol ?? null);

        if (!$role) {
            return false;
        }

        $role = strtolower($role);

        if (!isset(self::$permissions[$role])) {
            return false;
        }

        return in_array($action, self::$permissions[$role]);
    }

    /**
     * Autoriza una acción. Si falla, lanza una AuthorizationException y registra el evento.
     * 
     * @param array|object $user El objeto de usuario.
     * @param string $action El nombre del permiso.
     * @throws AuthorizationException
     */
    public static function authorize($user, string $action)
    {
        if (!self::can($user, $action)) {
            $userId = is_array($user) ? ($user['id'] ?? null) : ($user->id ?? null);
            $userRole = is_array($user) ? ($user['user_role'] ?? $user['role'] ?? $user['rol'] ?? 'unknown') : ($user->user_role ?? $user->role ?? $user->rol ?? 'unknown');

            // Registrar intento fallido
            $pdo = Database::getConnection();
            $logService = new LogService($pdo);
            $logService->register(
                $userId,
                'Acceso denegado (Policy)',
                'Seguridad',
                "Intento de acción '$action' no autorizada para el rol '$userRole'",
                'ERROR'
            );

            throw new AuthorizationException("No tienes permisos para realizar la acción: $action");
        }
    }
}

<?php
namespace App\Helpers;

class PrivacyHelper
{
    /**
     * Enmascara la cédula/identificación manteniendo 3 caracteres visibles al inicio y 2 al final.
     * Ej: 402******78
     *
     * @param string|null $cedula La cédula real del paciente.
     * @param int|null $owner_paciente_id (Opcional) El ID del paciente dueño de la cédula para validar contra la sesión.
     * @return string
     */
    public static function maskCedula(?string $cedula, ?int $owner_paciente_id = null): string
    {
        if (empty($cedula)) {
            return '';
        }

        $role = strtolower($_SESSION['user_role'] ?? 'invitado');

        // Administrador ve la cédula completa
        if ($role === 'administrador') {
            return $cedula;
        }

        // Si es el propio paciente logueado viendo sus propios datos, la ve completa
        if ($role === 'paciente' && $owner_paciente_id !== null) {
            $session_paciente_id = $_SESSION['paciente_id'] ?? null;
            if ($session_paciente_id != null && $session_paciente_id == $owner_paciente_id) {
                return $cedula;
            }
        }

        // Enmascaramiento para los demás roles (Médicos, Recepcionistas, Farmacéuticos, etc)
        $len = strlen($cedula);
        if ($len <= 5) {
            return str_repeat('*', $len);
        }

        // Ejemplo: Toma 3 del inicio, N asteriscos, 2 del final
        $prefix = substr($cedula, 0, 3);
        $suffix = substr($cedula, -2);

        return $prefix . str_repeat('*', $len - 5) . $suffix;
    }
}

<?php

class LogService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra una acción en la tabla de auditoría.
     * 
     * @param int $usuario_id ID del usuario que realiza la acción
     * @param string $accion Nombre corto de la acción
     * @param string $modulo Nombre del módulo (e.g., 'Pacientes', 'Citas')
     * @param string|null $descripcion Detalle opcional de la acción
     * @return bool
     */
    public function register($usuario_id, $accion, $modulo, $descripcion = null)
    {
        try {
            $sql = "INSERT INTO logs (usuario_id, accion, modulo, descripcion, ip) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            return $stmt->execute([$usuario_id, $accion, $modulo, $descripcion, $ip]);
        } catch (Exception $e) {
            // No lanzar errores visibles según requerimiento del issue
            error_log("Error in LogService::register: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los registros de auditoría con información de usuario.
     * 
     * @return array
     */
    public function getAll()
    {
        try {
            $sql = "SELECT l.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, r.nombre as rol_nombre
                    FROM logs l
                    JOIN usuarios u ON l.usuario_id = u.id
                    JOIN roles r ON u.rol_id = r.id
                    ORDER BY l.created_at DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in LogService::getAll: " . $e->getMessage());
            return [];
        }
    }
}

<?php
namespace App\Services;

use Exception;
use PDO;

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
     * @param string $nivel Nivel del log (INFO, WARNING, ERROR)
     * @return bool
     */
    public function register($usuario_id, $accion, $modulo, $descripcion = null, $nivel = 'INFO')
    {
        try {
            $sql = "INSERT INTO logs (usuario_id, accion, modulo, descripcion, ip, nivel, metodo_http, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $metodo = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            return $stmt->execute([$usuario_id, $accion, $modulo, $descripcion, $ip, $nivel, $metodo, $ua]);
        } catch (Exception $e) {
            // No lanzar errores visibles según requerimiento del issue
            error_log("Error in LogService::register: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca logs con filtros dinámicos.
     */
    public function search($filters = [], $limit = 50, $offset = 0)
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['usuario_id'])) {
                $where[] = "l.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }

            if (!empty($filters['modulo'])) {
                $where[] = "l.modulo = ?";
                $params[] = $filters['modulo'];
            }

            if (!empty($filters['fecha_desde'])) {
                $where[] = "l.created_at >= ?";
                $params[] = $filters['fecha_desde'] . ' 00:00:00';
            }

            if (!empty($filters['fecha_hasta'])) {
                $where[] = "l.created_at <= ?";
                $params[] = $filters['fecha_hasta'] . ' 23:59:59';
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $sql = "SELECT l.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, r.nombre as rol_nombre
                    FROM logs l
                    JOIN usuarios u ON l.usuario_id = u.id
                    JOIN roles r ON u.rol_id = r.id
                    $whereSql
                    ORDER BY l.created_at DESC
                    LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error in LogService::search: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los registros de auditoría con información de usuario.
     */
    public function getAll()
    {
        return $this->search();
    }

    /**
     * Obtiene la lista de módulos únicos registrados en logs.
     */
    public function getDistinctModules()
    {
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT modulo FROM logs ORDER BY modulo ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene la lista de usuarios para el filtro.
     */
    public function getUsersForFilter()
    {
        try {
            $stmt = $this->pdo->query("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}

<?php
namespace App\Repositories;

use PDO;

class LogRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getRecentLogs($limit = 5)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $stmt = $this->pdo->prepare("SELECT l.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, r.nombre as rol_nombre
                                     FROM logs l
                                     JOIN usuarios u ON l.usuario_id = u.id
                                     JOIN roles r ON u.rol_id = r.id
                                     ORDER BY l.created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($usuario_id, $accion, $modulo, $descripcion, $ip, $nivel, $metodo, $ua)
    {
        $sql = "INSERT INTO logs (usuario_id, accion, modulo, descripcion, ip, nivel, metodo_http, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$usuario_id, $accion, $modulo, $descripcion, $ip, $nivel, $metodo, $ua]);
    }

    public function search($filters = [], $limit = 50, $offset = 0)
    {
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

        // TODO: Refactorizar SELECT * cuando se estabilice la vista
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
    }

    public function getDistinctModules()
    {
        $stmt = $this->pdo->query("SELECT DISTINCT modulo FROM logs ORDER BY modulo ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getUsersForFilter()
    {
        $stmt = $this->pdo->query("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre ASC LIMIT 500");
        return $stmt->fetchAll();
    }
}

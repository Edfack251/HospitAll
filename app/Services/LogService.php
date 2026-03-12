<?php
namespace App\Services;

use Exception;
use PDO;
use App\Repositories\LogRepository;

class LogService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new LogRepository($pdo);
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
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $metodo = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            return $this->repo->create($usuario_id, $accion, $modulo, $descripcion, $ip, $nivel, $metodo, $ua);
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
            return $this->repo->search($filters, $limit, $offset);
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
            return $this->repo->getDistinctModules();
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
            return $this->repo->getUsersForFilter();
        } catch (Exception $e) {
            return [];
        }
    }
}

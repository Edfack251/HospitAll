<?php
namespace App\Controllers;

use App\Services\LogService;
use App\Policies\PolicyManager;

class LogController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new LogService($pdo);
    }

    /**
     * Retorna la lista de logs filtrada.
     */
    public function index()
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_logs');
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'modulo' => $_GET['modulo'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null
        ];

        return $this->service->search($filters, 20);
    }

    public function getFilterData()
    {
        return [
            'modulos' => $this->service->getDistinctModules(),
            'usuarios' => $this->service->getUsersForFilter()
        ];
    }

    public function export()
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_logs');
        $filters = [
            'usuario_id' => $_GET['usuario_id'] ?? null,
            'modulo' => $_GET['modulo'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null
        ];

        try {
            $logs = $this->service->search($filters, 10000); // Límite alto para exportación

            // Cabeceras para descarga de CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=auditoria_hospitall_' . date('Ymd_His') . '.csv');

            $output = fopen('php::output', 'w');

            // BOM para Excel (UTF-8)
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Encabezados
            fputcsv($output, ['ID', 'Fecha', 'Usuario', 'Rol', 'Módulo', 'Acción', 'Descripción', 'IP', 'Nivel', 'Método', 'User-Agent']);

            // Datos
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'],
                    $log['created_at'],
                    $log['usuario_nombre'] . ' ' . $log['usuario_apellido'],
                    $log['rol_nombre'],
                    $log['modulo'],
                    $log['accion'],
                    $log['descripcion'],
                    $log['ip'],
                    $log['nivel'],
                    $log['metodo_http'],
                    $log['user_agent']
                ]);
            }

            fclose($output);
            exit();
        } catch (\Exception $e) {
            die("Error al exportar logs: " . $e->getMessage());
        }
    }
}

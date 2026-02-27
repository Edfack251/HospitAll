<?php
session_start();
require_once __DIR__ . '/../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();

use App\Services\LogService;
use App\Helpers\AuthHelper;

// Validar rol administrador
AuthHelper::checkRole(['administrador']);

// Obtener filtros
$filters = [
    'usuario_id' => $_GET['usuario_id'] ?? null,
    'modulo' => $_GET['modulo'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? null,
    'fecha_hasta' => $_GET['fecha_hasta'] ?? null
];

$service = new LogService($pdo);
$logs = $service->search($filters, 10000); // Límite alto para exportación

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
?>
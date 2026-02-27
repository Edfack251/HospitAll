<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';
require_once '../app/autoload.php';

checkRole(['administrador']);

$controller = new LogController($pdo);
$logs = $controller->index();

$pageTitle = 'Auditoría del Sistema - HospitAll';
$activePage = 'auditoria';
$headerTitle = 'Registro de Auditoría';
$headerSubtitle = 'Monitoreo de acciones clave realizadas por los usuarios.';

include '../views/layout/header.php';
?>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="logsTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Fecha</th>
                <th class="py-4">Usuario</th>
                <th class="py-4">Rol</th>
                <th class="py-4">Módulo</th>
                <th class="py-4">Acción</th>
                <th class="py-4">Descripción</th>
                <th class="py-4">IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4 text-sm">
                        <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                    </td>
                    <td class="py-4 font-medium">
                        <?php echo htmlspecialchars($log['usuario_nombre'] . ' ' . $log['usuario_apellido']); ?>
                    </td>
                    <td class="py-4 text-xs font-semibold uppercase">
                        <?php echo htmlspecialchars($log['rol_nombre']); ?>
                    </td>
                    <td class="py-4">
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">
                            <?php echo htmlspecialchars($log['modulo']); ?>
                        </span>
                    </td>
                    <td class="py-4 font-medium text-blue-600">
                        <?php echo htmlspecialchars($log['accion']); ?>
                    </td>
                    <td class="py-4 text-sm text-gray-600">
                        <?php echo htmlspecialchars($log['descripcion'] ?: '-'); ?>
                    </td>
                    <td class="py-4 text-xs text-gray-400">
                        <?php echo htmlspecialchars($log['ip']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        $('#logsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        });
    });
</script>

<?php include '../views/layout/footer.php'; ?>
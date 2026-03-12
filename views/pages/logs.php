<?php
use App\Controllers\LogController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador']);

$controller = new LogController($pdo);
$logs = $controller->index();
$filterData = $controller->getFilterData();

$pageTitle = 'Auditoría del Sistema - HospitAll';
$activePage = 'auditoria';
$headerTitle = 'Registro de Auditoría';
$headerSubtitle = 'Monitoreo de acciones clave realizadas por los usuarios.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Filtros de Auditoría -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Usuario</label>
            <select name="usuario_id"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#007BFF] outline-none bg-white text-sm">
                <option value="">Todos los usuarios</option>
                <?php foreach ($filterData['usuarios'] as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $u['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Módulo</label>
            <select name="modulo"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#007BFF] outline-none bg-white text-sm">
                <option value="">Todos los módulos</option>
                <?php foreach ($filterData['modulos'] as $m): ?>
                    <option value="<?php echo htmlspecialchars($m); ?>" <?php echo (isset($_GET['modulo']) && $_GET['modulo'] == $m) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Desde</label>
            <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#007BFF] outline-none text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Hasta</label>
            <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#007BFF] outline-none text-sm">
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="flex-1 bg-[#007BFF] text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                Filtrar
            </button>
            <a href="<?php echo App\Helpers\UrlHelper::url('api'); ?>/logs/export?<?php echo http_build_query($_GET); ?>"
                class="bg-[#28A745] text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center"
                title="Exportar CSV">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="logsTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Fecha</th>
                <th class="py-4">Usuario</th>
                <th class="py-4">Rol</th>
                <th class="py-4">Módulo</th>
                <th class="py-4">Acción</th>
                <th class="py-4">Nivel</th>
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
                    <td class="py-4">
                        <?php
                        $nivelClass = $log['nivel'] === 'ERROR' ? 'text-red-600 bg-red-100' : ($log['nivel'] === 'WARNING' ? 'text-yellow-600 bg-yellow-100' : 'text-blue-600 bg-blue-100');
                        ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $nivelClass; ?>">
                            <?php echo $log['nivel']; ?>
                        </span>
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

<?php include __DIR__ . '/../layout/footer.php'; ?>
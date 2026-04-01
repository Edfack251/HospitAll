<?php
use App\Helpers\UrlHelper;
use App\Controllers\ImagingController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'tecnico_imagenes', 'recepcionista']);

$controller = new ImagingController($pdo);
$data = $controller->index();
$pendientes = $data['pendientes'] ?? [];
$completadas = ($_SESSION['user_role'] === 'recepcionista') ? [] : ($data['completadas'] ?? []);

$pageTitle = 'Imágenes Médicas - HospitAll';
$activePage = 'imaging';
$headerTitle = 'Gestión de Imágenes';
$headerSubtitle = 'Órdenes y resultados de estudios radiológicos.';

include __DIR__ . '/../layout/header.php';
?>

<div class="mb-4 md:mb-6 border-b border-gray-200 overflow-x-auto">
    <nav class="flex space-x-4 md:space-x-8 min-w-max px-1" aria-label="Tabs">
        <button onclick="switchTab('tab-history-pendientes')" id="btn-history-pendientes"
            class="tab-btn border-b-2 border-blue-600 py-4 px-1 text-sm font-medium text-blue-600">
            Pendientes (<?php echo count($pendientes); ?>)
        </button>
        <?php if ($_SESSION['user_role'] !== 'recepcionista'): ?>
        <button onclick="switchTab('tab-history-completadas')" id="btn-history-completadas"
            class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Histórico Completadas (<?php echo count($completadas); ?>)
        </button>
        <?php endif; ?>
    </nav>
</div>

<!-- Sección Pendientes -->
<div id="tab-history-pendientes" class="tab-content glass-card p-4 sm:p-6 md:p-8 rounded-2xl shadow-sm">
    <table id="imgTablePendientes" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4 px-2">Paciente</th>
                <th class="py-4 px-2">Médico</th>
                <th class="py-4 px-2">Estudio</th>
                <th class="py-4 px-2">Fecha</th>
                <th class="py-4 px-2 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendientes as $o): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4 px-2 font-medium">
                        <?php echo htmlspecialchars($o['paciente_nombre'] . ' ' . $o['paciente_apellido']); ?>
                    </td>
                    <td class="py-4 px-2 text-sm text-gray-600">
                        Dr. <?php echo htmlspecialchars($o['medico_nombre'] . ' ' . $o['medico_apellido']); ?>
                    </td>
                    <td class="py-4 px-2 text-sm font-semibold">
                        <?php echo htmlspecialchars($o['estudios']); ?>
                    </td>
                    <td class="py-4 px-2 text-xs text-gray-400">
                        <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                    </td>
                    <td class="py-4 px-2 text-right space-y-2">
                        <?php if (in_array($_SESSION['user_role'], ['administrador', 'recepcionista'])): ?>
                            <?php $yaFacturada = in_array($o['id'], ($data['ordenesImgFacturadas'] ?? [])); ?>
                            <?php if ($yaFacturada): ?>
                                <span class="inline-block text-xs bg-gray-100 text-gray-500 px-3 py-1.5 rounded-lg w-full font-medium text-center border border-gray-200">
                                    Facturado
                                </span>
                            <?php else: ?>
                                <form action="<?php echo UrlHelper::url('api/imaging/bill'); ?>" method="POST">
                                    <?php $csrf_img = \App\Helpers\CsrfHelper::generateToken(); ?>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_img; ?>">
                                    <input type="hidden" name="orden_id" value="<?php echo $o['id']; ?>">
                                    <input type="hidden" name="paciente_id" value="<?php echo $o['paciente_id']; ?>">
                                    <input type="hidden" name="descripcion" value="<?php echo htmlspecialchars($o['estudios']); ?>">
                                    <button type="submit"
                                        class="text-xs bg-[#28A745] text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition-all w-full font-bold shadow-sm">
                                        Cobrar Estudio
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (in_array($_SESSION['user_role'], ['administrador', 'tecnico_imagenes'])): ?>
                            <a href="<?php echo UrlHelper::url('dashboard_imaging'); ?>#section-pendientes" 
                               class="inline-block text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 font-bold transition-all w-full text-center">Gestionar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Sección Completadas -->
<div id="tab-history-completadas" class="tab-content hidden glass-card p-4 sm:p-6 md:p-8 rounded-2xl shadow-sm">
    <table id="imgTableCompletadas" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4 px-2">Paciente</th>
                <th class="py-4 px-2">Estudio</th>
                <th class="py-4 px-2">Fecha Resultado</th>
                <th class="py-4 px-2 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($completadas as $o): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4 px-2 font-medium">
                        <?php echo htmlspecialchars($o['paciente_nombre'] . ' ' . $o['paciente_apellido']); ?>
                    </td>
                    <td class="py-4 px-2 text-sm font-semibold"><?php echo htmlspecialchars($o['estudios']); ?></td>
                    <td class="py-4 px-2 text-xs text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($o['fecha_resultado'])); ?>
                    </td>
                    <td class="py-4 px-2 text-right">
                        <?php if ($o['archivo_imagen']): ?>
                            <a href="<?php echo UrlHelper::url($o['archivo_imagen']); ?>" target="_blank"
                                class="text-xs bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg hover:bg-emerald-100 font-bold border border-emerald-200 transition-all">Ver Resultado</a>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">Sin archivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-blue-600', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        document.getElementById(tabId).classList.remove('hidden');
        document.getElementById('btn-' + tabId.replace('tab-', '')).classList.add('border-blue-600', 'text-blue-600');
    }

    $(document).ready(function () {
        const config = {
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        };
        $('#imgTablePendientes').DataTable(config);
        $('#imgTableCompletadas').DataTable(config);
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

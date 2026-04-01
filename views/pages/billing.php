<?php
use App\Helpers\UrlHelper;
use App\Controllers\BillingController;
use App\Helpers\AuthHelper;
AuthHelper::checkRole(['administrador', 'recepcionista', 'farmaceutico']);

$controller = new BillingController($pdo);
$facturas = $controller->index();

$isRecepcionista = $_SESSION['user_role'] === 'recepcionista';
$facturasPendientes = array_filter($facturas, fn($f) => ($f['estado'] ?? '') === 'Pendiente');
$facturasCompletadas = $isRecepcionista ? [] : array_filter($facturas, fn($f) => ($f['estado'] ?? '') === 'Pagada');

$pageTitle = 'Facturación - HospitAll';
$activePage = 'facturacion';
$headerTitle = 'Gestión de Facturación';
$headerSubtitle = 'Administra los cobros de consultas, medicinas y laboratorio.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Cabecera de Acciones -->
<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-bold bg-gradient-to-r from-[#007BFF] to-[#28A745] bg-clip-text text-transparent">Listado de Facturas</h3>
    <button onclick="document.getElementById('modalCreate').classList.remove('hidden')"
        class="bg-[#007BFF] text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 transition shadow-sm">
        + Nueva Factura
    </button>
</div>

<!-- Tabs Pendientes / Completadas -->
<div class="mb-4 md:mb-6 border-b border-gray-200 overflow-x-auto">
    <nav class="flex space-x-4 md:space-x-8 min-w-max px-1" aria-label="Tabs">
        <button onclick="switchBillingTab('tab-pendientes')" id="btn-pendientes"
            class="billing-tab-btn border-b-2 border-[#007BFF] py-4 px-1 text-sm font-medium text-[#007BFF]">
            Pendientes (<?php echo count($facturasPendientes); ?>)
        </button>
        <?php if (!$isRecepcionista): ?>
        <button onclick="switchBillingTab('tab-completadas')" id="btn-completadas"
            class="billing-tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Completadas (<?php echo count($facturasCompletadas); ?>)
        </button>
        <?php endif; ?>
    </nav>
</div>

<!-- Sección Facturas Pendientes -->
<div id="tab-pendientes" class="billing-tab-content bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
    <?php if (empty($facturasPendientes)): ?>
        <div class="text-center py-10">
            <p class="text-gray-500">No hay facturas pendientes.</p>
        </div>
    <?php else: ?>
        <table id="billingTablePendientes" class="w-full text-left display">
            <thead>
                <tr class="text-[#6C757D] border-b text-sm">
                    <th class="py-4 px-2">ID</th>
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Fecha</th>
                    <th class="py-4 px-2">Total ($)</th>
                    <th class="py-4 px-2">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($facturasPendientes as $f): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2 font-semibold">#<?php echo str_pad($f['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="py-3 px-2">
                            <div class="font-medium text-[#212529]"><?php echo htmlspecialchars($f['paciente_nombre'] . ' ' . $f['paciente_apellido']); ?></div>
                            <div class="text-xs text-[#6C757D]"><?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($f['paciente_identificacion'] ?? '', $f['paciente_id_real'] ?? null)); ?></div>
                        </td>
                        <td class="py-3 px-2 text-[#6C757D] text-sm"><?php echo date('d/m/Y H:i', strtotime($f['fecha'])); ?></td>
                        <td class="py-3 px-2 font-bold text-[#28A745]">$<?php echo number_format($f['total'], 2); ?></td>
                        <td class="py-3 px-2">
                            <a href="<?php echo App\Helpers\UrlHelper::url('billing_details'); ?>?id=<?php echo $f['id']; ?>"
                                class="text-[#007BFF] hover:underline text-sm font-semibold">Ver detalle / Cobrar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Sección Facturas Completadas -->
<div id="tab-completadas" class="billing-tab-content hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
    <?php if (empty($facturasCompletadas)): ?>
        <div class="text-center py-10">
            <p class="text-gray-500">No hay facturas completadas.</p>
        </div>
    <?php else: ?>
        <table id="billingTableCompletadas" class="w-full text-left display">
            <thead>
                <tr class="text-[#6C757D] border-b text-sm">
                    <th class="py-4 px-2">ID</th>
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Fecha</th>
                    <th class="py-4 px-2">Total ($)</th>
                    <th class="py-4 px-2">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($facturasCompletadas as $f): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2 font-semibold">#<?php echo str_pad($f['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="py-3 px-2">
                            <div class="font-medium text-[#212529]"><?php echo htmlspecialchars($f['paciente_nombre'] . ' ' . $f['paciente_apellido']); ?></div>
                            <div class="text-xs text-[#6C757D]"><?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($f['paciente_identificacion'] ?? '', $f['paciente_id_real'] ?? null)); ?></div>
                        </td>
                        <td class="py-3 px-2 text-[#6C757D] text-sm"><?php echo date('d/m/Y H:i', strtotime($f['fecha'])); ?></td>
                        <td class="py-3 px-2 font-bold text-[#28A745]">$<?php echo number_format($f['total'], 2); ?></td>
                        <td class="py-3 px-2">
                            <a href="<?php echo App\Helpers\UrlHelper::url('billing_details'); ?>?id=<?php echo $f['id']; ?>"
                                class="text-[#007BFF] hover:underline text-sm font-semibold">Ver detalle</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal Nueva Factura -->
<div id="modalCreate" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 w-full max-w-sm shadow-xl relative">
        <button onclick="document.getElementById('modalCreate').classList.add('hidden')"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-xl">&times;</button>
        <h2 class="text-xl font-bold mb-4">Apertura de Factura</h2>

        <form action="<?php echo UrlHelper::url('api/billing/create'); ?>" method="POST" class="space-y-4">
            <!-- CSRF Token a Implementar (Opcional en esta demo, requiere CsrfHelper) -->
            <?php $csrf = \App\Helpers\CsrfHelper::generateToken(); ?>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">ID del Paciente (Registro Interno)</label>
                <input type="number" name="paciente_id" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                <p class="text-xs text-gray-400 mt-1">Ingrese el correlativo del paciente a facturar.</p>
            </div>

            <button type="submit"
                class="w-full bg-[#28A745] text-white font-bold py-2 rounded-lg hover:bg-green-700 transition">Crear
                Borrador</button>
        </form>
    </div>
</div>

<script>
    function switchBillingTab(tabId) {
        document.querySelectorAll('.billing-tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.billing-tab-btn').forEach(btn => {
            btn.classList.remove('border-[#007BFF]', 'text-[#007BFF]');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        document.getElementById(tabId).classList.remove('hidden');
        const btnId = tabId.replace('tab-', 'btn-');
        document.getElementById(btnId).classList.add('border-[#007BFF]', 'text-[#007BFF]');
        document.getElementById(btnId).classList.remove('border-transparent', 'text-gray-500');
    }

    $(document).ready(function () {
        const dtConfig = {
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            responsive: true,
            order: [[2, 'desc']],
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        };
        if ($('#billingTablePendientes').length && $('#billingTablePendientes tbody tr').length > 0) {
            $('#billingTablePendientes').DataTable(dtConfig);
        }
        if ($('#billingTableCompletadas').length && $('#billingTableCompletadas tbody tr').length > 0) {
            $('#billingTableCompletadas').DataTable(dtConfig);
        }
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<?php
use App\Helpers\UrlHelper;
use App\Controllers\BillingController;
use App\Helpers\AuthHelper;
// Políticas de acceso controladas
AuthHelper::checkRole(['administrador', 'recepcionista', 'farmaceutico']);

$controller = new BillingController($pdo);
$facturas = $controller->index();

$pageTitle = 'Facturación - HospitAll';
$activePage = 'facturacion';
$headerTitle = 'Gestión de Facturación';
$headerSubtitle = 'Administra los cobros de consultas, medicinas y laboratorio.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Cabecera de Acciones -->
<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-bold bg-gradient-to-r from-[#007BFF] to-[#28A745] bg-clip-text text-transparent">Listado de
        Facturas</h3>
    <button onclick="document.getElementById('modalCreate').classList.remove('hidden')"
        class="bg-[#007BFF] text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 transition shadow-sm">
        + Nueva Factura
    </button>
</div>

<!-- Tabla Principal -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
    <?php if (empty($facturas)): ?>
        <div class="text-center py-10">
            <p class="text-gray-500">No hay facturas registradas en el sistema.</p>
        </div>
    <?php else: ?>
        <table id="billingTable" class="w-full text-left display">
            <thead>
                <tr class="text-[#6C757D] border-b text-sm">
                    <th class="py-4 px-2">ID</th>
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Fecha</th>
                    <th class="py-4 px-2">Total ($)</th>
                    <th class="py-4 px-2">Estado</th>
                    <th class="py-4 px-2">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($facturas as $f): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2 font-semibold">#
                            <?php echo str_pad($f['id'], 5, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td class="py-3 px-2">
                            <div class="font-medium text-[#212529]">
                                <?php echo htmlspecialchars($f['paciente_nombre'] . ' ' . $f['paciente_apellido']); ?>
                            </div>
                            <div class="text-xs text-[#6C757D]">
                                <?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($f['paciente_identificacion'] ?? '', $f['paciente_id_real'] ?? null)); ?>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-[#6C757D] text-sm">
                            <?php echo date('d/m/Y H:i', strtotime($f['fecha'])); ?>
                        </td>
                        <td class="py-3 px-2 font-bold text-[#28A745]">
                            $
                            <?php echo number_format($f['total'], 2); ?>
                        </td>
                        <td class="py-3 px-2">
                            <?php
                            $badgeClass = '';
                            if ($f['estado'] === 'Pagada')
                                $badgeClass = 'bg-green-100 text-green-700';
                            else if ($f['estado'] === 'Pendiente')
                                $badgeClass = 'bg-amber-100 text-amber-700';
                            else
                                $badgeClass = 'bg-red-100 text-red-700';
                            ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $badgeClass; ?>">
                                <?php echo $f['estado']; ?>
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <!-- Link a una futura vista de detalle -->
                            <a href="<?php echo App\Helpers\UrlHelper::url('billing_details'); ?>?id=<?php echo $f['id']; ?>"
                                class="text-[#007BFF] hover:underline text-sm font-semibold">
                                Ver Detalle
                            </a>
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
    $(document).ready(function () {
        $('#billingTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            responsive: true,
            order: [[2, 'desc']] // Ordenar por fecha reciente
        });
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
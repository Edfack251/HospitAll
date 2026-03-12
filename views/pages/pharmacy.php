<?php
use App\Helpers\UrlHelper;
use App\Controllers\PharmacyController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['farmaceutico', 'administrador']);

$controller = new PharmacyController($pdo);
$medicamentos = $controller->getInventory();
$prescripciones = $controller->getPendingPrescriptions();

$pacientes = $controller->getPatients();

$pageTitle = 'Farmacia - HospitAll';
$activePage = 'farmacia';
$headerTitle = 'Módulo de Farmacia';
$headerSubtitle = 'Dispensario, control de inventario y despacho automático de medicamentos a facturación.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Mensajes de Éxito / Error -->
<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
        <strong class="font-bold">¡Despachado con Éxito!</strong>
        <span class="block sm:inline">El inventario ha sido reducido y se generó automáticamente la <strong>Factura #
                <?php echo str_pad($_GET['factura_id'], 5, '0', STR_PAD_LEFT); ?>
            </strong>.</span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
        <strong class="font-bold">Error en Farmacia:</strong>
        <span class="block sm:inline">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </span>
    </div>
<?php endif; ?>

<!-- Nueva sección: Prescripciones Pendientes -->
<div class="mb-8">
    <div class="glass-card p-8 rounded-2xl border border-blue-50">
        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <span class="gradient-text">Recetas Médicas Pendientes</span>
        </h3>

        <?php if (empty($prescripciones)): ?>
            <p class="text-gray-400 text-center py-8 italic">No hay recetas pendientes por dispensar.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b">
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3">Medicamento / Instrucción</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($prescripciones as $p): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    <?php echo date('d/m/Y H:i', strtotime($p['fecha_prescripcion'])); ?>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($p['paciente_nombre'] . ' ' . $p['paciente_apellido']); ?></span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    Dr. <?php echo htmlspecialchars($p['medico_nombre'] . ' ' . $p['medico_apellido']); ?>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-semibold text-blue-600">
                                        <?php echo htmlspecialchars($p['medicamentos_summary']); ?>
                                    </p>
                                    <p class="text-[10px] text-gray-400">
                                        Prescripción #<?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?>
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <a href="<?php echo App\Helpers\UrlHelper::url('pharmacy_dispense'); ?>?id=<?php echo $p['id']; ?>"
                                        class="bg-[#EAB308] text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-yellow-600 transition shadow-sm">
                                        Dispensar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Grid Principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Lado Izquierdo: Despachar -->
    <div class="lg:col-span-1 glass-card p-8 shadow-sm rounded-2xl h-fit">
        <h3 class="text-lg font-bold gradient-text mb-4">Despachar Medicamento</h3>

        <form action="<?php echo UrlHelper::url('api/pharmacy/dispense'); ?>" method="POST" class="space-y-4">
            <?php $csrf = \App\Helpers\CsrfHelper::generateToken(); ?>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Paciente</label>
                <select name="paciente_id" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                    <option value="">-- Seleccionar Paciente --</option>
                    <?php foreach ($pacientes as $pac): ?>
                        <option value="<?php echo $pac['id']; ?>">
                            <?php echo htmlspecialchars($pac['nombre'] . ' ' . $pac['apellido']); ?>
                            (
                            <?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($pac['identificacion'] ?? '', $pac['id'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Medicamento a Dispensar</label>
                <select name="medicamento_id" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                    <option value="">-- Seleccionar Inventario --</option>
                    <?php foreach ($medicamentos as $med): ?>
                        <option value="<?php echo $med['id']; ?>" <?php if ($med['stock'] <= 0)
                               echo 'disabled'; ?>>
                            <?php echo htmlspecialchars($med['nombre']); ?> (Stock:
                            <?php echo $med['stock']; ?> uds)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Cantidad de Cajas / Unidades</label>
                <input type="number" name="cantidad" value="1" min="1" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                <p class="text-xs text-gray-400 mt-1">El costo total se calculará de forma automática en la factura del
                    paciente.</p>
            </div>

            <button type="submit"
                class="w-full btn-success-gradient text-white font-bold py-3 mt-4 rounded-lg transition">
                Procesar Entrega e Imprimir Factura
            </button>
        </form>
    </div>

    <!-- Lado Derecho: Inventario Actual -->
    <div class="lg:col-span-2 glass-card p-8 shadow-sm rounded-2xl">
        <h3 class="text-xl font-bold gradient-text mb-6">
            Inventario Activo</h3>

        <?php if (empty($medicamentos)): ?>
            <p class="text-gray-500 text-center py-6">No hay medicamentos registrados en el sistema de farmacia.</p>
        <?php else: ?>
            <table id="pharmacyTable" class="w-full text-left display">
                <thead>
                    <tr class="text-[#6C757D] border-b text-sm">
                        <th class="py-4 px-2">ID</th>
                        <th class="py-4 px-2">Nombre / Descripción</th>
                        <th class="py-4 px-2">Costo (Doc)</th>
                        <th class="py-4 px-2">Inventario Lógico (Stock)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($medicamentos as $med): ?>
                        <tr class="hover:bg-gray-50 transition-colors <?php if ($med['stock'] <= 5)
                            echo 'bg-red-50'; ?>">
                            <td class="py-3 px-2 font-semibold">#
                                <?php echo str_pad($med['id'], 4, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td class="py-3 px-2">
                                <span class="font-bold text-[#212529]">
                                    <?php echo htmlspecialchars($med['nombre']); ?>
                                </span><br>
                                <span class="text-xs text-[#6C757D]">
                                    <?php echo htmlspecialchars($med['descripcion'] ?? 'Sin detalles extra'); ?>
                                </span>
                            </td>
                            <td class="py-3 px-2 font-medium text-[#28A745]">$
                                <?php echo number_format($med['precio'], 2); ?>
                            </td>
                            <td class="py-3 px-2">
                                <?php if ($med['stock'] > 10): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                        <?php echo $med['stock']; ?> Cajas Disp.
                                    </span>
                                <?php elseif ($med['stock'] > 0): ?>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full">
                                        <?php echo $med['stock']; ?> Cajas (Bajo)
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Agotado
                                        (0)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function () {
        initDataTable('#pharmacyTable', {
            order: [[3, 'asc']]
        });
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<?php
use App\Helpers\UrlHelper;
use App\Controllers\PharmacyController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\PrivacyHelper;

AuthHelper::checkRole(['farmaceutico', 'administrador']);

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) {
    UrlHelper::redirect('pharmacy');
}

$controller = new PharmacyController($pdo);
$prescripcion = $controller->getPrescription($id);

if (!$prescripcion) {
    UrlHelper::redirect('pharmacy', ['error' => 'not_found']);
}

$medicamentos_inv = $controller->getInventory();

$pageTitle = 'Dispensar Receta - HospitAll';
$activePage = 'farmacia';
$headerTitle = 'Dispensación de Receta #' . str_pad($id, 5, '0', STR_PAD_LEFT);
$headerSubtitle = 'Confirme los medicamentos y cantidades a entregar al paciente.';

include __DIR__ . '/../layout/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Información del Paciente -->
    <div class="glass-card p-8 rounded-2xl shadow-sm mb-8">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <h3 class="text-lg font-bold gradient-text">Datos de la Receta</h3>
            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full border border-yellow-200 uppercase tracking-wider">ESTADO: <?php echo $prescripcion['estado']; ?></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <p><span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Paciente</span> <span class="text-gray-800 font-semibold"><?php echo htmlspecialchars($prescripcion['paciente_nombre'] . ' ' . $prescripcion['paciente_apellido']); ?></span></p>
            <p><span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Identificación</span> <span class="text-gray-800 font-semibold"><?php echo htmlspecialchars(PrivacyHelper::maskCedula($prescripcion['identificacion'] ?? '')); ?></span></p>
            <p><span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Fecha Emisión</span> <span class="text-gray-800 font-semibold"><?php echo date('d/m/Y H:i', strtotime($prescripcion['fecha_prescripcion'])); ?></span></p>
        </div>
    </div>

    <!-- Formulario de Dispensación -->
    <form action="<?php echo UrlHelper::url('api/pharmacy/processDispense'); ?>" method="POST" class="space-y-6">
        <?php $csrf = CsrfHelper::generateToken(); ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="prescripcion_id" value="<?php echo $id; ?>">

    <div class="glass-card p-10 rounded-2xl shadow-sm">
        <h3 class="text-xl font-bold gradient-text mb-8">Detalle de Medicamentos</h3>
            
            <div class="space-y-6">
                <?php foreach ($prescripcion['detalles'] as $index => $det): ?>
                    <div class="p-6 bg-gray-50 rounded-xl border border-gray-100 relative">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Datos del médico -->
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase mb-2">Prescrito por el médico:</p>
                                <p class="text-md font-bold text-gray-800"><?php echo htmlspecialchars($det['medicamento_texto'] ?: $det['medicamento_nombre']); ?></p>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($det['dosis']); ?> - <?php echo htmlspecialchars($det['frecuencia']); ?></p>
                                <p class="text-xs text-gray-400 mt-1 italic"><?php echo htmlspecialchars($det['indicaciones']); ?></p>
                                <p class="text-xs font-bold text-blue-600 mt-2">Cantidad Requerida: <?php echo $det['cantidad_requerida'] ?: 'No especificada'; ?></p>
                            </div>

                            <!-- Selección del farmacéutico -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Medicamento en Inventario</label>
                                    <select name="items[<?php echo $index; ?>][medicamento_id]" required
                                        class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-[#007BFF] outline-none">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($medicamentos_inv as $med): ?>
                                            <option value="<?php echo $med['id']; ?>" 
                                                <?php echo ($med['id'] == $det['medicamento_id']) ? 'selected' : ''; ?>
                                                <?php echo ($med['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($med['nombre']); ?> (Stock: <?php echo $med['stock']; ?>) - $<?php echo number_format($med['precio'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cantidad a Entregar</label>
                                    <input type="number" name="items[<?php echo $index; ?>][cantidad]" 
                                           value="<?php echo $det['cantidad_requerida'] ?: 1; ?>" min="0" required
                                           class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-[#007BFF] outline-none">
                                    <p class="text-[10px] text-gray-400 mt-1">Si entrega 0, este ítem no se facturará.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t font-semibold">
            <a href="<?php echo App\Helpers\UrlHelper::url('pharmacy'); ?>" class="px-6 py-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition">Cancelar</a>
            <button type="submit" 
                    class="btn-success-gradient text-white px-8 py-2 rounded-lg shadow-md transition">
                Procesar Dispensación y Facturar
            </button>
        </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

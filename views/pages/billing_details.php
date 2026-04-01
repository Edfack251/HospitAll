<?php
use App\Helpers\UrlHelper;
use App\Controllers\BillingController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista', 'farmaceutico']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    UrlHelper::redirect('billing');
}

$factura_id = (int) $_GET['id'];
$controller = new BillingController($pdo);
$detalle = $controller->getDetails($factura_id);

if (!$detalle['factura']) {
    UrlHelper::redirect('billing', ['error' => '1', 'msg' => 'Factura no encontrada']);
}

$f = $detalle['factura'];
$items = $detalle['items'];

$pageTitle = 'Detalle de Factura - HospitAll';
$activePage = 'facturacion';
$headerTitle = 'Factura #' . str_pad($f['id'], 5, '0', STR_PAD_LEFT);
$headerSubtitle = 'Revisión y liquidación de renglones.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Mensajes -->
<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
        <strong class="font-bold">¡Hecho!</strong>
        <span class="block sm:inline"> La operación sobre la factura fue exitosa.</span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
        <strong class="font-bold">Error:</strong>
        <span class="block sm:inline">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Información de Cabecera -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-[#007BFF] mb-4">Información del Paciente</h3>
            <p class="font-medium text-[#212529]">
                <?php echo htmlspecialchars($f['paciente_nombre'] . ' ' . $f['paciente_apellido']); ?>
            </p>
            <p class="text-sm text-[#6C757D] mt-1">Cédula:
                <?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($f['identificacion'] ?? '', $f['paciente_id'] ?? null)); ?>
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-[#28A745] mb-4">Estado General</h3>
            <p class="text-sm text-[#6C757D] mb-1">Total a Pagar:</p>
            <p class="text-3xl font-black text-[#212529] mb-4">$
                <?php echo number_format($f['total'], 2); ?>
            </p>

            <?php
            $badgeClass = '';
            if ($f['estado'] === 'Pagada')
                $badgeClass = 'bg-green-100 text-green-700';
            else if ($f['estado'] === 'Pendiente')
                $badgeClass = 'bg-amber-100 text-amber-700';
            else
                $badgeClass = 'bg-red-100 text-red-700';
            ?>
            <div class="inline-block px-4 py-1.5 rounded-full font-bold text-sm <?php echo $badgeClass; ?>">
                Estatus:
                <?php echo mb_strtoupper($f['estado']); ?>
            </div>

            <?php if ($f['estado'] === 'Pagada'): ?>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Método Confirmado</p>
                    <p class="text-sm text-[#212529] font-medium mt-1">
                        <?php echo htmlspecialchars($f['metodo_pago']); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <?php 
        $hasMedicamento = false;
        foreach ($items as $item) {
            if (($item['tipo_item'] ?? '') === 'medicamento') {
                $hasMedicamento = true;
                break;
            }
        }
        $isRecepcionista = $_SESSION['user_role'] === 'recepcionista';
        $pagoBloqueado = $isRecepcionista && $hasMedicamento;
        ?>

        <?php if ($f['estado'] === 'Pendiente'): ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Liquidar Factura</h3>
                
                <?php if ($pagoBloqueado): ?>
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm mb-4">
                        <div class="flex items-center gap-2 font-bold mb-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Acceso Restringido
                        </div>
                        Esta factura contiene medicamentos. Por políticas de seguridad, el cobro de farmacia debe ser procesado por el personal farmacéutico.
                    </div>
                <?php else: ?>
                    <form action="<?php echo UrlHelper::url('api/billing/pay'); ?>" method="POST">
                        <?php $csrf = CsrfHelper::generateToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="factura_id" value="<?php echo $f['id']; ?>">

                        <label class="block text-sm font-semibold text-gray-700 mb-2">Método de Pago</label>
                        <select name="metodo_pago" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF] mb-4">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                            <option value="Seguro Médico">Seguro Médico Copago</option>
                            <option value="Transferencia">Transferencia Bancaria</option>
                        </select>

                        <button type="submit"
                            class="w-full bg-[#28A745] text-white font-bold py-3 rounded-lg hover:bg-green-700 transition shadow-sm">
                            Confirmar Pago ($<?php echo number_format($f['total'], 2); ?>)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pestaña de Renglones / Items -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-full">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h3 class="text-xl font-bold text-[#212529]">Desglose de Renglones</h3>
            </div>

            <?php if (empty($items)): ?>
                <div class="text-center py-12 text-[#6C757D]">
                    <p>Esta factura está en blanco y no posee elementos a cobrar.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[#6C757D] text-sm border-b">
                            <th class="py-3 px-2">Tipo</th>
                            <th class="py-3 px-2">Descripción</th>
                            <th class="py-3 px-2">Cantidad</th>
                            <th class="py-3 px-2">Precio U.</th>
                            <th class="py-3 px-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3 px-2">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-semibold uppercase">
                                        <?php echo htmlspecialchars($item['tipo_item'] ?? ''); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-2 font-medium text-[#212529]">
                                    <?php echo htmlspecialchars($item['descripcion'] ?? ''); ?>
                                </td>
                                <td class="py-3 px-2 text-[#6C757D] text-sm text-center">
                                    x
                                    <?php echo $item['cantidad'] ?? 0; ?>
                                </td>
                                <td class="py-3 px-2 text-[#6C757D] text-sm">
                                    $
                                    <?php echo number_format($item['precio'] ?? 0, 2); ?>
                                </td>
                                <td class="py-3 px-2 font-bold text-[#212529] text-right">
                                    $
                                    <?php echo number_format($item['subtotal'] ?? 0, 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t-2">
                        <tr>
                            <td colspan="4" class="py-4 px-2 text-right font-bold text-gray-600">Sumatoria Total:</td>
                            <td class="py-4 px-2 text-right text-xl font-black text-[#28A745]">$
                                <?php echo number_format($f['total'], 2); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
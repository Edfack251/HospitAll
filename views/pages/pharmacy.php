<?php
use App\Helpers\UrlHelper;
use App\Controllers\PharmacyController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['farmaceutico', 'administrador']);

$controller = new PharmacyController($pdo);
$medicamentos = $controller->getInventory();
$limit = 5;
$p_page = isset($_GET['p_page']) ? (int)$_GET['p_page'] : 1;
$offset = ($p_page - 1) * $limit;
$prescripciones = $controller->getPendingPrescriptions($limit, $offset);
$totalPrescripciones = $controller->getPendingPrescriptionsCount();
$totalPages = ceil($totalPrescripciones / $limit);

$pacientes = $controller->getPatients();
$turnoActual = $controller->getTurnoActual();
$turnosEsperandoCount = $controller->getTurnosEsperandoCount();

$pageTitle = 'Farmacia - HospitAll';
$activePage = 'farmacia';
$headerTitle = 'Módulo de Farmacia';
$headerSubtitle = 'Dispensario, control de inventario y despacho automático de medicamentos a facturación.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Mensajes de Éxito / Error -->
<?php if (isset($_GET['success'])): ?>
    <?php $factura_id = $_GET['factura_id'] ?? null; ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
        <strong class="font-bold">¡Despachado con Éxito!</strong>
        <span class="block sm:inline">El inventario ha sido reducido <?php echo $factura_id ? "y se generó automáticamente la <strong>Factura #" . str_pad($factura_id, 5, '0', STR_PAD_LEFT) . "</strong>" : "correctamente"; ?>.</span>
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
            <span class="gradient-text">Recetas médicas pendientes</span>
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
                                    <a href="<?php echo App\Helpers\UrlHelper::url('pharmacy_prescriptions'); ?>?id=<?php echo $p['id']; ?>"
                                        class="bg-[#EAB308] text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-yellow-600 transition shadow-sm">
                                        Dispensar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 font-medium">Página <?php echo $p_page; ?> de <?php echo $totalPages; ?> (<?php echo $totalPrescripciones; ?> total)</p>
                    <nav class="flex gap-2">
                        <?php if ($p_page > 1): ?>
                            <a href="?p_page=<?php echo $p_page - 1; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all">Anterior</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?p_page=<?php echo $i; ?>" 
                               class="px-3 py-1.5 text-xs font-bold rounded-lg border <?php echo ($i === $p_page) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-400'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($p_page < $totalPages): ?>
                            <a href="?p_page=<?php echo $p_page + 1; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-all">Siguiente</a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Grid Principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Lado Izquierdo: Despachar -->
    <div class="lg:col-span-1 glass-card p-8 shadow-sm rounded-2xl h-fit">
        <h3 class="text-lg font-bold gradient-text mb-4">Despachar medicamento</h3>

        <form action="<?php echo UrlHelper::url('api/pharmacy/dispense'); ?>" method="POST" class="space-y-4">
            <?php $csrf = \App\Helpers\CsrfHelper::generateToken(); ?>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Paciente</label>
                <select name="paciente_id" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                    <option value="">-- Seleccionar paciente --</option>
                    <?php foreach ($pacientes as $pac): ?>
                        <option value="<?php echo $pac['id']; ?>">
                            <?php echo htmlspecialchars($pac['nombre'] . ' ' . $pac['apellido']); ?>
                            (<?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($pac['identificacion'] ?? '', $pac['id'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Medicamento a dispensar</label>
                <select name="medicamento_id" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                    <option value="">-- Seleccionar inventario --</option>
                    <?php foreach ($medicamentos as $med): ?>
                        <option value="<?php echo $med['id']; ?>" <?php if ($med['stock'] <= 0) echo 'disabled'; ?>>
                            <?php echo htmlspecialchars($med['nombre']); ?> (Stock:
                            <?php echo $med['stock']; ?> uds)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Cantidad de cajas / unidades</label>
                <input type="number" name="cantidad" value="1" min="1" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007BFF]">
                <p class="text-xs text-gray-400 mt-1">El costo total se calculará de forma automática en la factura del paciente.</p>
            </div>

            <button type="submit"
                class="w-full btn-success-gradient text-white font-bold py-3 mt-4 rounded-lg transition">
                Procesar entrega e imprimir factura
            </button>
        </form>
    </div>

    <!-- Lado Derecho: Inventario Actual -->
    <div class="lg:col-span-2 glass-card p-8 shadow-sm rounded-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold gradient-text">Inventario activo</h3>
            <button type="button" onclick="openRegistrarModal()"
                class="bg-[#007BFF] text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Registrar medicamento
            </button>
        </div>

        <?php if (empty($medicamentos)): ?>
            <p class="text-gray-500 text-center py-6">No hay medicamentos registrados en el sistema de farmacia.</p>
        <?php else: ?>
            <table id="pharmacyTable" class="w-full text-left display">
                <thead>
                    <tr class="text-[#6C757D] border-b text-sm">
                        <th class="py-4 px-2">ID</th>
                        <th class="py-4 px-2">Nombre / Descripción</th>
                        <th class="py-4 px-2">Lote</th>
                        <th class="py-4 px-2">Proveedor</th>
                        <th class="py-4 px-2">Costo (Doc)</th>
                        <th class="py-4 px-2">Inventario lógico (stock)</th>
                        <th class="py-4 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($medicamentos as $med): ?>
                        <tr class="hover:bg-gray-50 transition-colors <?php if ($med['stock'] <= 5) echo 'bg-red-50'; ?>"
                            data-med-id="<?php echo $med['id']; ?>"
                            data-med-nombre="<?php echo htmlspecialchars($med['nombre']); ?>"
                            data-med-presentacion="<?php echo htmlspecialchars($med['presentacion'] ?? ''); ?>"
                            data-med-concentracion="<?php echo htmlspecialchars($med['concentracion'] ?? ''); ?>"
                            data-med-lote="<?php echo htmlspecialchars($med['lote'] ?? ''); ?>"
                            data-med-proveedor="<?php echo htmlspecialchars($med['proveedor'] ?? ''); ?>"
                            data-med-precio="<?php echo $med['precio']; ?>"
                            data-med-fecha-vencimiento="<?php echo $med['fecha_vencimiento'] ?? ''; ?>"
                            data-med-descripcion="<?php echo htmlspecialchars($med['descripcion'] ?? ''); ?>">
                            <td class="py-3 px-2 font-semibold">#<?php echo str_pad($med['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="py-3 px-2">
                                <span class="font-bold text-[#212529]"><?php echo htmlspecialchars($med['nombre']); ?></span><br>
                                <span class="text-xs text-[#6C757D]">
                                    <?php echo htmlspecialchars($med['presentacion'] ?? ''); ?>
                                    <?php if (!empty($med['concentracion'])): ?> — <?php echo htmlspecialchars($med['concentracion']); ?><?php endif; ?>
                                </span>
                            </td>
                            <td class="py-3 px-2 text-sm text-gray-600"><?php echo htmlspecialchars($med['lote'] ?? '—'); ?></td>
                            <td class="py-3 px-2 text-sm text-gray-600"><?php echo htmlspecialchars($med['proveedor'] ?? '—'); ?></td>
                            <td class="py-3 px-2 font-medium text-[#28A745]">$<?php echo number_format($med['precio'], 2); ?></td>
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
                                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Agotado (0)</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="openEditarModal(this.closest('tr'))"
                                        class="text-xs bg-blue-50 text-[#007BFF] px-3 py-1.5 rounded-lg font-bold hover:bg-blue-100 border border-blue-200 transition">Editar</button>
                                    <button type="button" onclick="openAjustarStockModal(<?php echo $med['id']; ?>, '<?php echo addslashes(htmlspecialchars($med['nombre'])); ?>')"
                                        class="text-xs bg-amber-50 text-amber-600 px-3 py-1.5 rounded-lg font-bold hover:bg-amber-100 border border-amber-200 transition">+ Stock</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- Modal: Registrar medicamento                                  -->
<!-- ============================================================ -->
<div id="registrarModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-lg mx-auto border-white/40 max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold gradient-text mb-2">Registrar medicamento</h3>
        <p class="text-xs text-gray-500 font-medium mb-6 uppercase tracking-wide">Complete los datos del nuevo producto</p>

        <form id="formRegistrar" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nombre *</label>
                    <input type="text" name="nombre" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                        placeholder="Ej: Amoxicilina">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Presentación *</label>
                    <select name="presentacion" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                        <option value="">-- Seleccionar --</option>
                        <option value="Tableta">Tableta</option>
                        <option value="Cápsula">Cápsula</option>
                        <option value="Jarabe">Jarabe</option>
                        <option value="Inyectable">Inyectable</option>
                        <option value="Crema">Crema</option>
                        <option value="Suspensión">Suspensión</option>
                        <option value="Gotas">Gotas</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Concentración</label>
                    <input type="text" name="concentracion"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                        placeholder="Ej: 500mg">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Lote</label>
                    <input type="text" name="lote"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                        placeholder="Ej: L2026-001">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Proveedor</label>
                <input type="text" name="proveedor"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                    placeholder="Ej: Farma Nacional S.A.">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Precio unitario *</label>
                    <input type="number" name="precio" min="0" step="0.01" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                        placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Stock inicial *</label>
                    <input type="number" name="stock" min="0" value="0" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Fecha de vencimiento</label>
                    <input type="date" name="fecha_vencimiento" min="<?php echo date('Y-m-d'); ?>"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Descripción</label>
                <textarea name="descripcion" rows="2"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                    placeholder="Notas adicionales (opcional)"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeRegistrarModal()"
                    class="px-6 py-2 rounded-lg border font-semibold text-gray-500 hover:bg-gray-50">Cancelar</button>
                <button type="submit" id="btnRegistrar"
                    class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- Modal: Editar medicamento                                     -->
<!-- ============================================================ -->
<div id="editarModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-lg mx-auto border-white/40 max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold gradient-text mb-2">Editar medicamento</h3>
        <p class="text-xs text-gray-500 font-medium mb-6 uppercase tracking-wide" id="editarSubtitle"></p>

        <form id="formEditar" class="space-y-4">
            <input type="hidden" name="id" id="editId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nombre *</label>
                    <input type="text" name="nombre" id="editNombre" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Presentación *</label>
                    <select name="presentacion" id="editPresentacion" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                        <option value="">-- Seleccionar --</option>
                        <option value="Tableta">Tableta</option>
                        <option value="Cápsula">Cápsula</option>
                        <option value="Jarabe">Jarabe</option>
                        <option value="Inyectable">Inyectable</option>
                        <option value="Crema">Crema</option>
                        <option value="Suspensión">Suspensión</option>
                        <option value="Gotas">Gotas</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Concentración</label>
                    <input type="text" name="concentracion" id="editConcentracion"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Lote</label>
                    <input type="text" name="lote" id="editLote"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Proveedor</label>
                <input type="text" name="proveedor" id="editProveedor"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Precio unitario *</label>
                    <input type="number" name="precio" id="editPrecio" min="0" step="0.01" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Fecha de vencimiento</label>
                    <input type="date" name="fecha_vencimiento" id="editFechaVencimiento"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Descripción</label>
                <textarea name="descripcion" id="editDescripcion" rows="2"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeEditarModal()"
                    class="px-6 py-2 rounded-lg border font-semibold text-gray-500 hover:bg-gray-50">Cancelar</button>
                <button type="submit" id="btnEditar"
                    class="bg-[#007BFF] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- Modal: Ajustar stock                                          -->
<!-- ============================================================ -->
<div id="ajustarStockModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-sm mx-auto border-white/40">
        <h3 class="text-2xl font-bold gradient-text mb-2">Ajustar stock</h3>
        <p class="text-xs text-gray-500 font-medium mb-6 uppercase tracking-wide" id="ajustarSubtitle"></p>

        <form id="formAjustarStock" class="space-y-4">
            <input type="hidden" name="medicamento_id" id="ajustarMedId">

            <div>
                <label class="block text-sm font-semibold mb-1">Cantidad a añadir *</label>
                <input type="number" name="cantidad" id="ajustarCantidad" min="1" value="1" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Motivo *</label>
                <input type="text" name="motivo" id="ajustarMotivo" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                    placeholder="Ej: Reposición de proveedor">
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeAjustarStockModal()"
                    class="px-6 py-2 rounded-lg border font-semibold text-gray-500 hover:bg-gray-50">Cancelar</button>
                <button type="submit" id="btnAjustar"
                    class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ---- CSRF token (shared) ----
    const csrfToken = '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>';
    const baseUrl = '<?php echo UrlHelper::url(""); ?>';

    // ---- Registrar medicamento ----
    function openRegistrarModal() {
        document.getElementById('formRegistrar').reset();
        document.getElementById('registrarModal').classList.remove('hidden');
    }
    function closeRegistrarModal() {
        document.getElementById('registrarModal').classList.add('hidden');
    }

    document.getElementById('formRegistrar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnRegistrar');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        const formData = new FormData(this);
        formData.set('csrf_token', csrfToken);

        try {
            const res = await fetch(baseUrl + 'api/pharmacy/registrar-medicamento', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                closeRegistrarModal();
                location.reload();
            } else {
                showToast(data.error || 'Error al registrar el medicamento.', 'error');
            }
        } catch (err) {
            showToast('Error de conexión: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Guardar';
        }
    });

    // ---- Editar medicamento ----
    function openEditarModal(row) {
        document.getElementById('editId').value = row.dataset.medId;
        document.getElementById('editNombre').value = row.dataset.medNombre;
        document.getElementById('editPresentacion').value = row.dataset.medPresentacion;
        document.getElementById('editConcentracion').value = row.dataset.medConcentracion;
        document.getElementById('editLote').value = row.dataset.medLote;
        document.getElementById('editProveedor').value = row.dataset.medProveedor;
        document.getElementById('editPrecio').value = row.dataset.medPrecio;
        document.getElementById('editFechaVencimiento').value = row.dataset.medFechaVencimiento;
        document.getElementById('editDescripcion').value = row.dataset.medDescripcion;
        document.getElementById('editarSubtitle').textContent = 'Editando: ' + row.dataset.medNombre;
        document.getElementById('editarModal').classList.remove('hidden');
    }
    function closeEditarModal() {
        document.getElementById('editarModal').classList.add('hidden');
    }

    document.getElementById('formEditar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnEditar');
        btn.disabled = true;
        btn.textContent = 'Actualizando...';

        const payload = {
            id: parseInt(document.getElementById('editId').value),
            nombre: document.getElementById('editNombre').value,
            presentacion: document.getElementById('editPresentacion').value,
            concentracion: document.getElementById('editConcentracion').value,
            lote: document.getElementById('editLote').value,
            proveedor: document.getElementById('editProveedor').value,
            precio: parseFloat(document.getElementById('editPrecio').value),
            fecha_vencimiento: document.getElementById('editFechaVencimiento').value || null,
            descripcion: document.getElementById('editDescripcion').value,
            csrf_token: csrfToken
        };

        try {
            const res = await fetch(baseUrl + 'api/pharmacy/editar-medicamento', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeEditarModal();
                location.reload();
            } else {
                showToast(data.error || 'Error al actualizar el medicamento.', 'error');
            }
        } catch (err) {
            showToast('Error de conexión: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Actualizar';
        }
    });

    // ---- Ajustar stock ----
    function openAjustarStockModal(id, nombre) {
        document.getElementById('ajustarMedId').value = id;
        document.getElementById('ajustarSubtitle').textContent = nombre;
        document.getElementById('ajustarCantidad').value = 1;
        document.getElementById('ajustarMotivo').value = '';
        document.getElementById('ajustarStockModal').classList.remove('hidden');
    }
    function closeAjustarStockModal() {
        document.getElementById('ajustarStockModal').classList.add('hidden');
    }

    document.getElementById('formAjustarStock').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnAjustar');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        const payload = {
            medicamento_id: parseInt(document.getElementById('ajustarMedId').value),
            cantidad: parseInt(document.getElementById('ajustarCantidad').value),
            motivo: document.getElementById('ajustarMotivo').value,
            csrf_token: csrfToken
        };

        try {
            const res = await fetch(baseUrl + 'api/pharmacy/ajustar-stock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeAjustarStockModal();
                location.reload();
            } else {
                showToast(data.error || 'Error al ajustar el stock.', 'error');
            }
        } catch (err) {
            showToast('Error de conexión: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirmar';
        }
    });

    // ---- DataTables ----
    $(document).ready(function () {
        initDataTable('#pharmacyTable', {
            order: [[5, 'asc']]
        });

        // Auto-open modal if requested from sidebar
        if (sessionStorage.getItem('openAddMedicine') === 'true') {
            sessionStorage.removeItem('openAddMedicine');
            openRegistrarModal();
        }
    });

    function llamarSiguiente(area) {
        if (!confirm('¿Desea llamar al siguiente paciente para ' + area + '?')) return;

        fetch('<?php echo UrlHelper::url('api/turnos/llamar'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>'
            },
            body: JSON.stringify({ area: area })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Llamando al turno: ' + data.turno.numero, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || 'No hay turnos pendientes.', 'info');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error al llamar al siguiente turno.', 'error');
        });
    }
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
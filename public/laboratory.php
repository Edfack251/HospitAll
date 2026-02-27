<?php
session_start();
require_once '../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\LaboratoryController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador', 'tecnico_laboratorio']);

$controller = new LaboratoryController($pdo);
$ordenes = $controller->index();

$pageTitle = 'Laboratorio - HospitAll';
$activePage = 'laboratorio';
$headerTitle = 'Gestión de Laboratorio';
$headerSubtitle = 'Órdenes y resultados de análisis clínicos.';

include '../views/layout/header.php';
?>

<?php
$pendientes = array_filter($ordenes, function ($o) {
    return $o['estado'] === 'Pendiente';
});
$completadas = array_filter($ordenes, function ($o) {
    return $o['estado'] === 'Completada';
});
?>

<div class="mb-6 border-b border-gray-200">
    <nav class="flex space-x-8" aria-label="Tabs">
        <button onclick="switchTab('tab-pendientes')" id="btn-pendientes"
            class="tab-btn border-b-2 border-[#007BFF] py-4 px-1 text-sm font-medium text-[#007BFF]">
            Pendientes (<?php echo count($pendientes); ?>)
        </button>
        <button onclick="switchTab('tab-completadas')" id="btn-completadas"
            class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Completadas (<?php echo count($completadas); ?>)
        </button>
    </nav>
</div>

<!-- Sección Pendientes -->
<div id="tab-pendientes" class="tab-content bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <?php if (empty($pendientes)): ?>
        <div class="text-center py-12">
            <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                    </path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">No hay órdenes pendientes</h3>
            <p class="text-gray-500 mt-2">Buen trabajo, has procesado todas las solicitudes.</p>
        </div>
    <?php else: ?>
        <table id="labTablePendientes" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] border-b">
                    <th class="py-4">Paciente</th>
                    <th class="py-4">Médico</th>
                    <th class="py-4">Descripción</th>
                    <th class="py-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendientes as $o): ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="py-4 font-medium">
                            <?php echo htmlspecialchars($o['paciente_nombre'] . ' ' . $o['paciente_apellido']); ?>
                        </td>
                        <td class="py-4 text-sm">
                            <?php echo htmlspecialchars($o['medico_nombre'] . ' ' . $o['medico_apellido']); ?>
                        </td>
                        <td class="py-4">
                            <div class="text-sm font-semibold"><?php echo htmlspecialchars($o['descripcion']); ?></div>
                            <div class="text-xs text-gray-400 italic">Diagnóstico:
                                <?php echo htmlspecialchars(substr($o['diagnostico'], 0, 40)) . '...'; ?>
                            </div>
                        </td>
                        <td class="py-4">
                            <button
                                onclick="openResultModal(<?php echo $o['id']; ?>, '<?php echo htmlspecialchars($o['paciente_nombre'] . ' ' . $o['paciente_apellido']); ?>')"
                                class="text-xs bg-[#007BFF] text-white px-3 py-2 rounded hover:bg-blue-700 transition-colors">
                                Cargar Resultados
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Sección Completadas -->
<div id="tab-completadas" class="tab-content hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <?php if (empty($completadas)): ?>
        <p class="text-center text-gray-400 py-12">No hay órdenes completadas aún.</p>
    <?php else: ?>
        <table id="labTableCompletadas" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] border-b">
                    <th class="py-4">Paciente</th>
                    <th class="py-4">Descripción</th>
                    <th class="py-4">Fecha</th>
                    <th class="py-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completadas as $o): ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="py-4 font-medium">
                            <?php echo htmlspecialchars($o['paciente_nombre'] . ' ' . $o['paciente_apellido']); ?>
                        </td>
                        <td class="py-4 text-sm font-semibold"><?php echo htmlspecialchars($o['descripcion']); ?></td>
                        <td class="py-4 text-xs text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($o['fecha_resultado'])); ?>
                        </td>
                        <td class="py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewResult('<?php echo htmlspecialchars($o['resultado']); ?>')"
                                    class="text-xs bg-gray-100 text-gray-600 px-3 py-2 rounded hover:bg-gray-200 uppercase font-bold">Texto</button>
                                <?php if ($o['archivo_pdf']): ?>
                                    <a href="<?php echo htmlspecialchars($o['archivo_pdf']); ?>" target="_blank"
                                        class="text-xs bg-blue-50 text-[#007BFF] px-3 py-2 rounded hover:bg-blue-100 font-bold border border-blue-200">PDF</a>
                                <?php endif; ?>
                                <button
                                    onclick="openResultModal(<?php echo $o['id']; ?>, '<?php echo htmlspecialchars($o['paciente_nombre'] . ' ' . $o['paciente_apellido']); ?>', '<?php echo htmlspecialchars($o['resultado']); ?>')"
                                    class="text-xs bg-amber-50 text-amber-600 px-3 py-2 rounded hover:bg-amber-100 font-bold border border-amber-200">Corregir</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal para cargar resultados -->
<div id="resultModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg mx-4">
        <h3 class="text-xl font-bold mb-4">Cargar Resultados</h3>
        <p class="text-sm text-gray-600 mb-6" id="modalPacienteName"></p>

        <form action="api/laboratory_upload_result.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="orden_id" id="modalOrdenId">
            <div>
                <label class="block text-sm font-semibold mb-2">Detalle del Resultado (Texto)</label>
                <textarea name="resultado" id="modalResultadoText" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none" rows="4"
                    placeholder="Ingrese un resumen de los valores..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Anexar PDF de Resultados</label>
                <input type="file" name="archivo_pdf" id="modalFile" accept="application/pdf"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-[10px] text-gray-400 mt-1">Solo se permiten archivos PDF. Dejar vacío si solo desea
                    corregir el texto.</p>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeResultModal()"
                    class="px-6 py-2 rounded-lg border font-semibold text-gray-500 hover:bg-gray-50">Cancelar</button>
                <button type="submit"
                    class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700">Guardar
                    Resultados</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResultModal(id, paciente, existingText = '') {
        document.getElementById('modalOrdenId').value = id;
        document.getElementById('modalPacienteName').innerText = "Paciente: " + paciente;
        document.getElementById('modalResultadoText').value = existingText;

        const fileInput = document.getElementById('modalFile');
        if (existingText !== '') {
            fileInput.required = false;
        } else {
            fileInput.required = true;
        }

        document.getElementById('resultModal').classList.remove('hidden');
    }

    function closeResultModal() {
        document.getElementById('resultModal').classList.add('hidden');
    }

    function viewResult(text) {
        alert("Resultados de laboratorio:\n\n" + text);
    }

    function switchTab(tabId) {
        // Ocultar todos los contenidos
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Desactivar todos los botones
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-[#007BFF]', 'text-[#007BFF]');
            btn.classList.add('border-transparent', 'text-gray-500');
        });

        // Mostrar tab actual
        document.getElementById(tabId).classList.remove('hidden');

        // Activar botón actual
        const activeBtnId = tabId.replace('tab-', 'btn-');
        const activeBtn = document.getElementById(activeBtnId);
        activeBtn.classList.add('border-[#007BFF]', 'text-[#007BFF]');
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
    }

    $(document).ready(function () {
        const config = {
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        };

        $('#labTablePendientes').DataTable(config);
        $('#labTableCompletadas').DataTable(config);
    });
</script>

<?php include '../views/layout/footer.php'; ?>
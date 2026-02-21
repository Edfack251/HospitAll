<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';

checkRole(['administrador', 'medico']);

$role = $_SESSION['user_role'] ?? '';

// Esta página ya está protegida por checkRole(['administrador', 'medico'])
// Pero validamos que el ID venga por GET y sea numérico
$patient_id = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : null;

if (isset($_GET['id']) && !$patient_id) {
    header("Location: clinical_history.php");
    exit();
}

$patient_data = null;
$history = [];
$labs = [];

require_once '../app/controllers/ClinicalHistoryController.php';

$controller = new ClinicalHistoryController($pdo);

if ($patient_id) {
    $data = $controller->show($patient_id);
    $patient_data = $data['patient_data'];
    $history = $data['history'];
    $labs = $data['labs'];
} else {
    $patients = $controller->search();
}

$pageTitle = 'Historial Clínico - HospitAll';
$activePage = 'historial';
$headerTitle = $patient_id ? 'Expediente Clínico' : 'Búsqueda de Historial';
$headerSubtitle = $patient_id ? 'Detalle completo del paciente y sus antecedentes.' : 'Consulte el historial de cualquier paciente registrado.';

include '../views/layout/header.php';
?>

<?php if (!$patient_id): ?>
    <!-- Vista de Búsqueda -->
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Buscar Paciente</h3>
            <p class="text-gray-500 text-sm">Utilice la tabla para localizar al paciente por nombre o documento de
                identidad.</p>
        </div>

        <table id="patientsSearchTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] border-b">
                    <th class="py-4">Paciente</th>
                    <th class="py-4">Identificación</th>
                    <th class="py-4">Género</th>
                    <th class="py-4">Última Visita</th>
                    <th class="py-4 text-right">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="py-4 font-bold text-[#007BFF]">
                            <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?>
                        </td>
                        <td class="py-4 text-sm">
                            <span class="text-xs text-gray-400 block">
                                <?php echo $p['identificacion_tipo']; ?>
                            </span>
                            <?php echo $p['identificacion']; ?>
                        </td>
                        <td class="py-4 text-sm text-gray-600">
                            <?php echo $p['genero']; ?>
                        </td>
                        <td class="py-4 text-xs text-gray-500">
                            <?php echo $p['última_cita'] ? date('d/m/Y', strtotime($p['última_cita'])) : 'Sin registros'; ?>
                        </td>
                        <td class="py-4 text-right">
                            <a href="clinical_history.php?id=<?php echo $p['id']; ?>"
                                class="bg-blue-50 text-[#007BFF] px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-100 transition-all border border-blue-200">
                                Ver Expediente
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function () {
            $('#patientsSearchTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 10,
                order: [[0, 'asc']]
            });
        });
    </script>

<?php elseif (!$patient_data): ?>
    <!-- Error: Paciente no encontrado -->
    <div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
        <div class="bg-red-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800">Paciente no encontrado</h3>
        <p class="text-gray-500 mt-2">El ID solicitado no corresponde a ningún registro activo.</p>
        <div class="mt-8">
            <a href="clinical_history.php"
                class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md inline-block">Volver a la
                búsqueda</a>
        </div>
    </div>

<?php else: ?>
    <!-- Detalle del Expediente -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Info Paciente -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-6">
                <div class="text-center mb-6">
                    <div
                        class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm">
                        <span class="text-3xl font-bold text-[#007BFF]">
                            <?php echo strtoupper(substr($patient_data['nombre'], 0, 1) . substr($patient_data['apellido'], 0, 1)); ?>
                        </span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800">
                        <?php echo htmlspecialchars($patient_data['nombre'] . ' ' . $patient_data['apellido']); ?>
                    </h4>
                    <span class="text-xs text-gray-400">
                        <?php echo $patient_data['identificacion_tipo']; ?>:
                        <?php echo $patient_data['identificacion']; ?>
                    </span>
                </div>

                <div class="space-y-4 text-sm border-t pt-6">
                    <div>
                        <p class="text-gray-400 text-xs font-semibold uppercase">Edad</p>
                        <p class="text-gray-700 font-medium">
                            <?php echo $patient_data['edad']; ?> años
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-semibold uppercase">Género</p>
                        <p class="text-gray-700 font-medium">
                            <?php echo $patient_data['genero']; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-semibold uppercase">Fecha Nacimiento</p>
                        <p class="text-gray-700 font-medium">
                            <?php echo date('d/m/Y', strtotime($patient_data['fecha_nacimiento'])); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-semibold uppercase">Teléfono</p>
                        <p class="text-gray-700 font-medium">
                            <?php echo htmlspecialchars($patient_data['telefono'] ?: 'No registrado'); ?>
                        </p>
                    </div>
                </div>

                <div class="mt-8">
                    <a href="clinical_history.php"
                        class="w-full flex items-center justify-center px-4 py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-500 hover:bg-gray-50 transition-all">
                        <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Nueva Búsqueda
                    </a>
                </div>
            </div>
        </div>

        <!-- Cuerpo del Historial -->
        <div class="lg:col-span-3 space-y-8">
            <!-- Pestañas de Navegación -->
            <div class="bg-white p-2 rounded-xl border border-gray-100 flex space-x-2">
                <button onclick="switchHistoryTab('tab-consultas')" id="btn-tab-consultas"
                    class="history-tab flex-1 py-2 text-xs font-bold rounded-lg transition-all bg-[#007BFF] text-white">
                    Consultas Médicas (
                    <?php echo count($history); ?>)
                </button>
                <button onclick="switchHistoryTab('tab-labs')" id="btn-tab-labs"
                    class="history-tab flex-1 py-2 text-xs font-bold rounded-lg transition-all text-gray-500 hover:bg-gray-50">
                    Laboratorio (
                    <?php echo count($labs); ?>)
                </button>
            </div>

            <!-- Tab: Consultas -->
            <div id="tab-consultas" class="history-content-tab space-y-6">
                <?php if (empty($history)): ?>
                    <div class="bg-white p-12 rounded-2xl border border-dashed border-gray-200 text-center">
                        <p class="text-gray-400 italic">No existen registros de consultas para este paciente.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-b">
                                <div>
                                    <span class="text-xs font-bold text-[#007BFF] uppercase tracking-wider">
                                        <?php echo date('d/m/Y', strtotime($h['fecha'])); ?>
                                    </span>
                                    <h5 class="text-sm font-bold text-gray-700 mt-1">Atendido por: Dr.
                                        <?php echo htmlspecialchars($h['medico_nombre'] . ' ' . $h['medico_apellido']); ?>
                                    </h5>
                                </div>
                                <span
                                    class="bg-blue-100 text-[#007BFF] text-[10px] font-bold px-3 py-1 rounded-full uppercase">Consulta</span>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h6 class="text-xs font-bold text-gray-400 uppercase mb-2">Diagnóstico</h6>
                                    <p
                                        class="text-sm text-gray-800 leading-relaxed bg-blue-50/50 p-4 rounded-xl border border-blue-50">
                                        <?php echo nl2br(htmlspecialchars($h['diagnostico'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <h6 class="text-xs font-bold text-gray-400 uppercase mb-2">Tratamiento</h6>
                                    <p
                                        class="text-sm text-gray-800 leading-relaxed bg-green-50/50 p-4 rounded-xl border border-green-50">
                                        <?php echo nl2br(htmlspecialchars($h['tratamiento'])); ?>
                                    </p>
                                </div>
                                <?php if ($h['observaciones']): ?>
                                    <div class="md:col-span-2">
                                        <h6 class="text-xs font-bold text-gray-400 uppercase mb-2">Observaciones Clínicas</h6>
                                        <p class="text-sm text-gray-600 italic">"
                                            <?php echo nl2br(htmlspecialchars($h['observaciones'])); ?>"
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tab: Laboratorios -->
            <div id="tab-labs" class="history-content-tab hidden space-y-4">
                <?php if (empty($labs)): ?>
                    <div class="bg-white p-12 rounded-2xl border border-dashed border-gray-200 text-center">
                        <p class="text-gray-400 italic">No existen órdenes de laboratorio para este paciente.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($labs as $l): ?>
                            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-4">
                                        <span
                                            class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded uppercase tracking-widest">Lab
                                            Result</span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo date('d/m/Y', strtotime($l['created_at'])); ?>
                                        </span>
                                    </div>
                                    <h5 class="text-sm font-bold text-gray-800 mb-2">
                                        <?php echo htmlspecialchars($l['descripcion']); ?>
                                    </h5>
                                    <p class="text-xs text-gray-500 line-clamp-3 mb-4 italic">"
                                        <?php echo htmlspecialchars($l['resultado'] ?: 'Pendiente de procesamiento'); ?>"
                                    </p>
                                </div>

                                <div class="flex items-center justify-between border-t pt-4 mt-2">
                                    <span
                                        class="text-[10px] font-bold <?php echo $l['estado'] === 'Completada' ? 'text-green-500' : 'text-amber-500'; ?>">
                                        ●
                                        <?php echo strtoupper($l['estado']); ?>
                                    </span>
                                    <?php if ($l['archivo_pdf']): ?>
                                        <a href="<?php echo htmlspecialchars($l['archivo_pdf']); ?>" target="_blank"
                                            class="bg-blue-50 text-[#007BFF] px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-100 transition-all flex items-center">
                                            <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            Ver PDF
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchHistoryTab(tabId) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.history-content-tab').forEach(tab => tab.classList.add('hidden'));
            // Mostrar el seleccionado
            document.getElementById(tabId).classList.remove('hidden');

            // Actualizar botones
            document.querySelectorAll('.history-tab').forEach(btn => {
                btn.classList.remove('bg-[#007BFF]', 'text-white');
                btn.classList.add('text-gray-500', 'hover:bg-gray-50');
            });

            const activeBtn = document.getElementById('btn-' + tabId);
            activeBtn.classList.add('bg-[#007BFF]', 'text-white');
            activeBtn.classList.remove('text-gray-500', 'hover:bg-gray-50');
        }
    </script>
<?php endif; ?>

<?php include '../views/layout/footer.php'; ?>
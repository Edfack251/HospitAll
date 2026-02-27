<?php
session_start();
require_once '../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador', 'medico']);

$id = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : null;

if (!$id) {
    header("Location: appointments.php");
    exit();
}

$controller = new AppointmentsController($pdo);
$data = $controller->getAttendData($id);

$cita = $data['cita'];
$historial_previo = $data['historial_previo'];
$resultados_lab = $data['resultados_lab'];

// Calcular edad
$cumpleanos = new DateTime($cita['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($cumpleanos)->y;

$pageTitle = 'Atender Cita - HospitAll';
$activePage = 'citas';
$headerTitle = 'Atención Médica';
$headerSubtitle = 'Registro de consulta clínica y diagnóstico.';

include '../views/layout/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Información del Paciente -->
    <div class="md:col-span-1 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-[#007BFF]">Datos del Paciente</h3>
            <div class="space-y-3 text-sm">
                <p><span class="font-semibold text-gray-600">Nombre:</span> <br>
                    <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                </p>
                <p><span class="font-semibold text-gray-600"><?php echo $cita['identificacion_tipo']; ?>:</span>
                    <?php echo htmlspecialchars($cita['identificacion']); ?>
                </p>
                <p><span class="font-semibold text-gray-600">Edad:</span>
                    <?php echo $edad; ?> años
                </p>
                <p><span class="font-semibold text-gray-600">Género:</span>
                    <?php echo htmlspecialchars($cita['genero']); ?>
                </p>
                <hr class="my-4">
                <p><span class="font-semibold text-gray-600">Motivo/Observaciones:</span> <br>
                    <?php echo nl2br(htmlspecialchars($cita['observaciones'])); ?>
                </p>
            </div>
        </div>

        <!-- Resultados de Laboratorio Previos -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-[#28A745]">Resultados de Laboratorio</h3>
            <?php if (empty($resultados_lab)): ?>
                <p class="text-xs text-gray-400 text-center py-4 italic">No hay resultados previos.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($resultados_lab as $rl): ?>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[10px] font-bold text-[#28A745]">
                                    <?php echo date('d/m/Y', strtotime($rl['fecha_resultado'])); ?>
                                </span>
                                <?php if ($rl['archivo_pdf']): ?>
                                    <a href="<?php echo htmlspecialchars($rl['archivo_pdf']); ?>" target="_blank"
                                        class="text-[#007BFF] hover:underline text-[10px] font-bold flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        VER PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($rl['descripcion']); ?>
                            </p>
                            <p class="text-[10px] text-gray-500 mt-1 line-clamp-2">
                                <?php echo htmlspecialchars($rl['resultado']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulario de Atención -->
    <div class="md:col-span-2 space-y-6">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <form action="api/appointments_save_attention.php" method="POST" class="space-y-6">
                <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">
                <input type="hidden" name="paciente_id" value="<?php echo $cita['paciente_id']; ?>">
                <input type="hidden" name="medico_id" value="<?php echo $cita['medico_id']; ?>">
                <input type="hidden" name="from" value="<?php echo htmlspecialchars($_GET['from'] ?? ''); ?>">
                <input type="hidden" name="back_id" value="<?php echo htmlspecialchars($_GET['patient_id'] ?? ''); ?>">

                <!-- 1. Sección de Laboratorio (Ahora Primero) -->
                <div class="bg-gray-50/50 -mx-8 px-8 py-6 border-b">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-[#28A745]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                </path>
                            </svg>
                            <h4 class="text-md font-bold text-gray-700">¿Requerir Laboratorio?</h4>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="requireLab" name="enviar_laboratorio" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-[#28A745]">
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-600">Sí, enviar o re-enviar orden</span>
                        </label>
                    </div>

                    <div id="labFields" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Instrucciones / Exámenes Solicitados</label>
                            <textarea name="laboratorio_descripcion" id="labText"
                                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#28A745] outline-none transition-all bg-white"
                                rows="3"
                                placeholder="Ej: Hemograma completo, Perfil lipídico, Glucosa en ayunas..."></textarea>
                            <p class="text-[10px] text-gray-400 mt-2 italic">Al activar esta opción, el registro se
                                mantendrá
                                como pendiente de resultados y se enviará una nueva orden.</p>
                        </div>
                    </div>
                    <div id="noLabInfo"
                        class="text-xs text-blue-600 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Complete el diagnóstico si ya no requiere más análisis de laboratorio.
                    </div>
                </div>

                <!-- 2. Sección de Diagnóstico y Tratamiento -->
                <div id="diagnosisSection" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Diagnóstico Médico</label>
                        <textarea name="diagnostico" id="diagInput" required
                            class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all"
                            rows="4" placeholder="Escriba el diagnóstico del paciente..."><?php
                            echo ($historial_previo && strpos($historial_previo['diagnostico'], 'Pendiente') === false) ? htmlspecialchars($historial_previo['diagnostico']) : '';
                            ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Tratamiento / Receta</label>
                        <textarea name="tratamiento" id="treatInput" required
                            class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all"
                            rows="4" placeholder="Escriba el tratamiento o medicamentos recetados..."><?php
                            echo ($historial_previo && strpos($historial_previo['tratamiento'], 'Pendiente') === false) ? htmlspecialchars($historial_previo['tratamiento']) : '';
                            ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Observaciones Adicionales</label>
                        <textarea name="observaciones_clinicas"
                            class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all"
                            rows="2"><?php echo $historial_previo ? htmlspecialchars($historial_previo['observaciones']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6">
                    <?php
                    $from = $_GET['from'] ?? '';
                    $backId = $_GET['patient_id'] ?? '';
                    $cancelUrl = 'appointments.php';
                    if ($_SESSION['user_role'] === 'medico') {
                        if ($from === 'history' && !empty($backId)) {
                            $cancelUrl = 'patient_portal.php?patient_id=' . $backId;
                        } else {
                            $cancelUrl = 'doctor_agenda.php';
                        }
                    }
                    ?>
                    <a href="<?php echo $cancelUrl; ?>"
                        class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50 transition-all">
                        Cancelar
                    </a>
                    <button type="submit" id="submitBtn"
                        class="bg-[#007BFF] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
                        Finalizar Atención
                    </button>
                </div>

                <script>
                    const requireLab = document.getElementById('requireLab');
                    const labFields = document.getElementById('labFields');
                    const labText = document.getElementById('labText');
                    const noLabInfo = document.getElementById('noLabInfo');
                    const diagnosisSection = document.getElementById('diagnosisSection');
                    const diagInput = document.getElementById('diagInput');
                    const treatInput = document.getElementById('treatInput');
                    const submitBtn = document.getElementById('submitBtn');

                    if (requireLab) {
                        requireLab.addEventListener('change', function (e) {
                            if (e.target.checked) {
                                // Mostrar campos de lab y ocultar diagnóstico
                                labFields.classList.remove('hidden');
                                noLabInfo.classList.add('hidden');
                                diagnosisSection.classList.add('hidden');

                                // Requerimientos
                                labText.required = true;
                                diagInput.required = false;
                                treatInput.required = false;

                                // Estética del botón
                                submitBtn.innerText = 'Enviar a Laboratorio';
                                submitBtn.classList.replace('bg-[#007BFF]', 'bg-[#28A745]');
                                submitBtn.classList.replace('hover:bg-blue-700', 'hover:bg-green-700');
                            } else {
                                // Mostrar diagnóstico y ocultar lab
                                labFields.classList.add('hidden');
                                noLabInfo.classList.remove('hidden');
                                diagnosisSection.classList.remove('hidden');

                                // Requerimientos
                                labText.required = false;
                                diagInput.required = true;
                                treatInput.required = true;

                                // Estética del botón
                                submitBtn.innerText = 'Finalizar Atención';
                                submitBtn.classList.replace('bg-[#28A745]', 'bg-[#007BFF]');
                                submitBtn.classList.replace('hover:bg-green-700', 'hover:bg-blue-700');
                            }
                        });
                    }
                </script>
            </form>
        </div>
    </div>
</div>

<?php include '../views/layout/footer.php'; ?>
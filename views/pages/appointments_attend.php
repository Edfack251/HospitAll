<?php
use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['administrador', 'medico']);

$id = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : null;

$walkin_id = isset($_GET['walkin_id']) && is_numeric($_GET['walkin_id'])
    ? (int) $_GET['walkin_id']
    : null;

if (!$id && !$walkin_id) {
    UrlHelper::redirect('appointments');
    exit;
}

$es_walkin = false;
$cita = null;
$historial_previo = null;
$resultados_lab = [];

if ($walkin_id) {
    $visitaRepo = new \App\Repositories\VisitaWalkinRepository($pdo);
    $visita = $visitaRepo->getById($walkin_id);
    if (!$visita) {
        UrlHelper::redirect('appointments');
        exit;
    }
    $pacienteRepo = new \App\Repositories\PatientRepository($pdo);
    $paciente = $pacienteRepo->getById($visita['paciente_id']);
    
    // Simulate $cita structure
    $cita = [
        'id' => null,
        'paciente_id' => $paciente['id'],
        'medico_id' => $_SESSION['user_role'] === 'medico' ? $_SESSION['medico_id'] : null,
        'paciente_nombre' => $paciente['nombre'],
        'paciente_apellido' => $paciente['apellido'],
        'identificacion_tipo' => $paciente['identificacion_tipo'],
        'identificacion' => $paciente['identificacion'],
        'fecha_nacimiento' => $paciente['fecha_nacimiento'],
        'genero' => $paciente['genero'],
        'observaciones' => 'Paciente Walk-in (Turno ' . $visita['turno_numero'] . ')',
        'estado' => 'Atendida'
    ];
    $es_walkin = true;
    
    // Fetch Lab Results
    $appService = new \App\Services\AppointmentsService($pdo);
    $resultados_lab = $appService->getResultadosLab($paciente['id']);
} else {
    $controller = new AppointmentsController($pdo);
    $data = $controller->getAttendData($id);

    $cita = $data['cita'];
    $historial_previo = $data['historial_previo'];
    $resultados_lab = $data['resultados_lab'];
}

// Calcular edad
$cumpleanos = new DateTime($cita['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($cumpleanos)->y;

$pageTitle = 'Atender Cita - HospitAll';
$activePage = 'citas';
$headerTitle = 'Atención Médica';
$headerSubtitle = 'Registro de consulta clínica y diagnóstico.';

include __DIR__ . '/../layout/header.php';
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
            <form action="<?php echo UrlHelper::url('api/appointments/saveAttention'); ?>" method="POST"
                class="space-y-6" id="attendForm">
                <?php $csrf = CsrfHelper::generateToken(); ?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="cita_id" value="<?php echo $cita['id'] ?? ''; ?>">
                <?php if ($es_walkin): ?>
                <input type="hidden" name="walkin_id" value="<?php echo $walkin_id; ?>">
                <?php endif; ?>
                <input type="hidden" name="paciente_id" value="<?php echo $cita['paciente_id']; ?>">
                <input type="hidden" name="medico_id" value="<?php echo $cita['medico_id']; ?>">
                <input type="hidden" name="from" value="<?php echo htmlspecialchars($_GET['from'] ?? ''); ?>">
                <input type="hidden" name="back_id" value="<?php echo htmlspecialchars($_GET['patient_id'] ?? ''); ?>">

                <!-- 1. Sección de Laboratorio -->
                <div class="bg-gray-50/50 -mx-8 px-8 py-6 border-b">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-[#28A745]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                </path>
                            </svg>
                            <h4 class="text-md font-bold text-gray-700">¿Requerir laboratorio?</h4>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="requireLab" name="enviar_laboratorio" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-[#28A745]">
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-600">Sí, enviar orden</span>
                        </label>
                    </div>

                    <div id="labFields" class="hidden space-y-4">
                        <label class="block text-sm font-semibold mb-2">Seleccione los exámenes</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            $examenes_lab = ['Hemograma', 'Glucosa', 'Perfil lipídico', 'Pruebas hepáticas'];
                            foreach ($examenes_lab as $examen): ?>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 bg-white hover:border-[#28A745] hover:bg-green-50/50 cursor-pointer transition-all">
                                    <input type="checkbox" name="examenes_laboratorio[]"
                                        value="<?php echo htmlspecialchars($examen); ?>"
                                        class="lab-checkbox w-4 h-4 text-[#28A745] border-gray-300 rounded focus:ring-[#28A745]">
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($examen); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-semibold mb-1 text-gray-500">Observaciones adicionales (opcional)</label>
                            <textarea name="laboratorio_descripcion" id="labText"
                                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#28A745] outline-none transition-all bg-white text-sm"
                                rows="2"
                                placeholder="Instrucciones especiales para el técnico de laboratorio..."></textarea>
                        </div>
                        <p class="text-[10px] text-gray-400 italic">Al activar esta opción, el registro se mantendrá
                            como pendiente de resultados y se enviará una nueva orden.</p>
                    </div>
                </div>

                <!-- 2. Sección de Imágenes Médicas -->
                <div class="bg-gray-50/50 -mx-8 px-8 py-6 border-b">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <h4 class="text-md font-bold text-gray-700">¿Requerir imágenes médicas?</h4>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="requireImg" name="enviar_imagenes" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-indigo-500">
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-600">Sí, enviar orden</span>
                        </label>
                    </div>

                    <div id="imgFields" class="hidden space-y-4">
                        <label class="block text-sm font-semibold mb-2">Seleccione los estudios</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            $estudios_img = ['Rayos X', 'Tomografía', 'Resonancia', 'Ultrasonido'];
                            foreach ($estudios_img as $estudio): ?>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 bg-white hover:border-indigo-400 hover:bg-indigo-50/50 cursor-pointer transition-all">
                                    <input type="checkbox" name="estudios_imagenes[]"
                                        value="<?php echo htmlspecialchars($estudio); ?>"
                                        class="img-checkbox w-4 h-4 text-indigo-500 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($estudio); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[10px] text-gray-400 italic">Se generará una orden por cada estudio seleccionado
                            para el técnico de imágenes.</p>
                    </div>
                </div>

                <div id="noStudiesInfo"
                    class="text-xs text-blue-600 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-center -mx-8 mx-0 px-8">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Complete el diagnóstico si ya no requiere estudios de laboratorio ni imágenes.
                </div>

                <!-- 2. Sección de Diagnóstico y Tratamiento -->
                <div id="diagnosisSection" class="space-y-6">
                    <?php if ($historial_previo && strpos($historial_previo['diagnostico'], 'Pendiente') === false): ?>
                        <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-200 mb-6">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-500 mr-2 mt-0.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-bold text-yellow-800">Historial Clínico Ya Registrado</h4>
                                    <p class="text-xs text-yellow-700 mt-1">Por normativas legales y de auditoría, el
                                        diagnóstico previo <strong>no puede ser modificado ni sobreescrito.</strong> En su
                                        lugar, utilice el formulario de Adenda Médica abajo para corregir o agregar nueva
                                        información al expediente.</p>
                                </div>
                            </div>
                        </div>

                        <!-- HISTORIAL COMPLETO EN ORDEN CRONOLÓGICO -->
                        <div class="space-y-6">
                            <?php foreach ($historial_completo as $index => $registro): ?>
                                <?php $es_original = ($index === 0); ?>
                                <div
                                    class="bg-gray-50/80 p-5 rounded-2xl border <?php echo $es_original ? 'border-gray-200' : 'border-blue-100 bg-blue-50/20'; ?>">
                                    <h4
                                        class="text-sm font-bold <?php echo $es_original ? 'text-gray-700' : 'text-blue-800'; ?> mb-3 flex items-center justify-between">
                                        <span class="flex items-center">
                                            <?php if ($es_original): ?>
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                                Registro Original
                                            <?php else: ?>
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v16m8-8H4"></path>
                                                </svg>
                                                Adenda Médica <?php echo $index; ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-xs font-normal text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($registro['created_at'])); ?> • Dr.
                                            <?php echo htmlspecialchars($registro['medico_nombre'] . ' ' . $registro['medico_apellido']); ?>
                                        </span>
                                    </h4>

                                    <div class="opacity-80 pointer-events-none space-y-4">
                                        <?php if ($es_original): ?>
                                            <div>
                                                <label class="block text-xs font-semibold mb-1 text-gray-600">Diagnóstico</label>
                                                <div
                                                    class="w-full px-4 py-2 rounded-lg border bg-white text-sm whitespace-pre-wrap">
                                                    <?php echo htmlspecialchars($registro['diagnostico']); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold mb-1 text-gray-600">Tratamiento /
                                                    Receta</label>
                                                <div
                                                    class="w-full px-4 py-2 rounded-lg border bg-white text-sm whitespace-pre-wrap">
                                                    <?php echo htmlspecialchars($registro['tratamiento']); ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($registro['observaciones'])): ?>
                                                <div>
                                                    <label class="block text-xs font-semibold mb-1 text-gray-600">Observaciones</label>
                                                    <div
                                                        class="w-full px-4 py-2 rounded-lg border bg-white text-sm whitespace-pre-wrap">
                                                        <?php echo htmlspecialchars($registro['observaciones']); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div>
                                                <div
                                                    class="w-full px-4 py-3 rounded-lg border border-blue-100 bg-white text-sm whitespace-pre-wrap italic text-gray-700">
                                                    <?php echo htmlspecialchars($registro['observaciones']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($es_original): ?>
                                        <!-- Keep original active diagnostic/treatment for backend payload -->
                                        <input type="hidden" name="diagnostico"
                                            value="<?php echo htmlspecialchars($registro['diagnostico']); ?>">
                                        <input type="hidden" name="tratamiento"
                                            value="<?php echo htmlspecialchars($registro['tratamiento']); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-6 border-gray-200">

                        <!-- FORMULARIO DE ADENDA (APPEND-ONLY) -->
                        <div class="bg-blue-50/30 p-6 rounded-2xl border border-blue-100">
                            <h3 class="text-md font-bold text-blue-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Agregar Adenda Médica
                            </h3>
                            <input type="hidden" name="es_adenda" value="1">

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-blue-800">Seleccione el motivo de la
                                        Adenda *</label>
                                    <select name="adenda_motivo" required
                                        class="w-full px-4 py-2 rounded-lg border border-blue-200 bg-white focus:ring-2 focus:ring-[#007BFF] outline-none">
                                        <option value="">-- Seleccione un motivo --</option>
                                        <option value="Corrección de Diagnóstico">Corrección de Diagnóstico (Error material)
                                        </option>
                                        <option value="Ajuste de Tratamiento">Ajuste de Tratamiento / Receta</option>
                                        <option value="Anotación Adicional">Anotación Adicional / Hallazgo tardío</option>
                                        <option value="Observación de Laboratorio">Observación de reporte de laboratorio
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-blue-800">Detalles de la corrección
                                        o ampliación *</label>
                                    <textarea name="adenda_texto" required
                                        class="w-full px-4 py-2 rounded-lg border border-blue-200 bg-white focus:ring-2 focus:ring-[#007BFF] outline-none"
                                        rows="4"
                                        placeholder="Describa con exactitud los cambios o la nueva información para anexar legalmente al expediente base..."></textarea>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- FORMULARIO ESTÁNDAR PARA PRIMERA VEZ -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Diagnóstico Médico</label>
                            <textarea name="diagnostico" id="diagInput" required
                                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all"
                                rows="4" placeholder="Escriba el diagnóstico del paciente..."></textarea>
                        </div>

                        <div class="space-y-4">
                            <label class="block text-sm font-semibold mb-2">Tratamiento / Receta</label>
                            
                            <!-- Buscador de Medicamentos con Stock -->
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 mb-4">
                                <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Buscador de Medicamentos (Stock Disponible)</label>
                                <div class="relative">
                                    <input type="text" id="medSearch" placeholder="Buscar por nombre..." class="w-full px-4 py-2 rounded-lg border border-blue-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                    <div id="medResults" class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-10 hidden max-h-48 overflow-y-auto"></div>
                                </div>
                            </div>

                            <textarea name="tratamiento" id="treatInput" required
                                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all"
                                rows="4" placeholder="Escriba el tratamiento o medicamentos recetados..."></textarea>
                            <p class="text-[10px] text-gray-400 italic">Los medicamentos seleccionados del buscador se añadirán automáticamente aquí.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Observaciones Adicionales</label>
                            <textarea name="observaciones_clinicas"
                                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all"
                                rows="2"></textarea>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex justify-end space-x-4 pt-6">
                    <?php
                    $from = $_GET['from'] ?? '';
                    $backId = $_GET['patient_id'] ?? '';
                    $cancelUrl = UrlHelper::url('appointments');
                    if ($_SESSION['user_role'] === 'medico') {
                        if ($from === 'history' && !empty($backId)) {
                            $cancelUrl = UrlHelper::url('patient_portal') . '?patient_id=' . $backId;
                        } else {
                            $cancelUrl = UrlHelper::url('doctor_agenda');
                        }
                    }
                    ?>
                    <a href="<?php echo $cancelUrl; ?>"
                        class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50 transition-all">
                        Cancelar
                    </a>
                    <button type="submit" id="submitBtn"
                        class="bg-[#007BFF] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                        Finalizar Atención
                    </button>
                </div>

                <script>
                    const attendForm = document.getElementById('attendForm');
                    const requireLab = document.getElementById('requireLab');
                    const requireImg = document.getElementById('requireImg');
                    const labFields = document.getElementById('labFields');
                    const imgFields = document.getElementById('imgFields');
                    const labText = document.getElementById('labText');
                    const noStudiesInfo = document.getElementById('noStudiesInfo');
                    const diagnosisSection = document.getElementById('diagnosisSection');
                    const diagInput = document.getElementById('diagInput');
                    const treatInput = document.getElementById('treatInput');
                    const submitBtn = document.getElementById('submitBtn');

                    function updateFormState() {
                        const labActive = requireLab && requireLab.checked;
                        const imgActive = requireImg && requireImg.checked;
                        const anyStudy = labActive || imgActive;

                        // Mostrar/Ocultar campos
                        if (labFields) labFields.classList.toggle('hidden', !labActive);
                        if (imgFields) imgFields.classList.toggle('hidden', !imgActive);
                        if (noStudiesInfo) noStudiesInfo.classList.toggle('hidden', anyStudy);
                        if (diagnosisSection) diagnosisSection.classList.toggle('hidden', anyStudy);

                        // Requerimientos de diagnóstico
                        if (diagInput) diagInput.required = !anyStudy;
                        if (treatInput) treatInput.required = !anyStudy;

                        // Botón
                        if (submitBtn) {
                            if (anyStudy) {
                                submitBtn.innerText = 'Enviar a estudios';
                                submitBtn.className = submitBtn.className
                                    .replace('bg-[#007BFF]', 'bg-[#28A745]')
                                    .replace('hover:bg-blue-700', 'hover:bg-green-700');
                            } else {
                                submitBtn.innerText = 'Finalizar Atención';
                                submitBtn.className = submitBtn.className
                                    .replace('bg-[#28A745]', 'bg-[#007BFF]')
                                    .replace('hover:bg-green-700', 'hover:bg-blue-700');
                            }
                        }
                    }

                    // Al desmarcar un toggle, limpiar sus checkboxes
                    function clearCheckboxes(selector) {
                        document.querySelectorAll(selector).forEach(cb => cb.checked = false);
                    }

                    if (requireLab) {
                        requireLab.addEventListener('change', function () {
                            if (!this.checked) clearCheckboxes('.lab-checkbox');
                            updateFormState();
                        });
                    }

                    if (requireImg) {
                        requireImg.addEventListener('change', function () {
                            if (!this.checked) clearCheckboxes('.img-checkbox');
                            updateFormState();
                        });
                    }

                    if (attendForm) {
                        attendForm.addEventListener('submit', function (e) {
                            // Validar al menos un checkbox si el toggle está activo
                            if (requireLab && requireLab.checked) {
                                const labChecked = document.querySelectorAll('.lab-checkbox:checked');
                                if (labChecked.length === 0) {
                                    e.preventDefault();
                                    alert('Seleccione al menos un examen de laboratorio.');
                                    return;
                                }
                            }
                            if (requireImg && requireImg.checked) {
                                const imgChecked = document.querySelectorAll('.img-checkbox:checked');
                                if (imgChecked.length === 0) {
                                    e.preventDefault();
                                    alert('Seleccione al menos un estudio de imagen.');
                                    return;
                                }
                            }
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = 'Procesando...';
                        });
                    }

                    // --- Buscador de Medicamentos con Stock ---
                    const medSearch = document.getElementById('medSearch');
                    const medResults = document.getElementById('medResults');
                    let allMeds = [];

                    if (medSearch) {
                        fetch('<?php echo UrlHelper::url('api/pharmacy/medicamentos-stock'); ?>')
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) allMeds = data.medicamentos;
                            });

                        medSearch.addEventListener('input', function() {
                            const q = this.value.toLowerCase().trim();
                            if (q.length < 2) {
                                medResults.classList.add('hidden');
                                return;
                            }

                            const filtered = allMeds.filter(m => m.nombre.toLowerCase().includes(q));
                            if (filtered.length > 0) {
                                medResults.innerHTML = filtered.map(m => `
                                    <div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0" onclick="addMed('${m.nombre}', ${m.stock_actual})">
                                        <div class="flex justify-between items-center">
                                            <span class="font-bold text-gray-800 text-sm">${m.nombre}</span>
                                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded ${m.stock_actual < 10 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}">Stock: ${m.stock_actual}</span>
                                        </div>
                                    </div>
                                `).join('');
                                medResults.classList.remove('hidden');
                            } else {
                                medResults.innerHTML = '<div class="p-4 text-center text-xs text-gray-400">No hay stock disponible</div>';
                                medResults.classList.remove('hidden');
                            }
                        });

                        document.addEventListener('click', (e) => {
                            if (!medSearch.contains(e.target) && !medResults.contains(e.target)) {
                                medResults.classList.add('hidden');
                            }
                        });
                    }

                    function addMed(nombre, stock) {
                        const current = treatInput.value;
                        const newLine = treatInput.value ? '\n' : '';
                        treatInput.value = current + newLine + '- ' + nombre + ' (Unidades en stock: ' + stock + '): ';
                        medSearch.value = '';
                        medResults.classList.add('hidden');
                        treatInput.focus();
                        
                        // Scroll to end of textarea
                        treatInput.scrollTop = treatInput.scrollHeight;
                    }
                </script>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
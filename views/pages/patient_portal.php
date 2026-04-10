<?php
use App\Helpers\UrlHelper;
use App\Controllers\PatientPortalController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['paciente', 'administrador', 'medico']);

$role = $_SESSION['user_role'] ?? '';

if ($role === 'paciente') {
    // Los pacientes SOLO pueden ver su propia información
    $paciente_id = $_SESSION['paciente_id'] ?? null;
} elseif (in_array($role, ['administrador', 'medico'])) {
    // Administradores y médicos pueden consultar cualquier paciente vía GET
    $paciente_id = isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])
        ? (int) $_GET['patient_id']
        : null;
} else {
    $paciente_id = null;
}

if (!$paciente_id) {
    UrlHelper::redirect('dashboard');
}

$pageTitle = 'Mi Portal - HospitAll';
$activePage = 'portal';
$headerTitle = 'Mi Historial Clínico';

$stmt_pac = $pdo->prepare("SELECT identificacion, id FROM pacientes WHERE id = ?");
$stmt_pac->execute([$paciente_id]);
$pac_datos = $stmt_pac->fetch();
$cedula_mask = \App\Helpers\PrivacyHelper::maskCedula($pac_datos['identificacion'] ?? '', $pac_datos['id'] ?? null);

$headerSubtitle = 'Consulta tus diagnósticos y laboratorio. Cédula: ' . $cedula_mask;


$controller = new PatientPortalController($pdo);

if ($role === 'paciente') {
    // Para pacientes, usamos el método index que resuelve ID automáticamente y verifica políticas
    $dashboard_data = $controller->index();
    $paciente_id = $dashboard_data['paciente_id'];
} else {
    // Para médicos y administradores, usamos show() y cargamos los datos
    $old_data = $controller->show($paciente_id);
    $dashboard_data = [
        'citas_proximas' => $old_data['citas_proximas'] ?? [],
        'historial_reciente' => $old_data['historial'] ?? [],
        'laboratorio_disponible' => array_filter($old_data['laboratorio'] ?? [], fn($l) => $l['estado'] === 'Completada'),
        'prescripciones_activas' => array_filter($old_data['prescripciones'] ?? [], fn($p) => $p['estado'] === 'Pendiente'),
        'facturas_pendientes' => []
    ];
}

$citas_proximas = $dashboard_data['citas_proximas'];
$historial = $dashboard_data['historial_reciente'];
$laboratorio = $dashboard_data['laboratorio_disponible'];
$prescripciones = $dashboard_data['prescripciones_activas'];
$facturas_pendientes = $dashboard_data['facturas_pendientes'] ?? [];

include __DIR__ . '/../layout/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Columna Izquierda: Citas y Laboratorio -->
    <div class="lg:col-span-1 space-y-8">
        <!-- Citas Próximas -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-[#212529]">Próximas Citas</h3>
                <?php if ($_SESSION['user_role'] !== 'medico'): ?>
                    <a href="<?php echo App\Helpers\UrlHelper::url('appointments_schedule'); ?>"
                        class="bg-[#007BFF] text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-blue-700 transition">
                        + Agendar
                    </a>
                <?php endif; ?>
            </div>
            <?php if (empty($citas_proximas)): ?>
                <p class="text-[#6C757D] text-sm text-center py-4">No tienes citas programadas.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($citas_proximas as $c): ?>
                        <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-xs font-bold text-[#007BFF]">
                                    <?php echo date('d/m/Y', strtotime($c['fecha'])); ?> - <?php echo $c['hora']; ?>
                                </span>
                                <span class="text-[10px] bg-white px-2 py-0.5 rounded-full border text-gray-500">
                                    <?php echo $c['estado']; ?>
                                </span>
                            </div>
                            <p class="text-xs font-medium text-gray-700">Dr.
                                <?php echo htmlspecialchars($c['medico_nombre'] . ' ' . $c['medico_apellido']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Resultados de Laboratorio -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-[#212529] mb-6">Laboratorio</h3>
            <?php if (empty($laboratorio)): ?>
                <p class="text-[#6C757D] text-sm text-center py-4">Sin resultados.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($laboratorio as $l): ?>
                        <div class="p-3 border rounded-xl flex justify-between items-center">
                            <div class="truncate mr-2">
                                <p class="font-semibold text-xs truncate">
                                    <?php echo htmlspecialchars($l['descripcion']); ?>
                                </p>
                                <p class="text-[10px] text-[#6C757D]">
                                    <?php echo date('d/m/Y', strtotime($l['fecha_resultado'] ?? $l['fecha'])); ?>
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0 <?php echo $l['estado'] === 'Completada' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                    <?php echo $l['estado']; ?>
                                </span>
                                <?php if ($l['archivo_pdf']): ?>
                                    <a href="<?php echo \App\Helpers\UrlHelper::url($l['archivo_pdf']); ?>" target="_blank"
                                        class="text-[#007BFF] hover:text-blue-800" title="Descargar PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prescripciones Médicas -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-[#721C24] mb-6">Recetas Médicas</h3>
            <?php if (empty($prescripciones)): ?>
                <p class="text-[#6C757D] text-sm text-center py-4">No hay recetas emitidas.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($prescripciones as $p): ?>
                        <div class="p-3 bg-red-50 border border-red-100 rounded-xl">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-xs font-bold text-[#721C24]">
                                    <?php echo date('d/m/Y', strtotime($p['fecha'])); ?>
                                </span>
                                <span class="text-[10px] bg-white px-2 py-0.5 rounded-full border text-[#721C24] font-bold">
                                    <?php echo $p['estado']; ?>
                                </span>
                            </div>
                            <p class="text-[11px] font-medium text-gray-700">Dr.
                                <?php echo htmlspecialchars($p['medico_nombre'] . ' ' . $p['medico_apellido']); ?>
                            </p>
                            <a href="#" class="text-[10px] text-[#721C24] font-bold mt-2 inline-block hover:underline">VER
                                DETALLES</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Facturas Pendientes -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-orange-600 mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                    </path>
                </svg>
                Facturas Pendientes
            </h3>
            <?php if (empty($facturas_pendientes)): ?>
                <p class="text-[#6C757D] text-sm text-center py-4">No tienes facturas por pagar.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($facturas_pendientes as $f): ?>
                        <div class="p-4 bg-orange-50 border border-orange-100 rounded-xl">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-bold text-orange-700">#<?php echo $f['id']; ?></span>
                                <span
                                    class="text-xs font-semibold text-gray-500"><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></span>
                            </div>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase font-bold">Total a pagar</p>
                                    <p class="text-lg font-bold text-[#212529]">RD$ <?php echo number_format($f['total'], 2); ?>
                                    </p>
                                </div>
                                <a href="<?php echo App\Helpers\UrlHelper::url('billing_pay'); ?>?id=<?php echo $f['id']; ?>"
                                    class="bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-orange-700 transition">Pagar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Columna Derecha: Historial y Tratamientos -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-full">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-[#212529]">Historial y Tratamientos</h3>
                <?php if (in_array($_SESSION['user_role'] ?? '', ['administrador', 'paciente'])): ?>
                    <a href="<?php echo App\Helpers\UrlHelper::url('api/clinical-history/export-pdf'); ?>?id=<?php echo $paciente_id; ?>" 
                       target="_blank"
                       class="flex items-center px-4 py-2 bg-[#28A745] rounded-xl text-xs font-bold text-white hover:bg-green-700 transition-all shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Descargar Historial (PDF)
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($historial)): ?>
                <div class="text-center py-20">
                    <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-[#6C757D]">Aún no existen atenciones médicas registradas.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($historial as $h): ?>
                        <div class="p-6 border rounded-2xl hover:border-[#007BFF] transition-all bg-gray-50/30">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-sm font-bold text-[#007BFF] block">
                                        <?php
                                        $timestamp = strtotime($h['fecha']);
                                        $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
                                        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                                        echo $dias[date('w', $timestamp)] . ", " . date('d', $timestamp) . " de " . $meses[date('n', $timestamp) - 1] . " de " . date('Y', $timestamp);
                                        ?>
                                    </span>
                                    <p class="text-sm font-medium text-gray-600 mt-1">
                                        Atención por Dr.
                                        <?php echo htmlspecialchars($h['medico_nombre'] . ' ' . $h['medico_apellido']); ?>
                                    </p>
                                </div>
                                <?php
                                $id_label = "patient_id=" . $paciente_id;
                                $isOwner = (isset($_SESSION['medico_id']) && $h['medico_id'] == $_SESSION['medico_id']);
                                $isAdmin = ($_SESSION['user_role'] === 'administrador');

                                $canComplete = (($isOwner || $isAdmin) &&
                                    strpos($h['diagnostico'], 'Pendiente') !== false &&
                                    $h['ordenes_pendientes'] == 0);

                                if ($canComplete): ?>
                                    <a href="<?php echo App\Helpers\UrlHelper::url('appointments_attend'); ?>?id=<?php echo $h['cita_id']; ?>&from=history&<?php echo $id_label; ?>"
                                        class="bg-[#EAB308] text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-yellow-600 transition shadow-sm">
                                        Completar Diagnóstico
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white p-4 rounded-xl border border-gray-100">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Diagnóstico</p>
                                    <p class="text-sm text-[#212529] leading-relaxed">
                                        <?php echo htmlspecialchars($h['diagnostico']); ?>
                                    </p>
                                </div>
                                <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                    <p class="text-xs font-bold text-[#007BFF] uppercase tracking-wider mb-2">Tratamiento
                                        Sugerido</p>
                                    <p class="text-sm text-[#007BFF] leading-relaxed font-medium">
                                        <?php echo htmlspecialchars($h['tratamiento']); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($h['observaciones'])): ?>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <p class="text-xs font-semibold text-gray-500 italic">
                                        Nota adicional: <?php echo htmlspecialchars($h['observaciones']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
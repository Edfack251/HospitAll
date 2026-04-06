<?php
use App\Helpers\UrlHelper;

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medico') {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Dashboard Médico - HospitAll';
$activePage = 'doctor_dashboard';
$headerTitle = 'Panel Médico';
$headerSubtitle = 'Resumen de tu actividad clínica y pacientes.';
include __DIR__ . '/pages/../layout/header.php';
?>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Error:</strong>
        <span class="block sm:inline">
            <?php echo htmlspecialchars($error); ?>
        </span>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
    <!-- Citas de hoy -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-blue-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Citas para Hoy</p>
        <p class="text-4xl font-extrabold text-[#007BFF] mt-2">
            <?php echo count($citas_hoy); ?>
        </p>
    </div>
    <!-- En espera -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-yellow-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">En Espera / Consulta</p>
        <p class="text-4xl font-extrabold text-orange-500 mt-2">
            <?php echo count($pacientes_espera); ?>
        </p>
    </div>
    <!-- Labs Pendientes -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-purple-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Labs Pendientes</p>
        <p class="text-4xl font-extrabold text-purple-600 mt-2">
            <?php echo count($resultados_pendientes); ?>
        </p>
    </div>
    <!-- Prescripciones Activas -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-green-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Prescripciones</p>
        <p class="text-4xl font-extrabold text-[#28A745] mt-2">
            <?php echo count($prescripciones_activas); ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">

    <!-- Pacientes en Espera -->
    <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Pacientes en Espera / En Consulta
        </h3>

        <?php if (empty($pacientes_espera)): ?>
            <p class="text-gray-500 text-center py-4">No hay pacientes en espera en este momento.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($pacientes_espera as $cita): ?>
                    <li class="py-4 flex justify-between items-center">
                        <div>
                            <p class="font-bold text-gray-800">
                                <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                            </p>
                            <p class="text-xs text-gray-500">Hora:
                                <?php echo date('H:i', strtotime($cita['hora'])); ?>
                            </p>
                        </div>
                        <div>
                            <span
                                class="px-3 py-1 text-xs font-bold rounded-full 
                                <?php echo $cita['estado_clinico'] === 'en_consulta' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'; ?>">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $cita['estado_clinico'])); ?>
                            </span>
                            <a href="<?php echo UrlHelper::url('appointments_attend'); ?>?id=<?php echo $cita['id']; ?>"
                                class="ml-2 text-blue-600 hover:text-blue-800 font-bold text-sm">Atender</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Citas de Hoy -->
    <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Agenda del Día
        </h3>

        <?php if (empty($citas_hoy)): ?>
            <p class="text-gray-500 text-center py-4">No tienes citas programadas para hoy.</p>
        <?php else: ?>
            <div class="overflow-y-auto max-h-80 pr-2">
                <ul class="divide-y divide-gray-100">
                    <?php foreach ($citas_hoy as $cita): ?>
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="font-bold text-gray-700">
                                    <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                                </p>
                                <p class="text-sm font-semibold text-blue-600">
                                    <?php echo date('H:i', strtotime($cita['hora'])); ?>
                                </p>
                            </div>
                            <div>
                                <span class="px-2 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-600">
                                    <?php echo htmlspecialchars($cita['estado']); ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">

    <!-- Laboratorio -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                </path>
            </svg>
            Labs Pendientes
        </h3>
        <?php if (empty($resultados_pendientes)): ?>
            <p class="text-sm text-gray-500">Sin resultados pendientes.</p>
        <?php else: ?>
            <ul class="text-sm divide-y">
                <?php foreach ($resultados_pendientes as $lab): ?>
                    <li class="py-2">
                        <p class="font-bold">
                            <?php echo htmlspecialchars($lab['paciente_nombre'] . ' ' . $lab['paciente_apellido']); ?>
                        </p>
                        <p class="text-gray-500 text-xs mt-1">Sol:
                            <?php echo date('d/m/Y', strtotime($lab['created_at'])); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Prescripciones -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Prescripciones Activas
        </h3>
        <?php if (empty($prescripciones_activas)): ?>
            <p class="text-sm text-gray-500">Sin prescripciones activas.</p>
        <?php else: ?>
            <ul class="text-sm divide-y">
                <?php foreach ($prescripciones_activas as $presc): ?>
                    <li class="py-2">
                        <p class="font-bold">
                            <?php echo htmlspecialchars($presc['paciente_nombre'] . ' ' . $presc['paciente_apellido']); ?>
                        </p>
                        <p class="text-gray-500 text-xs mt-1">Cita:
                            <?php echo date('d/m/Y', strtotime($presc['fecha_cita'])); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Consultas Recientes -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Consultas Recientes
        </h3>
        <?php if (empty($consultas_recientes)): ?>
            <p class="text-sm text-gray-500">Aún no hay consultas recientes.</p>
        <?php else: ?>
            <ul class="text-sm divide-y">
                <?php foreach ($consultas_recientes as $consulta): ?>
                    <li class="py-2">
                        <p class="font-bold">
                            <?php echo htmlspecialchars($consulta['paciente_nombre'] . ' ' . $consulta['paciente_apellido']); ?>
                        </p>
                        <p class="text-gray-500 text-xs mt-1">Registrado el:
                            <?php echo date('d/m/Y H:i', strtotime($consulta['created_at'])); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/pages/../layout/footer.php'; ?>
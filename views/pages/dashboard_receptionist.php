<?php
use App\Controllers\DashboardController;
use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;
use App\Helpers\PrivacyHelper;

AuthHelper::checkRole(['recepcionista', 'administrador']);

$pdo = $pdo ?? $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    throw new \RuntimeException('No se pudo conectar a la base de datos.');
}

$controller = new DashboardController($pdo);
$data = $controller->getRecepcionistaData();

$citasHoy = $data['citasHoy'] ?? [];
$pacientesEnEspera = $data['pacientesEnEspera'] ?? [];
$proximasCitas = $data['proximasCitas'] ?? [];
$pacientesAtendidos = $data['pacientesAtendidos'] ?? [];

$pageTitle = 'Dashboard Recepcionista - HospitAll';
$activePage = 'dashboard_receptionist';
$headerTitle = 'Panel de Recepción';
$headerSubtitle = 'Citas del día y gestión de pacientes.';

include __DIR__ . '/../layout/header.php';
?>

<?php
function estadoBadgeClass($estado) {
    $statusClass = 'bg-gray-100 text-gray-600';
    switch ($estado) {
        case 'Programada': return 'bg-blue-100 text-blue-600';
        case 'Confirmada': return 'bg-indigo-100 text-indigo-600';
        case 'En espera': return 'bg-yellow-100 text-yellow-600';
        case 'Atendida': return 'bg-green-100 text-green-600';
        case 'Cancelada': return 'bg-red-100 text-red-600';
        case 'No asistió': return 'bg-orange-100 text-orange-600';
    }
    return $statusClass;
}
?>

<!-- Botones de acción rápida -->
<div class="flex flex-wrap gap-4 mb-8">
    <a href="<?php echo UrlHelper::url('patients_create'); ?>"
        class="bg-[#007BFF] text-white px-6 py-3 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
        Registrar paciente
    </a>
    <a href="<?php echo UrlHelper::url('appointments_schedule'); ?>"
        class="bg-[#28A745] text-white px-6 py-3 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        Crear cita
    </a>
    <a href="<?php echo UrlHelper::url('appointments'); ?>"
        class="bg-[#6C757D] text-white px-6 py-3 rounded-lg font-semibold shadow-md hover:bg-gray-600 transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Gestión de citas
    </a>
</div>



<!-- Citas del día -->
<div class="glass-card p-10 rounded-2xl shadow-sm border border-gray-100 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold gradient-text">Citas del día</h3>
        <a href="<?php echo UrlHelper::url('appointments'); ?>" class="text-[#007BFF] text-sm font-bold hover:underline bg-blue-50 px-4 py-2 rounded-full transition">Ver todas las citas</a>
    </div>
    <div class="overflow-x-auto">
        <table id="citasHoyTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] border-b">
                    <th class="py-4">Paciente</th>
                    <th class="py-4">Médico</th>
                    <th class="py-4">Hora</th>
                    <th class="py-4">Estado</th>
                    <th class="py-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($citasHoy as $cita): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4">
                        <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . ($cita['paciente_id'] ?? $cita['paciente_id_real'] ?? ''); ?>" class="font-medium text-[#007BFF] hover:underline">
                            <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                        </a>
                        <div class="text-xs text-[#6C757D]">
                            <?php echo htmlspecialchars(PrivacyHelper::maskCedula($cita['paciente_identificacion'] ?? '', $cita['paciente_id'] ?? null)); ?>
                        </div>
                    </td>
                    <td class="py-4 text-[#495057]">
                        Dr. <?php echo htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']); ?>
                    </td>
                    <td class="py-4 text-[#495057]">
                        <?php echo date('H:i', strtotime($cita['hora'])); ?>
                    </td>
                    <td class="py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo estadoBadgeClass($cita['estado'] ?? 'Programada'); ?>">
                            <?php echo htmlspecialchars($cita['estado'] ?? 'Programada'); ?>
                        </span>
                    </td>
                    <td class="py-4">
                        <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . ($cita['paciente_id'] ?? $cita['paciente_id_real'] ?? ''); ?>" class="text-[#007BFF] hover:underline font-medium text-sm">Ver paciente</a>
                        <?php if (in_array($cita['estado'] ?? '', ['Programada', 'Confirmada'])): ?>
                        <a href="<?php echo UrlHelper::url('appointments_reprogram') . '?id=' . $cita['id']; ?>" class="ml-2 text-[#6C757D] hover:underline font-medium text-sm">Reprogramar</a>
                        <?php endif; ?>
                        <?php if (in_array($cita['estado'] ?? '', ['Programada', 'Confirmada', 'En espera'])): ?>
                        <a href="<?php echo UrlHelper::url('appointments_attend') . '?id=' . $cita['id']; ?>" class="ml-2 text-[#28A745] hover:underline font-medium text-sm">Atender</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Pacientes en espera -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-yellow-50">
        <h4 class="text-lg font-bold text-[#212529] mb-4">Pacientes en espera</h4>
        <?php if (empty($pacientesEnEspera)): ?>
        <p class="text-[#6C757D] text-sm">No hay pacientes en espera.</p>
        <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($pacientesEnEspera as $cita): ?>
            <li class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . ($cita['paciente_id'] ?? ''); ?>" class="text-[#007BFF] hover:underline font-medium text-sm">
                    <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                </a>
                <a href="<?php echo UrlHelper::url('appointments_attend') . '?id=' . $cita['id']; ?>" class="text-xs bg-[#28A745] text-white px-2 py-1 rounded hover:bg-green-700">Atender</a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Próximas citas -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-blue-50">
        <h4 class="text-lg font-bold text-[#212529] mb-4">Próximas citas</h4>
        <?php if (empty($proximasCitas)): ?>
        <p class="text-[#6C757D] text-sm">No hay citas próximas.</p>
        <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($proximasCitas as $cita):
                $esHoy = ($cita['fecha'] ?? '') === date('Y-m-d');
            ?>
            <li class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <div>
                    <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . ($cita['paciente_id'] ?? ''); ?>" class="text-[#007BFF] hover:underline font-medium text-sm">
                        <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                    </a>
                    <span class="text-xs text-[#6C757D] block"><?php echo $esHoy ? date('H:i', strtotime($cita['hora'])) : date('d/m', strtotime($cita['fecha'])) . ' ' . date('H:i', strtotime($cita['hora'])); ?></span>
                </div>
                <?php if ($esHoy): ?>
                <a href="<?php echo UrlHelper::url('appointments_attend') . '?id=' . $cita['id']; ?>" class="text-xs text-[#007BFF] hover:underline">Atender</a>
                <?php else: ?>
                <a href="<?php echo UrlHelper::url('appointments_reprogram') . '?id=' . $cita['id']; ?>" class="text-xs text-[#6C757D] hover:underline">Reprogramar</a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Pacientes atendidos -->
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-green-50">
        <h4 class="text-lg font-bold text-[#212529] mb-4">Pacientes atendidos</h4>
        <?php if (empty($pacientesAtendidos)): ?>
        <p class="text-[#6C757D] text-sm">No hay pacientes atendidos hoy.</p>
        <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($pacientesAtendidos as $cita): ?>
            <li class="py-2 border-b border-gray-100 last:border-0">
                <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . ($cita['paciente_id'] ?? ''); ?>" class="text-[#007BFF] hover:underline font-medium text-sm">
                    <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                </a>
                <span class="text-xs text-[#6C757D] block"><?php echo date('H:i', strtotime($cita['hora'])); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($('#citasHoyTable').length && typeof initDataTable === 'function') {
        initDataTable('#citasHoyTable', {
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>',
            paging: true,
            pageLength: 10
        });
    }
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

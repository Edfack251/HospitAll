<?php
use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista']);

$controller = new AppointmentsController($pdo);
$citas = $controller->index();

// Categorizar citas
$citasActivas = [];
$citasHistorial = [];

foreach ($citas as $cita) {
    if (in_array($cita['estado'], ['Atendida', 'Cancelada', 'No asistió'])) {
        $citasHistorial[] = $cita;
    } else {
        $citasActivas[] = $cita;
    }
}

$pageTitle = 'Citas Médicas - HospitAll';
$activePage = 'citas';
$headerTitle = 'Gestión de Citas';
$headerSubtitle = 'Control y seguimiento de citas médicas.';

include __DIR__ . '/../layout/header.php';
?>

<!-- Tabs Navigation -->
<div class="flex space-x-4 mb-8 border-b border-gray-100">
    <button onclick="switchTab('activas')" id="tab-activas" class="px-6 py-3 font-bold text-sm uppercase tracking-widest border-b-2 border-blue-600 text-blue-600 transition-all">
        Citas Activas
    </button>
    <button onclick="switchTab('historial')" id="tab-historial" class="px-6 py-3 font-bold text-sm uppercase tracking-widest border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all">
        Historial
    </button>
</div>

<div class="flex justify-between items-center mb-6">
    <h3 id="tableTitle" class="text-xl font-bold text-[#212529]">Listado de Citas Activas</h3>
    <a href="<?php echo \App\Helpers\UrlHelper::url('appointments_schedule'); ?>"
        class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
        + Agendar Cita
    </a>
</div>

<!-- Tab Activas -->
<div id="content-activas" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="citasActivasTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Paciente</th>
                <th class="py-4">Médico</th>
                <th class="py-4">Fecha y Hora</th>
                <th class="py-4">Estado</th>
                <th class="py-4">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citasActivas as $cita): ?>
                <?php $estado = $cita['estado'] ?: 'Programada'; ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4">
                        <div class="font-medium"><?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?></div>
                        <div class="text-xs text-[#6C757D]">Cédula: <?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($cita['paciente_identificacion'] ?? '', $cita['paciente_id_real'] ?? null)); ?></div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm font-semibold"><?php echo htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']); ?></div>
                        <div class="text-xs text-[#6C757D]"><?php echo htmlspecialchars($cita['especialidad']); ?></div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm"><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></div>
                        <div class="text-xs text-[#6C757D]"><?php echo date('H:i', strtotime($cita['hora'])); ?></div>
                    </td>
                    <td class="py-4">
                        <?php
                        $statusClass = 'bg-gray-100 text-gray-600';
                        if ($estado === 'Programada') $statusClass = 'bg-blue-100 text-blue-600';
                        elseif ($estado === 'Confirmada') $statusClass = 'bg-indigo-100 text-indigo-600';
                        elseif ($estado === 'En espera') $statusClass = 'bg-yellow-100 text-yellow-600';
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>"><?php echo $estado; ?></span>
                    </td>
                    <td class="py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($estado === 'En espera'): ?>
                                <a href="<?php echo \App\Helpers\UrlHelper::url('appointments_attend'); ?>?id=<?php echo $cita['id']; ?>"
                                    class="inline-flex items-center gap-1 text-xs bg-[#28A745] text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition-colors font-medium shadow-sm">
                                    Atender
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($estado, ['Programada', 'Confirmada']) && in_array($_SESSION['user_role'], ['administrador', 'recepcionista'])): ?>
                                <a href="<?php echo \App\Helpers\UrlHelper::url('appointments_reprogram'); ?>?id=<?php echo $cita['id']; ?>"
                                    class="inline-flex items-center gap-1 text-xs bg-[#007BFF] text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-sm">
                                    Reprogramar
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($_SESSION['user_role'], ['administrador', 'recepcionista'])): ?>
                                <?php if ($estado === 'Programada'): ?>
                                    <form action="<?php echo \App\Helpers\UrlHelper::url('appointments_status_update'); ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo CsrfHelper::generateToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="Confirmada">
                                        <button type="submit" class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-200 transition-colors border border-indigo-200">Confirmar</button>
                                    </form>
                                    <form action="<?php echo \App\Helpers\UrlHelper::url('appointments_status_update'); ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo CsrfHelper::generateToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="Cancelada">
                                        <button type="submit" class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors border border-red-200" onclick="return confirm('¿Cancelar esta cita?');">Cancelar</button>
                                    </form>
                                <?php elseif ($estado === 'Confirmada'): ?>
                                    <form action="<?php echo \App\Helpers\UrlHelper::url('appointments_status_update'); ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo CsrfHelper::generateToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="En espera">
                                        <button type="submit" class="text-xs bg-amber-100 text-amber-700 px-3 py-1.5 rounded-lg hover:bg-amber-200 transition-colors border border-amber-200">Llegada</button>
                                    </form>
                                <?php elseif ($estado === 'En espera'): ?>
                                    <form action="<?php echo \App\Helpers\UrlHelper::url('appointments_status_update'); ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo CsrfHelper::generateToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="No asistió">
                                        <button type="submit" class="text-xs bg-orange-100 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-200 transition-colors border border-orange-200" onclick="return confirm('¿Marcar como no asistió?');">No asistió</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Tab Historial -->
<div id="content-historial" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="citasHistorialTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Paciente</th>
                <th class="py-4">Médico</th>
                <th class="py-4">Fecha y Hora</th>
                <th class="py-4">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citasHistorial as $cita): ?>
                <?php $estado = $cita['estado']; ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4">
                        <div class="font-medium"><?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?></div>
                    </td>
                    <td class="py-4 font-semibold text-sm">Dr. <?php echo htmlspecialchars($cita['medico_apellido']); ?></td>
                    <td class="py-4 text-xs"><?php echo date('d/m/Y H:i', strtotime($cita['fecha'] . ' ' . $cita['hora'])); ?></td>
                    <td class="py-4">
                        <?php
                        $statusClass = 'bg-gray-100 text-gray-600';
                        if ($estado === 'Atendida') $statusClass = 'bg-green-100 text-green-600';
                        elseif ($estado === 'Cancelada') $statusClass = 'bg-red-100 text-red-600';
                        elseif ($estado === 'No asistió') $statusClass = 'bg-orange-100 text-orange-600';
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>"><?php echo $estado; ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        const tableOptions = {
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        };
        $('#citasActivasTable').DataTable(tableOptions);
        $('#citasHistorialTable').DataTable(tableOptions);
    });

    function switchTab(tab) {
        // Contenidos
        document.getElementById('content-activas').classList.toggle('hidden', tab !== 'activas');
        document.getElementById('content-historial').classList.toggle('hidden', tab !== 'historial');
        
        // Botones
        document.getElementById('tab-activas').className = (tab === 'activas') ? 
            'px-6 py-3 font-bold text-sm uppercase tracking-widest border-b-2 border-blue-600 text-blue-600 transition-all' : 
            'px-6 py-3 font-bold text-sm uppercase tracking-widest border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all';
        
        document.getElementById('tab-historial').className = (tab === 'historial') ? 
            'px-6 py-3 font-bold text-sm uppercase tracking-widest border-b-2 border-blue-600 text-blue-600 transition-all' : 
            'px-6 py-3 font-bold text-sm uppercase tracking-widest border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all';

        // Título
        document.getElementById('tableTitle').textContent = (tab === 'activas') ? 'Listado de Citas Activas' : 'Historial de Citas';
    }
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>

<?php include __DIR__ . '/../layout/footer.php'; ?>
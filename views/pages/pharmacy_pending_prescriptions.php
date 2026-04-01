<?php
use App\Helpers\UrlHelper;
use App\Controllers\PharmacyController;
use App\Helpers\AuthHelper;
use App\Helpers\PrivacyHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['farmaceutico', 'administrador']);

$controller = new PharmacyController($pdo);
// Obtener todas las pendientes sin límite para DataTables
$prescripciones = $controller->getPendingPrescriptions(1000, 0);

$pageTitle = 'Recetas Pendientes - HospitAll';
$activePage = 'pharmacy_pending_prescriptions';
$headerTitle = 'Prescripciones Médicas Pendientes';
$headerSubtitle = 'Listado de todas las recetas emitidas por médicos que aún no han sido dispensadas.';
$csrfToken = CsrfHelper::generateToken();

$walkin_id = isset($_GET['walkin_id']) ? (int)$_GET['walkin_id'] : null;
$walkin_paciente = null;
if ($walkin_id) {
    try {
        $visitaRepo = new \App\Repositories\VisitaWalkinRepository($pdo);
        $walkin_paciente = $visitaRepo->getById($walkin_id);
    } catch (\Throwable $e) {}
}

$existe_orden_walkin = false;
if ($walkin_paciente) {
    foreach ($prescripciones as $p) {
        if ($p['paciente_id'] == $walkin_paciente['paciente_id']) {
            $existe_orden_walkin = true;
            break;
        }
    }
}

include __DIR__ . '/../layout/header.php';
?>
<?php if ($walkin_paciente): ?>
<div class="mb-8 p-4 rounded-xl shadow-sm border border-l-4 border-l-orange-500 bg-orange-50 text-orange-800 flex items-center justify-between">
    <div>
        <h4 class="font-bold text-lg flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Paciente Walk-in Llamado: <?php echo htmlspecialchars($walkin_paciente['paciente_nombre'] . ' ' . $walkin_paciente['paciente_apellido']); ?>
        </h4>
        <p class="text-sm opacity-90 mt-1">
            <?php if ($existe_orden_walkin): ?>
                Se ha encontrado una prescripción pendiente en la tabla de abajo. Por favor proceda a dispensar.
            <?php else: ?>
                <strong>Este paciente no tiene prescripciones pendientes.</strong> Si requiere medicamentos, el médico debe generar una prescripción primero.
            <?php endif; ?>
        </p>
    </div>
    <button type="button" onclick="marcarTurnoAtendido(<?php echo $walkin_paciente['turno_id']; ?>)" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition-colors">
        Marcar Turno Atendido
    </button>
</div>
<?php endif; ?>

<div class="glass-card p-8 rounded-2xl shadow-sm border border-blue-50">
    <div class="overflow-x-auto">
        <table id="pendingPrescriptionsTable" class="w-full text-left display">
            <thead>
                <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b">
                    <th class="px-4 py-4">ID</th>
                    <th class="px-4 py-4">Fecha / Hora</th>
                    <th class="px-4 py-4">Paciente</th>
                    <th class="px-4 py-4">Médico</th>
                    <th class="px-4 py-4">Medicamentos Prescritos</th>
                    <th class="px-4 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($prescripciones as $p): ?>
                    <?php 
                    $isWalkinTarget = ($walkin_paciente && $p['paciente_id'] == $walkin_paciente['paciente_id']);
                    $rowClass = $isWalkinTarget ? "bg-amber-50 ring-2 ring-amber-500 transition-colors" : "hover:bg-gray-50 transition-colors";
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td class="px-4 py-4 font-bold text-gray-400">#<?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="px-4 py-4 text-sm text-gray-600">
                            <?php echo date('d/m/Y H:i', strtotime($p['fecha_prescripcion'])); ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars(($p['paciente_nombre'] ?? '') . ' ' . ($p['paciente_apellido'] ?? '')); ?></span>
                                <span class="text-[10px] text-gray-400 font-medium"><?php echo htmlspecialchars(PrivacyHelper::maskCedula($p['paciente_identificacion'] ?? '')); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-600 font-medium">
                            Dr. <?php echo htmlspecialchars(($p['medico_nombre'] ?? '') . ' ' . ($p['medico_apellido'] ?? '')); ?>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-blue-600 font-semibold block max-w-xs truncate" title="<?php echo htmlspecialchars($p['medicamentos_summary'] ?? ''); ?>">
                                <?php echo htmlspecialchars($p['medicamentos_summary'] ?? ''); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <a href="<?php echo UrlHelper::url('pharmacy_prescriptions'); ?>?id=<?php echo $p['id']; ?>" 
                               class="bg-blue-600 text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-blue-700 transition shadow-sm inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                Dispensar Receta
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#pendingPrescriptionsTable', {
        order: [[1, 'desc']],
        pageLength: 25
    });
});

function marcarTurnoAtendido(turnoId) {
    if (!confirm('¿Está seguro de marcar este turno como atendido?')) return;
    
    fetch('<?php echo UrlHelper::url('api/turnos/atendido'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?php echo $csrfToken; ?>'
        },
        body: JSON.stringify({ turno_id: turnoId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            showToast(data.message || 'Error al completar el turno.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Error de conexión al servidor.', 'error');
    });
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

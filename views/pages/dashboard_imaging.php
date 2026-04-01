<?php
use App\Controllers\DashboardController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['tecnico_imagenes', 'administrador']);

$pdo = $pdo ?? $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    throw new \RuntimeException('No se pudo conectar a la base de datos.');
}

$controller = new DashboardController($pdo);
$data = $controller->getTecnicoImagenesData();

$ordenesPendientes = $data['ordenesPendientes'] ?? [];
$ordenesEnProceso = $data['ordenesEnProceso'] ?? [];
$ordenesCompletadasHoy = $data['ordenesCompletadasHoy'] ?? [];
$turnoActual = $data['turnoActual'] ?? null;
$turnosEsperandoCount = $data['turnosEsperandoCount'] ?? 0;

$pageTitle = 'Dashboard Imágenes - HospitAll';
$activePage = 'dashboard_imaging';
$headerTitle = 'Panel de Imágenes Médicas';
$headerSubtitle = 'Estudios pendientes, en proceso y completados hoy.';
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
    foreach ($ordenesPendientes as $o) {
        if ($o['paciente_id'] == $walkin_paciente['paciente_id']) {
            $existe_orden_walkin = true;
            break;
        }
    }
}

include __DIR__ . '/../layout/header.php';
?>

<?php
function estadoBadgeClassImg($estado) {
    switch ($estado) {
        case 'Pendiente': return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'En proceso': return 'bg-blue-50 text-blue-700 border border-blue-200';
        case 'Completada': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    }
    return 'bg-gray-100 text-gray-600';
}
?>



<!-- Tarjetas de resumen -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="#section-pendientes" class="glass-card p-6 rounded-2xl border-l-4 border-l-amber-500 hover:shadow-lg hover:border-amber-400 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-[#6C757D] uppercase tracking-wide">Pendientes</p>
                <p class="text-4xl font-extrabold text-amber-600 mt-1"><?php echo count($ordenesPendientes); ?></p>
            </div>
            <div class="w-14 h-14 rounded-xl bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </a>
    <a href="#section-enproceso" class="glass-card p-6 rounded-2xl border-l-4 border-l-[#007BFF] hover:shadow-lg hover:border-blue-600 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-[#6C757D] uppercase tracking-wide">En proceso</p>
                <p class="text-4xl font-extrabold text-[#007BFF] mt-1"><?php echo count($ordenesEnProceso); ?></p>
            </div>
            <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                <svg class="w-7 h-7 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
        </div>
    </a>
    <a href="#section-completados" class="glass-card p-6 rounded-2xl border-l-4 border-l-[#28A745] hover:shadow-lg hover:border-emerald-500 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-[#6C757D] uppercase tracking-wide">Completados hoy</p>
                <p class="text-4xl font-extrabold text-[#28A745] mt-1"><?php echo count($ordenesCompletadasHoy); ?></p>
            </div>
            <div class="w-14 h-14 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                <svg class="w-7 h-7 text-[#28A745]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </a>
</div>

<?php if ($walkin_paciente): ?>
<div class="mb-8 p-4 rounded-xl shadow-sm border border-l-4 border-l-orange-500 bg-orange-50 text-orange-800 flex items-center justify-between">
    <div>
        <h4 class="font-bold text-lg flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Paciente Walk-in Llamado: <?php echo htmlspecialchars($walkin_paciente['paciente_nombre'] . ' ' . $walkin_paciente['paciente_apellido']); ?>
        </h4>
        <p class="text-sm opacity-90 mt-1">
            <?php if ($existe_orden_walkin): ?>
                Se ha encontrado una orden de imagenología pendiente en la tabla de abajo. Por favor inicie el procesamiento.
            <?php else: ?>
                <strong>Paciente walk-in sin orden médica.</strong> Crear orden manual o proceder al despacho de exámenes.
            <?php endif; ?>
        </p>
    </div>
    <button type="button" onclick="marcarTurnoAtendido(<?php echo $walkin_paciente['turno_id']; ?>)" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition-colors">
        Marcar Turno Atendido
    </button>
</div>
<?php endif; ?>

<!-- Estudios pendientes -->
<div id="section-pendientes" class="glass-card rounded-2xl shadow-sm border border-amber-50/80 mb-8 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50/50 to-transparent">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-[#212529]">Estudios pendientes</h3>
                <p class="text-sm text-[#6C757D]">Órdenes que requieren inicio de proceso</p>
            </div>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <?php if (empty($ordenesPendientes)): ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-[#6C757D] font-medium">No hay estudios pendientes</p>
            <p class="text-sm text-[#6C757D] mt-1">Las nuevas órdenes aparecerán aquí</p>
        </div>
        <?php else: ?>
        <table id="pendientesTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] text-sm font-semibold uppercase tracking-wider border-b-2 border-gray-100">
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Médico solicitante</th>
                    <th class="py-4 px-2">Tipo de estudio</th>
                    <th class="py-4 px-2">Estado</th>
                    <th class="py-4 px-2">Fecha</th>
                    <th class="py-4 px-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordenesPendientes as $o): ?>
                <?php 
                $isWalkinTarget = ($walkin_paciente && $o['paciente_id'] == $walkin_paciente['paciente_id']);
                $rowClass = $isWalkinTarget ? "border-b border-amber-200 bg-amber-50 ring-2 ring-amber-500 transition-colors" : "border-b border-gray-50 hover:bg-amber-50/30 transition-colors";
                ?>
                <tr class="<?php echo $rowClass; ?>" data-orden-id="<?php echo (int)$o['id']; ?>">
                    <td class="py-4 px-2">
                        <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . (int)($o['paciente_id'] ?? 0); ?>" class="font-semibold text-[#212529] hover:text-[#007BFF] transition-colors">
                            <?php echo htmlspecialchars(trim(($o['paciente_nombre'] ?? '') . ' ' . ($o['paciente_apellido'] ?? ''))); ?>
                        </a>
                    </td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php $medico = trim(($o['medico_nombre'] ?? '') . ' ' . ($o['medico_apellido'] ?? '')); echo $medico ? 'Dr. ' . htmlspecialchars($medico) : '-'; ?></td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php echo htmlspecialchars($o['estudios'] ?? $o['tipo_estudio'] ?? '-'); ?></td>
                    <td class="py-4 px-2">
                        <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold <?php echo estadoBadgeClassImg($o['estado'] ?? 'Pendiente'); ?>">
                            <?php echo htmlspecialchars($o['estado'] ?? 'Pendiente'); ?>
                        </span>
                    </td>
                    <td class="py-4 px-2 text-[#6C757D] text-sm"><?php echo $o['created_at'] ? date('d/m/Y H:i', strtotime($o['created_at'])) : '-'; ?></td>
                    <td class="py-4 px-2 text-right">
                        <button type="button" onclick="cambiarEstado(<?php echo (int)$o['id']; ?>, 'En proceso')" class="btn-primary-gradient text-white px-4 py-2 rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Iniciar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- En proceso -->
<div id="section-enproceso" class="glass-card rounded-2xl shadow-sm border border-blue-50/80 mb-8 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-blue-50/50 to-transparent">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-[#212529]">En proceso</h3>
                <p class="text-sm text-[#6C757D]">Órdenes en preparación de resultados</p>
            </div>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <?php if (empty($ordenesEnProceso)): ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
            <p class="text-[#6C757D] font-medium">No hay estudios en proceso</p>
            <p class="text-sm text-[#6C757D] mt-1">Inicia órdenes pendientes para verlas aquí</p>
        </div>
        <?php else: ?>
        <table id="enProcesoTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] text-sm font-semibold uppercase tracking-wider border-b-2 border-gray-100">
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Médico solicitante</th>
                    <th class="py-4 px-2">Tipo de estudio</th>
                    <th class="py-4 px-2">Estado</th>
                    <th class="py-4 px-2">Fecha</th>
                    <th class="py-4 px-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordenesEnProceso as $o): ?>
                <tr class="border-b border-gray-50 hover:bg-blue-50/30 transition-colors" data-orden-id="<?php echo (int)$o['id']; ?>">
                    <td class="py-4 px-2">
                        <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . (int)($o['paciente_id'] ?? 0); ?>" class="font-semibold text-[#212529] hover:text-[#007BFF] transition-colors">
                            <?php echo htmlspecialchars(trim(($o['paciente_nombre'] ?? '') . ' ' . ($o['paciente_apellido'] ?? ''))); ?>
                        </a>
                    </td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php $medico = trim(($o['medico_nombre'] ?? '') . ' ' . ($o['medico_apellido'] ?? '')); echo $medico ? 'Dr. ' . htmlspecialchars($medico) : '-'; ?></td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php echo htmlspecialchars($o['estudios'] ?? $o['tipo_estudio'] ?? '-'); ?></td>
                    <td class="py-4 px-2">
                        <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold <?php echo estadoBadgeClassImg($o['estado'] ?? 'En proceso'); ?>">
                            <?php echo htmlspecialchars($o['estado'] ?? 'En proceso'); ?>
                        </span>
                    </td>
                    <td class="py-4 px-2 text-[#6C757D] text-sm"><?php echo $o['created_at'] ? date('d/m/Y H:i', strtotime($o['created_at'])) : '-'; ?></td>
                    <td class="py-4 px-2 text-right">
                        <div class="flex flex-wrap gap-2 justify-end">
                            <button type="button" onclick="openUploadModal(<?php echo (int)$o['id']; ?>, '<?php echo addslashes(htmlspecialchars(trim(($o['paciente_nombre'] ?? '') . ' ' . ($o['paciente_apellido'] ?? '')))); ?>')" class="btn-success-gradient text-white px-4 py-2 rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Subir archivo
                            </button>
                            <button type="button" onclick="cambiarEstado(<?php echo (int)$o['id']; ?>, 'Completada')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 text-[#6C757D] hover:border-[#28A745] hover:text-[#28A745] transition-colors" title="Marcar como completada sin subir archivo">
                                Completar
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Completados hoy -->
<div id="section-completados" class="glass-card rounded-2xl shadow-sm border border-emerald-50/80 mb-8 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-emerald-50/50 to-transparent">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-[#28A745]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-[#212529]">Completados hoy</h3>
                    <p class="text-sm text-[#6C757D]">Órdenes finalizadas en la fecha</p>
                </div>
                <a href="<?php echo UrlHelper::url('imaging'); ?>" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline">Ir al registro completo</a>
            </div>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <?php if (empty($ordenesCompletadasHoy)): ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-[#28A745]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-[#6C757D] font-medium">No hay estudios completados hoy</p>
            <p class="text-sm text-[#6C757D] mt-1">Los resultados finalizados aparecerán aquí</p>
        </div>
        <?php else: ?>
        <table id="completadosTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] text-sm font-semibold uppercase tracking-wider border-b-2 border-gray-100">
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Médico solicitante</th>
                    <th class="py-4 px-2">Tipo de estudio</th>
                    <th class="py-4 px-2">Estado</th>
                    <th class="py-4 px-2">Fecha resultado</th>
                    <th class="py-4 px-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordenesCompletadasHoy as $o): ?>
                <tr class="border-b border-gray-50 hover:bg-emerald-50/30 transition-colors">
                    <td class="py-4 px-2">
                        <a href="<?php echo UrlHelper::url('patients_edit') . '?id=' . (int)($o['paciente_id'] ?? 0); ?>" class="font-semibold text-[#212529] hover:text-[#007BFF] transition-colors">
                            <?php echo htmlspecialchars(trim(($o['paciente_nombre'] ?? '') . ' ' . ($o['paciente_apellido'] ?? ''))); ?>
                        </a>
                    </td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php $medico = trim(($o['medico_nombre'] ?? '') . ' ' . ($o['medico_apellido'] ?? '')); echo $medico ? 'Dr. ' . htmlspecialchars($medico) : '-'; ?></td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php echo htmlspecialchars($o['estudios'] ?? $o['tipo_estudio'] ?? '-'); ?></td>
                    <td class="py-4 px-2">
                        <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold <?php echo estadoBadgeClassImg($o['estado'] ?? 'Completada'); ?>">
                            <?php echo htmlspecialchars($o['estado'] ?? 'Completada'); ?>
                        </span>
                    </td>
                    <td class="py-4 px-2 text-[#6C757D] text-sm"><?php echo $o['fecha_resultado'] ? date('d/m/Y H:i', strtotime($o['fecha_resultado'])) : '-'; ?></td>
                    <td class="py-4 px-2 text-right">
                        <button type="button" onclick="openUploadModal(<?php echo (int)$o['id']; ?>, '<?php echo addslashes(htmlspecialchars(trim(($o['paciente_nombre'] ?? '') . ' ' . ($o['paciente_apellido'] ?? '')))); ?>')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-[#007BFF] text-[#007BFF] hover:bg-[#007BFF] hover:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Subir archivo
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para subir archivo -->
<div id="uploadModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-4 sm:p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-lg mx-auto border-white/40 max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold text-[#212529] mb-2">Subir archivo de estudio</h3>
        <p class="text-xs text-[#6C757D] font-medium mb-8 uppercase tracking-wide" id="modalPacienteName"></p>

        <form action="<?php echo UrlHelper::url('api/imagenes/upload'); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="orden_id" id="modalOrdenId">
            <input type="hidden" name="redirect_after" value="dashboard_imaging">
            <div>
                <label class="block text-sm font-semibold mb-2">Archivo de imagen o resultado</label>
                <input type="file" name="archivo_imagen" id="modalFile" accept=".jpg,.jpeg,.png,.pdf,.dcm" required
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[#007BFF] hover:file:bg-blue-100">
                <p class="text-xs text-[#6C757D] mt-1">Formatos permitidos: JPG, PNG, PDF, DCM</p>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeUploadModal()"
                    class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</button>
                <button type="submit"
                    class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
var csrfToken = '<?php echo addslashes($csrfToken); ?>';
var updateStatusUrl = '<?php echo addslashes(UrlHelper::url('api/imagenes/update-status')); ?>';

function openUploadModal(id, paciente) {
    document.getElementById('modalOrdenId').value = id;
    document.getElementById('modalPacienteName').innerText = 'Paciente: ' + paciente;
    document.getElementById('modalFile').value = '';
    document.getElementById('uploadModal').classList.remove('hidden');
}
function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}
function cambiarEstado(ordenId, nuevoEstado) {
    fetch(updateStatusUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
            orden_id: ordenId,
            nuevo_estado: nuevoEstado
        })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.reload();
        } else {
            showToast('Error: ' + (data.error || 'No se pudo cambiar el estado.'), 'error');
        }
    })
    .catch(function(err) {
        console.error(err);
        showToast('Error de conexión.', 'error');
    });
}

function llamarSiguiente(area) {
    if (!confirm('¿Desea llamar al siguiente paciente para ' + area + '?')) return;

    fetch('<?php echo UrlHelper::url('api/turnos/llamar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ area: area })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Llamando al turno: ' + data.turno.numero, 'success');
            // Recargar para ver los cambios
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

$(document).ready(function () {
    var opts = { dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>', paging: true, pageLength: 10 };
    if ($('#pendientesTable').length && typeof initDataTable === 'function') {
        initDataTable('#pendientesTable', opts);
    }
    if ($('#enProcesoTable').length && typeof initDataTable === 'function') {
        initDataTable('#enProcesoTable', opts);
    }
    if ($('#completadosTable').length && typeof initDataTable === 'function') {
        initDataTable('#completadosTable', opts);
    }
});

function marcarTurnoAtendido(turnoId) {
    if (!confirm('¿Está seguro de marcar este turno como atendido?')) return;
    
    fetch('<?php echo UrlHelper::url('api/turnos/atendido'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
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

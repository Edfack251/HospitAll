<?php
use App\Controllers\DashboardController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\UrlHelper;
use App\Helpers\PrivacyHelper;

AuthHelper::checkRole(['enfermera', 'administrador']);

$pdo = $pdo ?? $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    throw new \RuntimeException('No se pudo conectar a la base de datos.');
}

$controller = new DashboardController($pdo);
$data = $controller->getEnfermeraData();

$pacientesAsignados = $data['pacientesAsignados'] ?? [];
$emergenciasActivas = $data['emergenciasActivas'] ?? [];
$emergenciasHoy = $data['emergenciasHoy'] ?? [];
$pacientes = $data['pacientes'] ?? [];

$pageTitle = 'Dashboard Enfermería - HospitAll';
$activePage = 'dashboard_nursing';
$headerTitle = 'Panel de Enfermería';
$headerSubtitle = 'Pacientes asignados hoy. Toma de signos vitales y registro de observaciones.';
$csrfToken = CsrfHelper::generateToken();

include __DIR__ . '/../layout/header.php';
?>

<?php
function estadoBadgeEnf($estado) {
    switch ($estado) {
        case 'Programada': return 'bg-blue-50 text-blue-700 border border-blue-200';
        case 'Confirmada': return 'bg-cyan-50 text-cyan-700 border border-cyan-200';
        case 'En espera': return 'bg-amber-50 text-amber-700 border border-amber-200';
        default: return 'bg-gray-100 text-gray-600';
    }
}
function triageBadgeColor($nivel) {
    switch ($nivel) {
        case 'Rojo': return '#DC3545';
        case 'Naranja': return '#FD7E14';
        case 'Amarillo': return '#FFC107';
        case 'Verde': return '#28A745';
        default: return '#6C757D';
    }
}
?>

    <div class="glass-card p-6 rounded-2xl border-l-4 border-l-[#6F42C1]">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-[#6C757D] uppercase tracking-wide">Pacientes Internados</p>
                <p class="text-4xl font-extrabold text-[#6F42C1] mt-1"><?php echo count($data['internamientosActivos'] ?? []); ?></p>
            </div>
            <div class="w-14 h-14 rounded-xl bg-purple-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-[#6F42C1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
        </div>
    </div>
</div>

<!-- Área de Hospitalización -->
<div class="glass-card rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-purple-50/50 to-transparent">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#6F42C1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#212529]">Monitoreo de Hospitalización</h3>
                    <p class="text-sm text-[#6C757D]">Seguimiento de rondas, signos vitales y cuidados de pacientes internados</p>
                </div>
            </div>
            <a href="<?php echo UrlHelper::url('hospitalization_rounds'); ?>" class="bg-[#6F42C1] text-white px-6 py-2.5 rounded-lg font-semibold shadow-md hover:bg-purple-700 transition-all inline-flex items-center gap-2">
                Ver todas las rondas
            </a>
        </div>
    </div>
    <div class="p-6">
        <?php if (empty($data['internamientosActivos'])): ?>
        <div class="py-10 text-center border border-dashed border-gray-200 rounded-xl">
            <p class="text-[#6C757D] font-medium">No hay pacientes internados actualmente</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach (array_slice($data['internamientosActivos'], 0, 6) as $internamiento): ?>
            <div class="p-4 rounded-xl border border-gray-100 hover:shadow-md transition-shadow bg-white flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full mb-2 inline-block">Hab. <?php echo htmlspecialchars($internamiento['habitacion_numero']); ?> - Cama <?php echo htmlspecialchars($internamiento['cama_numero']); ?></span>
                    <p class="font-bold text-[#212529] truncate"><?php echo htmlspecialchars(trim(($internamiento['paciente_nombre'] ?? '') . ' ' . ($internamiento['paciente_apellido'] ?? ''))); ?></p>
                    <p class="text-xs text-[#6C757D] mt-1">Ingreso: <?php echo date('d/m/Y', strtotime($internamiento['fecha_ingreso'])); ?></p>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="<?php echo UrlHelper::url('hospitalization_rounds'); ?>?internamiento_id=<?php echo $internamiento['id']; ?>" class="flex-1 text-center py-1.5 bg-gray-50 text-[#6C757D] rounded-lg text-xs font-bold hover:bg-purple-50 hover:text-purple-700 transition-colors">Nueva Ronda</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($data['internamientosActivos']) > 6): ?>
        <div class="mt-4 text-center">
            <a href="<?php echo UrlHelper::url('hospitalization_rounds'); ?>" class="text-sm text-[#007BFF] font-semibold hover:underline">Ver los <?php echo count($data['internamientosActivos']); ?> pacientes...</a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Área de Emergencias -->
<div class="glass-card rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-red-50/50 to-transparent">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#DC3545]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#212529]">Área de emergencias</h3>
                    <p class="text-sm text-[#6C757D]">Triaje, asignación de médico y seguimiento de ingresos por emergencia</p>
                </div>
            </div>
            <button type="button" onclick="openEmergenciaModal()" class="bg-[#DC3545] text-white px-6 py-2.5 rounded-lg font-semibold shadow-md hover:bg-red-700 transition-all inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Registrar emergencia
            </button>
        </div>
    </div>
    <div class="p-6 space-y-6">
        <!-- Tabla emergencias activas -->
        <?php if (empty($emergenciasActivas)): ?>
        <div class="py-10 text-center border border-dashed border-gray-200 rounded-xl">
            <p class="text-[#6C757D] font-medium">No hay emergencias activas</p>
            <p class="text-sm text-[#6C757D] mt-1">Registre un ingreso de emergencia para comenzar</p>
        </div>
        <?php else: ?>
        <table id="emergenciasActivasTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] text-sm font-semibold uppercase tracking-wider border-b-2 border-gray-100">
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Nivel triaje</th>
                    <th class="py-4 px-2">Motivo</th>
                    <th class="py-4 px-2">Médico asignado</th>
                    <th class="py-4 px-2">Estado</th>
                    <th class="py-4 px-2">Hora ingreso</th>
                    <th class="py-4 px-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emergenciasActivas as $e): ?>
                <tr class="border-b border-gray-50 hover:bg-red-50/30 transition-colors">
                    <td class="py-4 px-2 font-semibold text-[#212529]"><?php echo htmlspecialchars(trim(($e['paciente_nombre'] ?? '') . ' ' . ($e['paciente_apellido'] ?? ''))); ?></td>
                    <td class="py-4 px-2">
                        <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold text-white" style="background-color: <?php echo htmlspecialchars(triageBadgeColor($e['nivel_triage'] ?? '')); ?>"><?php echo htmlspecialchars($e['nivel_triage'] ?? '-'); ?></span>
                    </td>
                    <td class="py-4 px-2 text-sm text-[#495057] max-w-xs truncate"><?php echo htmlspecialchars($e['motivo_ingreso'] ?? '-'); ?></td>
                    <td class="py-4 px-2 text-sm"><?php $med = trim(($e['medico_nombre'] ?? '') . ' ' . ($e['medico_apellido'] ?? '')); echo $med ? 'Dr. ' . htmlspecialchars($med) : '-'; ?></td>
                    <td class="py-4 px-2"><span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold <?php echo ($e['estado'] ?? '') === 'En atención' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>"><?php echo htmlspecialchars($e['estado'] ?? '-'); ?></span></td>
                    <td class="py-4 px-2 text-[#6C757D] text-sm"><?php echo $e['fecha_ingreso'] ? date('H:i', strtotime($e['fecha_ingreso'])) : '-'; ?></td>
                    <td class="py-4 px-2 text-right">
                        <?php if (empty($e['medico_id'])): ?>
                        <button type="button" onclick="openAsignarMedicoModal(<?php echo (int)$e['id']; ?>)" class="bg-[#007BFF] text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-blue-700 mr-1">Asignar médico</button>
                        <?php endif; ?>
                        <?php if (($e['estado'] ?? '') === 'En espera'): ?>
                        <button type="button" onclick="cambiarEstado(<?php echo (int)$e['id']; ?>, 'En atención')" class="bg-[#28A745] text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-green-700">En atención</button>
                        <?php elseif (($e['estado'] ?? '') === 'En atención'): ?>
                        <select onchange="if(this.value){cambiarEstado(<?php echo (int)$e['id']; ?>, this.value); this.value='';}" class="text-xs rounded-lg border border-gray-200 py-1.5 px-2">
                            <option value="">Actualizar estado</option>
                            <option value="Atendido">Atendido</option>
                            <option value="Transferido">Transferido</option>
                        </select>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Tabla emergencias de hoy (colapsable) -->
        <div class="border-t border-gray-200 pt-6">
            <button type="button" onclick="toggleEmergenciasHoy()" class="flex items-center gap-2 text-[#007BFF] font-semibold hover:underline" id="toggleEmergenciasHoyBtn">
                <svg id="toggleIcon" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                Emergencias de hoy (<?php echo count($emergenciasHoy); ?>)
            </button>
            <div id="emergenciasHoySection" class="mt-4 overflow-hidden hidden">
                <?php if (empty($emergenciasHoy)): ?>
                <p class="text-[#6C757D] text-sm">No hay emergencias registradas hoy</p>
                <?php else: ?>
                <table id="emergenciasHoyTable" class="display w-full">
                    <thead>
                        <tr class="text-left text-[#6C757D] text-sm font-semibold uppercase tracking-wider border-b-2 border-gray-100">
                            <th class="py-4 px-2">Paciente</th>
                            <th class="py-4 px-2">Nivel triaje</th>
                            <th class="py-4 px-2">Motivo</th>
                            <th class="py-4 px-2">Médico</th>
                            <th class="py-4 px-2">Estado</th>
                            <th class="py-4 px-2">Hora ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emergenciasHoy as $e): ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 px-2 font-semibold text-[#212529]"><?php echo htmlspecialchars(trim(($e['paciente_nombre'] ?? '') . ' ' . ($e['paciente_apellido'] ?? ''))); ?></td>
                            <td class="py-4 px-2"><span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold text-white" style="background-color: <?php echo htmlspecialchars(triageBadgeColor($e['nivel_triage'] ?? '')); ?>"><?php echo htmlspecialchars($e['nivel_triage'] ?? '-'); ?></span></td>
                            <td class="py-4 px-2 text-sm text-[#495057] max-w-xs truncate"><?php echo htmlspecialchars($e['motivo_ingreso'] ?? '-'); ?></td>
                            <td class="py-4 px-2 text-sm"><?php $med = trim(($e['medico_nombre'] ?? '') . ' ' . ($e['medico_apellido'] ?? '')); echo $med ? 'Dr. ' . htmlspecialchars($med) : '-'; ?></td>
                            <td class="py-4 px-2"><span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600"><?php echo htmlspecialchars($e['estado'] ?? '-'); ?></span></td>
                            <td class="py-4 px-2 text-[#6C757D] text-sm"><?php echo $e['fecha_ingreso'] ? date('H:i', strtotime($e['fecha_ingreso'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal registrar emergencia -->
<div id="emergenciaModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-4 sm:p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-lg mx-auto border-white/40 max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold text-[#212529] mb-6">Registrar ingreso de emergencia</h3>
        <form id="emergenciaForm" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-[#6C757D] mb-1">Paciente</label>
                <select name="paciente_id" id="emergenciaPacienteId" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    <option value="">Seleccione paciente</option>
                    <?php foreach ($pacientes as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')) . ' - ' . PrivacyHelper::maskCedula($p['identificacion'] ?? '', $p['id'])); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="openPacienteNuevoModal()" class="mt-2 text-sm text-[#007BFF] font-medium hover:underline">Paciente no está en la lista? Registrar nuevo</button>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#6C757D] mb-1">Nivel de triaje</label>
                <select name="nivel_triage" id="emergenciaNivelTriage" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    <option value="">Seleccione nivel</option>
                    <option value="Rojo">Rojo (crítico)</option>
                    <option value="Naranja">Naranja (muy urgente)</option>
                    <option value="Amarillo">Amarillo (urgente)</option>
                    <option value="Verde">Verde (poco urgente)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#6C757D] mb-1">Motivo de ingreso</label>
                <textarea name="motivo_ingreso" id="emergenciaMotivo" rows="4" required placeholder="Describa el motivo del ingreso..." class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeEmergenciaModal()" class="px-6 py-2 rounded-lg border border-gray-200 font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="bg-[#DC3545] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-red-700">Registrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal registrar paciente nuevo (emergencia) -->
<div id="pacienteNuevoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-[60] p-4 transition-all">
    <div class="glass-card p-4 sm:p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-lg mx-auto border-white/40 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-[#212529] mb-2">Registrar paciente (emergencia)</h3>
        <p class="text-sm text-[#6C757D] mb-6">Solo se crea el registro en el sistema. No se crea cuenta de acceso al portal.</p>
        <form id="pacienteNuevoForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#6C757D] mb-1">Nombre *</label>
                    <input type="text" name="nombre" id="pacNuevoNombre" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#6C757D] mb-1">Apellido *</label>
                    <input type="text" name="apellido" id="pacNuevoApellido" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#6C757D] mb-1">Identificación *</label>
                    <input type="text" name="identificacion" id="pacNuevoIdentificacion" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]" placeholder="Cédula, pasaporte...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#6C757D] mb-1">Tipo documento</label>
                    <select name="identificacion_tipo" id="pacNuevoIdentificacionTipo" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                        <option value="Cédula">Cédula</option>
                        <option value="Pasaporte">Pasaporte</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#6C757D] mb-1">Género *</label>
                    <select name="genero" id="pacNuevoGenero" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#6C757D] mb-1">Fecha nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" id="pacNuevoFechaNac" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#6C757D] mb-1">Teléfono (opcional)</label>
                <input type="text" name="telefono" id="pacNuevoTelefono" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]" placeholder="809-000-0000">
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closePacienteNuevoModal()" class="px-6 py-2 rounded-lg border border-gray-200 font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700">Registrar paciente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal asignar médico -->
<div id="asignarMedicoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-4 sm:p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-md mx-auto border-white/40">
        <h3 class="text-2xl font-bold text-[#212529] mb-6">Asignar médico</h3>
        <form id="asignarMedicoForm" class="space-y-5">
            <input type="hidden" id="asignarEmergenciaId" name="emergencia_id">
            <div>
                <label class="block text-sm font-medium text-[#6C757D] mb-1">Médico</label>
                <select name="medico_id" id="asignarMedicoId" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    <option value="">Cargando...</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeAsignarMedicoModal()" class="px-6 py-2 rounded-lg border border-gray-200 font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="bg-[#007BFF] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700">Asignar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de pacientes asignados -->
<div class="glass-card rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-blue-50/50 to-transparent">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-[#212529]">Pacientes asignados</h3>
                <p class="text-sm text-[#6C757D]">Registrar signos vitales y observaciones pre-consulta</p>
            </div>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <?php if (empty($pacientesAsignados)): ?>
        <div class="py-16 text-center">
            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <p class="text-[#6C757D] font-medium">No hay pacientes asignados hoy</p>
            <p class="text-sm text-[#6C757D] mt-1">Las citas de hoy aparecerán aquí</p>
        </div>
        <?php else: ?>
        <table id="pacientesTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] text-sm font-semibold uppercase tracking-wider border-b-2 border-gray-100">
                    <th class="py-4 px-2">Paciente</th>
                    <th class="py-4 px-2">Médico</th>
                    <th class="py-4 px-2">Hora</th>
                    <th class="py-4 px-2">Estado</th>
                    <th class="py-4 px-2">Signos vitales</th>
                    <th class="py-4 px-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pacientesAsignados as $row): ?>
                <tr class="border-b border-gray-50 hover:bg-blue-50/30 transition-colors">
                    <td class="py-4 px-2">
                        <span class="font-semibold text-[#212529]">
                            <?php echo htmlspecialchars(trim(($row['paciente_nombre'] ?? '') . ' ' . ($row['paciente_apellido'] ?? ''))); ?>
                        </span>
                    </td>
                    <td class="py-4 px-2 text-[#495057] text-sm"><?php $medico = trim(($row['medico_nombre'] ?? '') . ' ' . ($row['medico_apellido'] ?? '')); echo $medico ? 'Dr. ' . htmlspecialchars($medico) : '-'; ?></td>
                    <td class="py-4 px-2 text-[#6C757D] text-sm"><?php echo $row['hora'] ? date('H:i', strtotime($row['hora'])) : '-'; ?></td>
                    <td class="py-4 px-2">
                        <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold <?php echo estadoBadgeEnf($row['estado'] ?? 'Programada'); ?>">
                            <?php echo htmlspecialchars($row['estado'] ?? 'Programada'); ?>
                        </span>
                    </td>
                    <td class="py-4 px-2">
                        <?php if (!empty($row['signos_registrados'])): ?>
                        <span class="text-[#28A745] font-bold" title="Signos vitales registrados">✓</span>
                        <?php else: ?>
                        <span class="text-red-500 font-bold" title="Pendiente de registrar">✗</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 px-2 text-right">
                        <button type="button" onclick="openModal(<?php echo (int)$row['cita_id']; ?>, <?php echo (int)$row['medico_id']; ?>, <?php echo (int)$row['paciente_id']; ?>, '<?php echo addslashes(htmlspecialchars(trim(($row['paciente_nombre'] ?? '') . ' ' . ($row['paciente_apellido'] ?? '')))); ?>')" class="btn-primary-gradient text-white px-4 py-2 rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Registrar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal signos vitales + observaciones -->
<div id="registroModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all">
    <div class="glass-card p-4 sm:p-6 md:p-10 rounded-3xl shadow-2xl w-full max-w-lg mx-auto border-white/40 max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold text-[#212529] mb-2">Registrar signos vitales y observaciones</h3>
        <p class="text-xs text-[#6C757D] font-medium mb-6 uppercase tracking-wide" id="modalPacienteName"></p>

        <form id="registroForm" class="space-y-6">
            <input type="hidden" id="modalCitaId" name="cita_id">
            <input type="hidden" id="modalMedicoId" name="medico_id">
            <input type="hidden" id="modalPacienteId" name="paciente_id">

            <div class="border-b border-gray-200 pb-4">
                <h4 class="text-sm font-semibold text-[#212529] mb-3 uppercase tracking-wide">Signos vitales</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#6C757D] mb-1">Presión arterial</label>
                        <input type="text" id="inputPresion" name="presion_arterial" placeholder="120/80" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#6C757D] mb-1">Frecuencia cardíaca (lpm)</label>
                        <input type="number" id="inputFc" name="frecuencia_cardiaca" min="0" step="1" placeholder="72" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#6C757D] mb-1">Temperatura (°C)</label>
                        <input type="number" id="inputTemp" name="temperatura" min="0" step="0.1" placeholder="36.5" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#6C757D] mb-1">Peso (kg)</label>
                        <input type="number" id="inputPeso" name="peso" min="0" step="0.1" placeholder="70" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-[#6C757D] mb-1">Estatura (cm)</label>
                        <input type="number" id="inputEstatura" name="estatura" min="0" step="0.1" placeholder="170" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]">
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-[#212529] mb-3 uppercase tracking-wide">Observaciones</h4>
                <textarea id="inputObservaciones" name="observaciones" rows="4" placeholder="Observaciones de enfermería..." class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none text-[#212529]"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-2 rounded-lg border border-gray-200 font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
var csrfToken = '<?php echo addslashes($csrfToken); ?>';
var signosVitalesUrl = '<?php echo addslashes(UrlHelper::url('api/enfermeria/signos-vitales')); ?>';
var observacionUrl = '<?php echo addslashes(UrlHelper::url('api/enfermeria/observacion')); ?>';
var signosVitalesCitaUrl = '<?php echo addslashes(UrlHelper::url('api/enfermeria/signos-vitales-cita')); ?>';
var emergenciaRegistrarUrl = '<?php echo addslashes(UrlHelper::url("api/enfermeria/emergencia/registrar")); ?>';
var emergenciaCrearPacienteUrl = '<?php echo addslashes(UrlHelper::url("api/enfermeria/emergencia/crear-paciente")); ?>';
var emergenciaAsignarUrl = '<?php echo addslashes(UrlHelper::url("api/enfermeria/emergencia/asignar-medico")); ?>';
var emergenciaEstadoUrl = '<?php echo addslashes(UrlHelper::url("api/enfermeria/emergencia/actualizar-estado")); ?>';
var medicosDisponiblesUrl = '<?php echo addslashes(UrlHelper::url("api/enfermeria/emergencia/medicos-disponibles")); ?>';

function openModal(citaId, medicoId, pacienteId, paciente) {
    document.getElementById('modalCitaId').value = citaId;
    document.getElementById('modalMedicoId').value = medicoId;
    document.getElementById('modalPacienteId').value = pacienteId;
    document.getElementById('modalPacienteName').innerText = 'Paciente: ' + paciente;
    document.getElementById('inputPresion').value = '';
    document.getElementById('inputFc').value = '';
    document.getElementById('inputTemp').value = '';
    document.getElementById('inputPeso').value = '';
    document.getElementById('inputEstatura').value = '';
    document.getElementById('inputObservaciones').value = '';
    document.getElementById('registroModal').classList.remove('hidden');

    fetch(signosVitalesCitaUrl + '?cita_id=' + citaId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.presion_arterial) document.getElementById('inputPresion').value = data.presion_arterial;
            if (data.frecuencia_cardiaca) document.getElementById('inputFc').value = data.frecuencia_cardiaca;
            if (data.temperatura) document.getElementById('inputTemp').value = data.temperatura;
            if (data.peso) document.getElementById('inputPeso').value = data.peso;
            if (data.estatura) document.getElementById('inputEstatura').value = data.estatura;
        })
        .catch(function() {});
}

function closeModal() {
    document.getElementById('registroModal').classList.add('hidden');
}

function openEmergenciaModal() {
    document.getElementById('emergenciaModal').classList.remove('hidden');
}
function closeEmergenciaModal() {
    document.getElementById('emergenciaModal').classList.add('hidden');
}

function openPacienteNuevoModal() {
    document.getElementById('pacienteNuevoModal').classList.remove('hidden');
    document.getElementById('pacNuevoNombre').value = '';
    document.getElementById('pacNuevoApellido').value = '';
    document.getElementById('pacNuevoIdentificacion').value = '';
    document.getElementById('pacNuevoTelefono').value = '';
}
function closePacienteNuevoModal() {
    document.getElementById('pacienteNuevoModal').classList.add('hidden');
}

document.getElementById('pacienteNuevoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var data = {
        nombre: document.getElementById('pacNuevoNombre').value.trim(),
        apellido: document.getElementById('pacNuevoApellido').value.trim(),
        identificacion: document.getElementById('pacNuevoIdentificacion').value.trim(),
        identificacion_tipo: document.getElementById('pacNuevoIdentificacionTipo').value,
        genero: document.getElementById('pacNuevoGenero').value,
        fecha_nacimiento: document.getElementById('pacNuevoFechaNac').value,
        telefono: document.getElementById('pacNuevoTelefono').value.trim() || null
    };
    fetch(emergenciaCrearPacienteUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify(data)
    })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.paciente_id) {
                closePacienteNuevoModal();
                var sel = document.getElementById('emergenciaPacienteId');
                var label = data.nombre + ' ' + data.apellido + ' - ' + data.identificacion;
                var opt = document.createElement('option');
                opt.value = res.paciente_id;
                opt.textContent = label;
                opt.selected = true;
                sel.appendChild(opt);
            } else {
                showToast('Error: ' + (res.error || 'No se pudo registrar.'), 'error');
            }
        })
        .catch(function() { showToast('Error de conexión.', 'error'); });
});

function openAsignarMedicoModal(emergenciaId) {
    document.getElementById('asignarEmergenciaId').value = emergenciaId;
    document.getElementById('asignarMedicoModal').classList.remove('hidden');
    var sel = document.getElementById('asignarMedicoId');
    sel.innerHTML = '<option value="">Cargando...</option>';
    fetch(medicosDisponiblesUrl)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            sel.innerHTML = '<option value="">Seleccione médico</option>';
            if (data.success && data.medicos) {
                data.medicos.forEach(function(m) {
                    var opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = (m.nombre || '') + ' ' + (m.apellido || '') + ' - ' + (m.especialidad || '');
                    sel.appendChild(opt);
                });
            }
        })
        .catch(function() { sel.innerHTML = '<option value="">Error al cargar</option>'; });
}
function closeAsignarMedicoModal() {
    document.getElementById('asignarMedicoModal').classList.add('hidden');
}

function cambiarEstado(emergenciaId, nuevoEstado) {
    fetch(emergenciaEstadoUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ emergencia_id: emergenciaId, nuevo_estado: nuevoEstado })
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) window.location.reload();
            else showToast('Error: ' + (data.error || 'No se pudo actualizar.'), 'error');
        })
        .catch(function() { showToast('Error de conexión.', 'error'); });
}

function toggleEmergenciasHoy() {
    var sec = document.getElementById('emergenciasHoySection');
    var icon = document.getElementById('toggleIcon');
    if (sec.classList.contains('hidden')) {
        sec.classList.remove('hidden');
        icon.style.transform = 'rotate(0deg)';
    } else {
        sec.classList.add('hidden');
        icon.style.transform = 'rotate(-90deg)';
    }
}

document.getElementById('emergenciaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var pacienteId = parseInt(document.getElementById('emergenciaPacienteId').value, 10);
    var nivelTriage = document.getElementById('emergenciaNivelTriage').value;
    var motivo = document.getElementById('emergenciaMotivo').value.trim();
    if (!pacienteId || !nivelTriage || !motivo) {
        showToast('Complete todos los campos.', 'warning');
        return;
    }
    fetch(emergenciaRegistrarUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ paciente_id: pacienteId, nivel_triage: nivelTriage, motivo_ingreso: motivo })
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                closeEmergenciaModal();
                window.location.reload();
            } else {
                showToast('Error: ' + (data.error || 'No se pudo registrar.'), 'error');
            }
        })
        .catch(function() { showToast('Error de conexión.', 'error'); });
});

document.getElementById('asignarMedicoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var emergenciaId = parseInt(document.getElementById('asignarEmergenciaId').value, 10);
    var medicoId = parseInt(document.getElementById('asignarMedicoId').value, 10);
    if (!emergenciaId || !medicoId) {
        showToast('Seleccione un médico.', 'warning');
        return;
    }
    fetch(emergenciaAsignarUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ emergencia_id: emergenciaId, medico_id: medicoId })
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                closeAsignarMedicoModal();
                window.location.reload();
            } else {
                showToast('Error: ' + (data.error || 'No se pudo asignar.'), 'error');
            }
        })
        .catch(function() { showToast('Error de conexión.', 'error'); });
});

document.getElementById('registroForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var citaId = parseInt(document.getElementById('modalCitaId').value, 10);
    var medicoId = parseInt(document.getElementById('modalMedicoId').value, 10);
    var pacienteId = parseInt(document.getElementById('modalPacienteId').value, 10);
    var presion = document.getElementById('inputPresion').value.trim();
    var fc = document.getElementById('inputFc').value.trim();
    var temp = document.getElementById('inputTemp').value.trim();
    var peso = document.getElementById('inputPeso').value.trim();
    var estatura = document.getElementById('inputEstatura').value.trim();
    var observaciones = document.getElementById('inputObservaciones').value.trim();

    var tieneSignos = presion || fc || temp || peso || estatura;
    var promesas = [];

    if (tieneSignos) {
        promesas.push(fetch(signosVitalesUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({
                cita_id: citaId,
                medico_id: medicoId,
                paciente_id: pacienteId,
                data: {
                    presion_arterial: presion || null,
                    frecuencia_cardiaca: fc ? parseInt(fc, 10) : null,
                    temperatura: temp ? parseFloat(temp) : null,
                    peso: peso ? parseFloat(peso) : null,
                    estatura: estatura ? parseFloat(estatura) : null
                }
            })
        }));
    }
    if (observaciones) {
        promesas.push(fetch(observacionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ cita_id: citaId, paciente_id: pacienteId, observaciones: observaciones })
        }));
    }

    if (promesas.length === 0) {
        showToast('Debe ingresar al menos signos vitales o observaciones.', 'warning');
        return;
    }

    Promise.all(promesas)
        .then(function(responses) { return Promise.all(responses.map(function(r) { return r.json(); })); })
        .then(function(results) {
            var ok = results.every(function(r) { return r.success; });
            if (ok) {
                window.location.reload();
            } else {
                var err = (results.find(function(r) { return !r.success; }) || {}).error || 'Error al guardar.';
                showToast('Error: ' + err, 'error');
            }
        })
        .catch(function(err) {
            console.error(err);
            showToast('Error de conexión.', 'error');
        });
});

$(document).ready(function () {
    var opts = { dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>', paging: true, pageLength: 10 };
    if ($('#pacientesTable').length && typeof initDataTable === 'function') {
        initDataTable('#pacientesTable', opts);
    }
    if ($('#emergenciasActivasTable').length && typeof initDataTable === 'function') {
        initDataTable('#emergenciasActivasTable', opts);
    }
    if ($('#emergenciasHoyTable').length && typeof initDataTable === 'function') {
        initDataTable('#emergenciasHoyTable', opts);
    }
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

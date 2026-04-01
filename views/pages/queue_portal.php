<?php
use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['medico', 'tecnico_laboratorio', 'tecnico_imagenes', 'farmaceutico', 'recepcionista', 'administrador']);

$userRoleRaw = $_SESSION['user_role'] ?? '';
$userRole = strtolower($userRoleRaw);

// Áreas configuradas en el sistema
$areasConfig = [
    'consulta' => ['nombre' => 'Consulta Médica', 'color' => '#007BFF', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    'laboratorio' => ['nombre' => 'Laboratorio', 'color' => '#28A745', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
    'farmacia' => ['nombre' => 'Farmacia', 'color' => '#FD7E14', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
    'imagenes' => ['nombre' => 'Imágenes', 'color' => '#6366F1', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h14a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z']
];

$area_map = [
    'medico' => 'consulta',
    'médico' => 'consulta',
    'tecnico_laboratorio' => 'laboratorio',
    'tecnico_imagenes' => 'imagenes',
    'farmaceutico' => 'farmacia',
    'farmaceútico' => 'farmacia',
];

$mi_area = $area_map[$userRole] ?? null;

// Títulos y meta
$pageTitle = 'Portal de Turnos - HospitAll';
$activePage = 'queue_portal';
$headerTitle = 'Portal de Turnos';
$headerSubtitle = $mi_area ? "Gestión de turnos para " . ucfirst($mi_area) : "Panel general de gestión";

include __DIR__ . '/../layout/header.php';
?>

<div id="portal-container" class="space-y-8 pb-20">
    <?php if ($mi_area): ?>
        <!-- VISTA INDIVIDUAL (MÉDICO / TÉCNICO) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Panel Principal -->
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden flex flex-col min-h-[500px]">
                <div class="p-8 border-b bg-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 uppercase tracking-tight">ÁREA: <?php echo ($mi_area === 'imagenes' ? 'IMÁGENES' : strtoupper($mi_area)); ?></h3>
                        <p class="text-gray-500 font-medium italic">Control de flujo de atención</p>
                    </div>
                    <div class="bg-blue-50 text-[#007BFF] px-6 py-3 rounded-2xl font-black border border-blue-100">
                        <span id="wait-count-<?php echo $mi_area; ?>" class="text-xl">0</span> Pacientes en espera
                    </div>
                </div>

                <div class="flex-1 p-12 flex flex-col items-center justify-center text-center">
                    <p class="text-gray-400 font-black uppercase tracking-[0.3em] text-xs mb-6">TURNOS EN ATENCIÓN</p>
                    <div id="actual-num-<?php echo $mi_area; ?>" class="text-[12rem] md:text-[15rem] font-black leading-none mb-6 transition-all duration-500 transform text-[#007BFF] drop-shadow-sm">
                        --
                    </div>
                    <div id="actual-name-<?php echo $mi_area; ?>" class="text-3xl font-bold text-gray-700 mb-12 h-10 truncate max-w-2xl px-4">
                        Esperando paciente...
                    </div>

                    <div class="flex flex-col gap-4 w-full max-w-md mx-auto">
                        <button id="btn-llamar-<?php echo $mi_area; ?>" onclick="llamarSiguiente('<?php echo $mi_area; ?>')" 
                            class="w-full py-8 bg-[#007BFF] text-white rounded-[2.5rem] text-2xl font-black shadow-2xl hover:bg-blue-700 hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-6 group">
                            <svg class="w-8 h-8 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 5v14l11-7L5 5z"></path></svg>
                            Llamar Siguiente
                        </button>
                        
                        <button id="btn-atender-individual-<?php echo $mi_area; ?>" onclick="atenderTurnoActualIndividual()" 
                            class="hidden w-full py-6 bg-[#28A745] text-white rounded-[2.5rem] text-2xl font-black shadow-2xl hover:bg-green-700 hover:scale-105 active:scale-95 transition-all items-center justify-center gap-6 group">
                            <svg class="w-8 h-8 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Atender Paciente
                        </button>
                    </div>
                    
                    <p class="mt-8 text-gray-400 text-sm font-medium">Controle el flujo de atención desde este panel.</p>
                </div>
            </div>

            <!-- Lista de Espera del Área -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                    <h4 class="font-bold text-gray-700">Próximos en Espera</h4>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b">
                            <tr>
                                <th class="px-6 py-4">Turno</th>
                                <th class="px-4 py-4">Paciente</th>
                                <th class="px-4 py-4">Hora</th>
                            </tr>
                        </thead>
                        <tbody id="espera-tabla-<?php echo $mi_area; ?>" class="divide-y divide-gray-100">
                            <!-- Dinámico -->
                            <tr><td colspan="3" class="px-6 py-12 text-center text-gray-400 italic">No hay pacientes esperando</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- VISTA DE PANELES (RECEPCIONISTA / ADMINISTRADOR) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            foreach ($areasConfig as $key => $config):
            ?>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full border-t-8 transition-all hover:shadow-md" style="border-top-color: <?php echo $config['color']; ?>">
                <div class="p-5 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg text-white" style="background-color: <?php echo $config['color']; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $config['icon']; ?>"></path></svg>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm whitespace-nowrap"><?php echo $config['nombre']; ?></h4>
                    </div>
                </div>
                
                <div class="p-8 flex-1 flex flex-col items-center justify-center text-center">
                    <p class="text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">ACTUAL</p>
                    <div id="actual-num-<?php echo $key; ?>" class="text-6xl font-black mb-2 transition-all duration-300" style="color: <?php echo $config['color']; ?>">--</div>
                    <div id="actual-name-<?php echo $key; ?>" class="text-xs font-bold text-gray-500 mb-8 h-4 truncate w-full italic">--</div>
                    
                    <div class="bg-gray-50 w-full mb-8 py-3 rounded-2xl border border-gray-100">
                        <span id="wait-count-<?php echo $key; ?>" class="text-lg font-black text-gray-700">0</span>
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">En espera</p>
                    </div>
                    
                    <div class="grid <?php echo ($userRole === 'administrador') ? 'grid-cols-2' : 'grid-cols-1'; ?> gap-3 w-full mt-auto">
                        <button onclick="abrirModalNuevoTurno('<?php echo $key; ?>')" 
                            class="w-full py-3.5 bg-gray-900 text-white rounded-2xl text-[11px] font-black hover:bg-black transition-all flex items-center justify-center gap-2 shadow-lg active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            GENERAR
                        </button>
                        <?php if ($userRole === 'administrador' || ($area_map[$userRole] ?? null) === $key): ?>
                        <button id="btn-llamar-<?php echo $key; ?>" onclick="llamarSiguiente('<?php echo $key; ?>')" 
                            class="w-full py-3.5 text-white rounded-2xl text-[11px] font-black shadow-lg hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-2" 
                            style="background-color: <?php echo $config['color']; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 5v14l11-7L5 5z"></path></svg>
                            LLAMAR
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabla General de Espera (Vista Recepción/Admin) -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mt-8">
            <div class="p-8 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-gray-800 uppercase tracking-tight">Lista de Espera Global</h3>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">En vivo</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table id="globalTable" class="w-full">
                    <thead>
                        <tr class="bg-gray-50/50 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b">
                            <th class="px-8 py-5">Turno</th>
                            <th class="px-6 py-5">Área</th>
                            <th class="px-6 py-5">Paciente</th>
                            <th class="px-6 py-5">Tipo</th>
                            <th class="px-6 py-5">Hora</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="global-wait-list" class="divide-y divide-gray-100">
                        <!-- Dinámico -->
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL NUEVO TURNO -->
<div id="modalTurno" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-95 opacity-0 duration-300" id="modalContent">
        <div class="p-8 border-b flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Nuevo Turno</h3>
                <p class="text-xs text-gray-500 font-bold">Registro de llegada de paciente</p>
            </div>
            <button onclick="cerrarModal()" class="p-2 bg-white rounded-full shadow-sm text-gray-400 hover:text-red-500 transition-all border border-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-10 space-y-8">
            <!-- Selección de Área -->
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Área de Atención</label>
                <div class="grid grid-cols-2 gap-4">
                    <?php foreach ($areasConfig as $key => $config): ?>
                    <button type="button" onclick="setArea('<?php echo $key; ?>')" id="btn-area-<?php echo $key; ?>" 
                        class="area-btn flex flex-col items-center justify-center p-4 rounded-[1.5rem] border-2 border-gray-100 bg-gray-50 hover:bg-white hover:border-blue-500 transition-all group">
                        <div class="p-1.5 rounded-lg mb-2 text-white opacity-40 group-hover:opacity-100" style="background-color: <?php echo $config['color']; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $config['icon']; ?>"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-gray-600 uppercase group-hover:text-blue-600"><?php echo $config['nombre']; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="modal-area" value="">
            </div>

            <!-- Toggle Cita -->
            <div class="flex items-center justify-between p-6 bg-blue-50/50 rounded-3xl border border-blue-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white rounded-2xl shadow-sm text-blue-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="font-black text-gray-800 text-sm">¿TIENE CITA PROGRAMADA?</p>
                        <p class="text-[10px] text-gray-500 font-medium">Los pacientes registrados tienen prioridad.</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="tiene-cita" class="sr-only peer" onchange="toggleCitaFlow()">
                    <div class="w-14 h-8 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-[#28A745]"></div>
                </label>
            </div>

            <!-- Búsqueda de Paciente -->
            <div id="paciente-search-container">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Buscar Paciente</label>
                <div class="relative">
                    <input type="text" id="paciente-search" placeholder="Escriba nombre o cédula..." 
                        class="w-full pl-14 pr-6 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-bold text-gray-700">
                    <svg class="w-6 h-6 absolute left-5 top-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <div id="search-results" class="mt-3 max-h-56 overflow-y-auto bg-white rounded-2xl shadow-xl border border-gray-100 hidden z-50 divide-y divide-gray-50"></div>
            </div>

            <!-- Selección de Citas -->
            <div id="cita-select-container" class="hidden animate-fade-in">
                 <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Seleccionar Cita Disponible</label>
                 <select id="cita-id" class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl outline-none focus:bg-white focus:border-blue-500 font-bold text-gray-700">
                     <option value="">Esperando búsqueda de paciente...</option>
                 </select>
            </div>

            <input type="hidden" id="selected-paciente-id">
        </div>

        <div class="p-8 bg-gray-50 flex gap-4">
            <button onclick="cerrarModal()" class="flex-1 py-4 bg-white text-gray-400 rounded-2xl font-black text-sm border-2 border-gray-100 hover:bg-gray-100 transition-all">CANCELAR</button>
            <button onclick="generarTurnoSubmit()" class="flex-[2] py-4 bg-gray-900 text-white rounded-2xl font-black text-sm shadow-xl hover:bg-black transition-all flex items-center justify-center gap-3">
                <span id="btn-text">GENERAR TURNO</span>
                <div id="btn-loading" class="hidden w-5 h-5 border-3 border-white border-t-transparent rounded-full animate-spin"></div>
            </button>
        </div>
    </div>
</div>

<!-- MODAL ATENDER TURNO -->
<div id="modalAtenderTurno" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-95 opacity-0 duration-300" id="modalAtenderContent">
        <div class="p-8 border-b flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Atender Turno</h3>
                <p class="text-xs text-gray-500 font-bold">Confirmar atención del paciente</p>
            </div>
            <button onclick="cerrarModalAtender()" class="p-2 bg-white rounded-full shadow-sm text-gray-400 hover:text-red-500 transition-all border border-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-10 space-y-8 text-center">
            <p class="text-lg font-bold text-gray-700">¿Desea marcar el turno <span id="atender-turno-numero" class="text-blue-600"></span> como atendido?</p>
            <p class="text-sm text-gray-500">Esta acción finalizará el turno actual y lo registrará como completado.</p>
            <input type="hidden" id="atender-turno-id">
        </div>

        <div class="p-8 bg-gray-50 flex gap-4">
            <button onclick="cerrarModalAtender()" class="flex-1 py-4 bg-white text-gray-400 rounded-2xl font-black text-sm border-2 border-gray-100 hover:bg-gray-100 transition-all">CANCELAR</button>
            <button onclick="confirmarAtenderTurno()" class="flex-[2] py-4 bg-[#28A745] text-white rounded-2xl font-black text-sm shadow-xl hover:bg-green-700 transition-all flex items-center justify-center gap-3">
                <span id="btn-atender-text">CONFIRMAR ATENCIÓN</span>
                <div id="btn-atender-loading" class="hidden w-5 h-5 border-3 border-white border-t-transparent rounded-full animate-spin"></div>
            </button>
        </div>
    </div>
</div>

<script>
const USER_ROLE = '<?php echo $userRole; ?>';
const MI_AREA = '<?php echo $mi_area; ?>';
const API_SALAS = '<?php echo UrlHelper::url('api/turnos/estado-salas'); ?>';
const CSRF_TOKEN = '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>';

let areas_global = <?php echo json_encode($areasConfig); ?>;
let searchTimeout = null;
let turno_actual_id = null;
let turno_actual_numero = null;
let turno_actual_cita_id = null;
let turno_actual_paciente_id = null;
let turno_actual_visita_walkin_id = null;

function atenderTurnoActualIndividual() {
    if (turno_actual_cita_id) {
        window.location.href = `<?php echo UrlHelper::url('appointments_attend'); ?>?id=${turno_actual_cita_id}`;
    } else if (turno_actual_visita_walkin_id) {
        if (MI_AREA === 'consulta') {
            window.location.href = `<?php echo UrlHelper::url('appointments_attend'); ?>?walkin_id=${turno_actual_visita_walkin_id}`;
        } else if (MI_AREA === 'laboratorio') {
            window.location.href = `<?php echo UrlHelper::url('dashboard_laboratory'); ?>?walkin_id=${turno_actual_visita_walkin_id}`;
        } else if (MI_AREA === 'imagenes') {
            window.location.href = `<?php echo UrlHelper::url('dashboard_imaging'); ?>?walkin_id=${turno_actual_visita_walkin_id}`;
        } else if (MI_AREA === 'farmacia') {
            window.location.href = `<?php echo UrlHelper::url('pharmacy_pending_prescriptions'); ?>?walkin_id=${turno_actual_visita_walkin_id}`;
        }
    }
}

$(document).ready(function() {
    updateState();
    setInterval(updateState, 5000);

    $('#paciente-search').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        if (query.length < 3) {
            $('#search-results').addClass('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`<?php echo UrlHelper::url('api/patients/search'); ?>?q=${query}`)
                .then(r => r.json())
                .then(data => {
                    const results = $('#search-results').empty().removeClass('hidden');
                    if (data.length === 0) {
                        results.append('<div class="p-6 text-sm text-gray-400 italic text-center">No hay resultados</div>');
                        return;
                    }
                    data.forEach(p => {
                        const item = $(`
                            <div class="p-4 hover:bg-blue-50 cursor-pointer transition-colors flex items-center justify-between group">
                                <div>
                                    <p class="font-bold text-gray-800">${p.nombre} ${p.apellido}</p>
                                    <p class="text-[10px] font-medium text-gray-400">${p.identificacion}</p>
                                </div>
                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            </div>
                        `);
                        item.on('click', () => seleccionarPaciente(p));
                        results.append(item);
                    });
                });
        }, 300);
    });
});

async function updateState() {
    try {
        const res = await fetch(API_SALAS);
        const data = await res.json();
        
        if (MI_AREA) {
            renderIndividualView(data[MI_AREA]);
        } else {
            renderPanelsView(data);
            renderGlobalWaitList(data);
        }
    } catch (e) {
        console.error("Error updating portal state:", e);
    }
}

function renderIndividualView(info) {
    const numEl = document.getElementById(`actual-num-${MI_AREA}`);
    const nameEl = document.getElementById(`actual-name-${MI_AREA}`);
    const waitEl = document.getElementById(`wait-count-${MI_AREA}`);
    const tabla = document.getElementById(`espera-tabla-${MI_AREA}`);
    const btnAtender = document.getElementById(`btn-atender-individual-${MI_AREA}`);

    const oldNum = numEl.innerText;
    const newNum = info.actual ? info.actual.numero : '--';
    
    if (oldNum !== newNum && newNum !== '--') {
        numEl.classList.add('scale-110');
        setTimeout(() => numEl.classList.remove('scale-110'), 500);
    }

    numEl.innerText = newNum;
    nameEl.innerText = info.actual ? `${info.actual.paciente_nombre} ${info.actual.paciente_apellido}` : 'Esperando paciente...';
    waitEl.innerText = info.esperando_count;

    if (info.actual) {
        turno_actual_id = info.actual.id;
        turno_actual_numero = info.actual.numero;
        turno_actual_cita_id = info.actual.cita_id;
        turno_actual_paciente_id = info.actual.paciente_id;
        turno_actual_visita_walkin_id = info.actual.visita_walkin_id;
        if (btnAtender) {
            btnAtender.classList.remove('hidden');
            btnAtender.classList.add('flex');
        }
    } else {
        turno_actual_id = null;
        turno_actual_numero = null;
        turno_actual_cita_id = null;
        turno_actual_paciente_id = null;
        turno_actual_visita_walkin_id = null;
        if (btnAtender) {
            btnAtender.classList.add('hidden');
            btnAtender.classList.remove('flex');
        }
    }

    // Render tabla de espera
    tabla.innerHTML = '';
    if (info.lista_espera.length === 0) {
        tabla.innerHTML = '<tr><td colspan="3" class="px-6 py-12 text-center text-gray-400 italic">No hay pacientes esperando</td></tr>';
    } else {
        info.lista_espera.forEach((t, i) => {
            const isPriority = t.tipo === 'preferencial';
            const row = `
                <tr class="hover:bg-gray-50/50 transition-colors animate-fade-in" style="animation-delay: ${i*50}ms">
                    <td class="px-6 py-5">
                        <span class="font-black text-blue-600">${t.numero}</span>
                    </td>
                    <td class="px-4 py-5 font-bold text-gray-700">
                        ${t.paciente_nombre} ${t.paciente_apellido}
                        <span class="block text-[9px] font-black uppercase tracking-widest ${isPriority ? 'text-amber-500' : 'text-gray-300'}">
                            ${t.tipo}
                        </span>
                    </td>
                    <td class="px-4 py-5 text-xs text-gray-400 font-medium">
                        ${new Date(t.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </td>
                </tr>
            `;
            tabla.insertAdjacentHTML('beforeend', row);
        });
    }
}

function renderPanelsView(data) {
    const AREAS_VALIDAS = ['consulta', 'laboratorio', 'farmacia', 'imagenes'];
    AREAS_VALIDAS.forEach(key => {
        const info = data[key];
        if (!info) return;
        document.getElementById(`actual-num-${key}`).innerText = info.actual ? info.actual.numero : '--';
        document.getElementById(`actual-name-${key}`).innerText = info.actual ? `${info.actual.paciente_nombre} ${info.actual.paciente_apellido}` : '--';
        document.getElementById(`wait-count-${key}`).innerText = info.esperando_count;
    });
}

function renderGlobalWaitList(data) {
    const listBody = document.getElementById('global-wait-list');
    listBody.innerHTML = '';
    
    let allWait = [];
    const AREAS_VALIDAS = ['consulta', 'laboratorio', 'farmacia', 'imagenes'];
    AREAS_VALIDAS.forEach(key => {
        if (data[key] && data[key].lista_espera) {
            data[key].lista_espera.forEach(t => allWait.push(t));
        }
    });
    
    allWait.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

    if (allWait.length === 0) {
        listBody.innerHTML = '<tr><td colspan="6" class="px-8 py-12 text-center text-gray-400 italic">No hay pacientes esperando en ninguna área</td></tr>';
    } else {
        allWait.forEach(t => {
            const color = areas_global[t.area]?.color || '#666';
            const isPriority = t.tipo === 'preferencial';
            const row = `
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-8 py-5">
                        <span class="font-black" style="color: ${color}">${t.numero}</span>
                    </td>
                    <td class="px-6 py-5">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-500 border-b-2" style="border-color: ${color}22">
                            ${t.area}
                        </span>
                    </td>
                    <td class="px-6 py-5 font-bold text-gray-800">
                        ${t.paciente_nombre} ${t.paciente_apellido}
                        <span class="block text-[10px] font-medium text-gray-400">${t.paciente_identificacion}</span>
                    </td>
                    <td class="px-6 py-5">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${isPriority ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'}">
                            ${t.tipo}
                        </span>
                    </td>
                    <td class="px-6 py-5 text-xs text-gray-400 font-bold">
                        ${new Date(t.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex flex-col gap-2">
                            ${t.estado === 'llamado' || t.estado === 'atendido' ? `
                                <button onclick="abrirModalAtender(${t.id}, '${t.numero}')" 
                                    class="w-full py-2.5 bg-[#28A745] text-white rounded-xl text-sm font-bold shadow-md hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    Atender
                                </button>
                            ` : ''}
                            <button onclick="cancelarTurno(${t.id})" class="w-full py-2 bg-gray-50 text-[#6C757D] rounded-xl text-xs font-bold border border-gray-100 hover:bg-gray-100 transition-all">
                                Finalizar Turno
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            listBody.insertAdjacentHTML('beforeend', row);
        });
    }
}

function llamarSiguiente(area) {
    const btnId = `btn-llamar-${area}`;
    const btn = document.getElementById(btnId);
    
    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'animate-pulse');
    }

    fetch('<?php echo UrlHelper::url('api/turnos/llamar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({ area })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            reproducirBeep();
            updateState();
            if (typeof showToast === 'function') {
                showToast('Llamando al siguiente turno...', 'success');
            }
        } else {
            alert(res.message || 'No hay pacientes esperando.');
        }
    })
    .catch(err => {
        console.error("Fetch error in llamarSiguiente:", err);
        alert('Error de conexión al intentar llamar al turno.');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'animate-pulse');
        }
    });
}

function cancelarTurno(id) {
    if (!confirm('¿Seguro que desea cancelar este turno?')) return;
    fetch('<?php echo UrlHelper::url('api/turnos/cancelar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({ turno_id: id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) updateState();
        else alert(res.message);
    });
}

function setArea(area) {
    $('#modal-area').val(area);
    $('.area-btn').removeClass('border-blue-500 bg-white shadow-lg').addClass('border-gray-100 bg-gray-50');
    $(`#btn-area-${area}`).addClass('border-blue-500 bg-white shadow-lg text-blue-600').removeClass('border-gray-100 bg-gray-50');
}

function abrirModalNuevoTurno(area) {
    setArea(area);
    $('#modalTurno').removeClass('hidden').addClass('flex');
    setTimeout(() => {
        $('#modalContent').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
    }, 10);
}

function cerrarModal() {
    $('#modalContent').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
    setTimeout(() => {
        $('#modalTurno').addClass('hidden').removeClass('flex');
        $('#paciente-search').val('');
        $('#selected-paciente-id').val('');
        $('#tiene-cita').prop('checked', false);
        $('#cita-select-container').addClass('hidden');
        $('.area-btn').removeClass('border-blue-500 bg-white shadow-lg');
    }, 300);
}

function toggleCitaFlow() {
    const tieneCita = $('#tiene-cita').is(':checked');
    if (!tieneCita) {
        $('#cita-select-container').addClass('hidden');
        $('#cita-id').val('');
    } else {
        const pId = $('#selected-paciente-id').val();
        if (pId) buscarCitas(pId);
    }
}

function seleccionarPaciente(p) {
    $('#selected-paciente-id').val(p.id);
    $('#paciente-search').val(`${p.nombre} ${p.apellido}`);
    $('#search-results').addClass('hidden');

    if ($('#tiene-cita').is(':checked')) {
        buscarCitas(p.id);
    }
}

function buscarCitas(pacienteId) {
    $('#cita-select-container').removeClass('hidden');
    $('#cita-id').empty().append('<option>Buscando citas...</option>');
    
    fetch(`<?php echo UrlHelper::url('api/appointments/hoy'); ?>?paciente_id=${pacienteId}`)
        .then(r => r.json())
        .then(data => {
            const select = $('#cita-id').empty();
            if (data.length === 0) {
                select.append('<option value="">Sin citas registradas hoy</option>');
            } else {
                data.forEach(c => {
                    select.append(`<option value="${c.id}">${c.hora} - ${c.medico_nombre} ${c.medico_apellido}</option>`);
                });
            }
        });
}

function generarTurnoSubmit() {
    const data = {
        area: $('#modal-area').val(),
        paciente_id: $('#selected-paciente-id').val(),
        cita_id: $('#tiene-cita').is(':checked') ? $('#cita-id').val() : null,
        tipo: $('#tiene-cita').is(':checked') ? 'preferencial' : 'general'
    };

    if (!data.area) { alert('Por favor seleccione una área.'); return; }
    if (!data.paciente_id) { alert('Por favor seleccione un paciente.'); return; }

    $('#btn-text').addClass('hidden');
    $('#btn-loading').removeClass('hidden');

    fetch('<?php echo UrlHelper::url('api/turnos/generar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            updateState();
            cerrarModal();
        } else {
            alert(res.message);
        }
    })
    .finally(() => {
        $('#btn-text').removeClass('hidden');
        $('#btn-loading').addClass('hidden');
    });
}

function abrirModalAtender(id, numero) {
    document.getElementById('atender-turno-id').value = id;
    document.getElementById('atender-turno-numero').innerText = numero;
    const modal = document.getElementById('modalAtenderTurno');
    const content = document.getElementById('modalAtenderContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function cerrarModalAtender() {
    const content = document.getElementById('modalAtenderContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        document.getElementById('modalAtenderTurno').classList.add('hidden');
    }, 300);
}

function confirmarAtenderTurno() {
    const id = document.getElementById('atender-turno-id').value;
    const btnText = document.getElementById('btn-atender-text');
    const btnLoading = document.getElementById('btn-atender-loading');

    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');

    fetch('<?php echo UrlHelper::url('api/turnos/atendido'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>'
        },
        body: JSON.stringify({ turno_id: id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.redirect_url) {
            window.location.href = res.redirect_url;
        } else if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Error al procesar atención');
            cerrarModalAtender();
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión');
        cerrarModalAtender();
    })
    .finally(() => {
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    });
}

function showToast(message, type = 'info') {
    const colors = {
        success: '#28A745',
        error: '#DC3545',
        info: '#007BFF',
        warning: '#FFC107'
    };
    const div = document.createElement('div');
    div.style.cssText = `
        position: fixed; top: 20px; right: 20px;
        background: ${colors[type] || colors.info};
        color: white; padding: 12px 20px;
        border-radius: 8px; z-index: 9999;
        font-size: 14px; max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    `;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3500);
}

function reproducirBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); 
        gainNode.gain.setValueAtTime(0.05, audioCtx.currentTime);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.2);
    } catch(e) {}
}

</script>

<style>
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>

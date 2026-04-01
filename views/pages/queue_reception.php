<?php
use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['recepcionista', 'administrador']);

$pageTitle = 'Gestión de Turnos - HospitAll';
$activePage = 'queue_reception';
$headerTitle = 'Gestión de Turnos';
$headerSubtitle = 'Control de flujo de pacientes en tiempo real.';

include __DIR__ . '/../layout/header.php';

// Asegurar que estadoSalas exista
if (!isset($estadoSalas)) {
    $turnosService = new \App\Services\QueueService($pdo);
    $estadoSalas = $turnosService->getEstadoSalas();
}

$userRole = $_SESSION['user_role'] ?? '';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php
    $areas = [
        'consulta' => ['nombre' => 'Consulta Médica', 'color' => '#007BFF', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'laboratorio' => ['nombre' => 'Laboratorio', 'color' => '#28A745', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
        'farmacia' => ['nombre' => 'Farmacia', 'color' => '#FD7E14', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
        'imagenes' => ['nombre' => 'Imágenes Médicas', 'color' => '#6366F1', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h14a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z']
    ];

    foreach ($areas as $key => $data):
        $infoArea = $estadoSalas[$key] ?? ['actual' => null, 'esperando_count' => 0];
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
        <div class="p-4 flex items-center justify-between border-b bg-gray-50">
            <h4 class="font-bold text-gray-700 flex items-center">
                <span class="w-2 h-2 rounded-full mr-2" style="background-color: <?php echo $data['color']; ?>"></span>
                <?php echo $data['nombre']; ?>
            </h4>
            <span class="text-xs font-semibold px-2 py-1 bg-white border rounded-full text-gray-500">
                <?php echo $infoArea['esperando_count']; ?> en espera
            </span>
        </div>
        <div class="p-6 flex-1 flex flex-col items-center justify-center text-center">
            <p class="text-[10px] text-uppercase tracking-wider text-gray-400 font-bold mb-2">TURNO ACTUAL</p>
            <div id="actual-<?php echo $key; ?>" class="text-6xl font-black mb-4 transition-all duration-500 transform" style="color: <?php echo $data['color']; ?>">
                <?php echo $infoArea['actual']['numero'] ?? '--'; ?>
            </div>
            <p class="text-sm text-gray-700 font-bold mb-6 h-5 truncate w-full">
                <?php echo $infoArea['actual'] ? htmlspecialchars($infoArea['actual']['paciente_nombre'] . ' ' . $infoArea['actual']['paciente_apellido']) : '<span class="text-gray-300 font-normal italic">Ninguno</span>'; ?>
            </p>
            
            <div class="grid <?php echo ($userRole === 'recepcionista') ? 'grid-cols-1' : 'grid-cols-2'; ?> gap-3 w-full mt-auto">
                <button onclick="abrirModalNuevoTurno('<?php echo $key; ?>')" class="w-full py-3 bg-gray-50 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-100 border border-gray-200 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nuevo
                </button>
                <?php if ($userRole !== 'recepcionista'): ?>
                <button onclick="llamarSiguiente('<?php echo $key; ?>')" class="w-full py-3 text-white rounded-xl text-sm font-bold shadow-lg hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" style="background-color: <?php echo $data['color']; ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 5v14l11-7L5 5z"></path></svg>
                    Llamar
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Listado General de Espera de Hoy</h3>
        <div class="flex gap-2">
            <button onclick="window.location.reload()" class="p-2 text-gray-500 hover:text-[#007BFF]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="turnosTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] border-b">
                    <th class="py-4">Turno</th>
                    <th class="py-4">Área</th>
                    <th class="py-4">Paciente</th>
                    <th class="py-4">Tipo</th>
                    <th class="py-4">Generado</th>
                    <th class="py-4">Estado</th>
                    <th class="py-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estadoSalas as $key => $sala): 
                      foreach ($sala['lista_espera'] as $turno): ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="py-4 font-black">
                            <span style="color: <?php echo $areas[$turno['area']]['color']; ?>">
                                <?php echo $turno['numero']; ?>
                            </span>
                        </td>
                        <td class="py-4 text-sm font-medium text-gray-600">
                            <?php echo ucfirst($turno['area']); ?>
                        </td>
                        <td class="py-4 font-medium text-gray-800">
                            <?php echo htmlspecialchars($turno['paciente_nombre'] . ' ' . $turno['paciente_apellido']); ?>
                            <div class="text-[10px] text-gray-400"><?php echo $turno['paciente_identificacion']; ?></div>
                        </td>
                        <td class="py-4">
                            <?php if ($turno['tipo'] === 'preferencial'): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200 uppercase">Preferencial</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">General</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 text-xs text-gray-500">
                            <?php echo date('H:i', strtotime($turno['created_at'])); ?>
                        </td>
                        <td class="py-4">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase">
                                <?php echo $turno['estado']; ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <button onclick="cancelarTurno(<?php echo $turno['id']; ?>)" class="text-red-400 hover:text-red-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Turno -->
<div id="modalTurno" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Generar Nuevo Turno</h3>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Área del Hospital</label>
                <select id="modal-area" class="w-full px-4 py-2 border rounded-xl outline-none focus:ring-2 focus:ring-[#007BFF]">
                    <option value="consulta">Consulta Médica</option>
                    <option value="laboratorio">Laboratorio</option>
                    <option value="farmacia">Farmacia</option>
                    <option value="imagenes">Imágenes Médicas</option>
                </select>
            </div>

            <div id="status-selection" class="flex gap-2">
                <button type="button" onclick="setPriority('general')" id="btn-priority-general" class="flex-1 py-2 rounded-xl border-2 border-blue-500 bg-blue-50 text-blue-700 font-bold text-sm transition-all">General</button>
                <button type="button" onclick="setPriority('preferencial')" id="btn-priority-pref" class="flex-1 py-2 rounded-xl border-2 border-gray-100 bg-gray-50 text-gray-400 font-bold text-sm transition-all">Preferencial</button>
            </div>
            <input type="hidden" id="turno-prioridad" value="general">

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                <div>
                    <p class="font-bold text-gray-800">¿Tiene Cita Hoy?</p>
                    <p class="text-xs text-gray-500">Los pacientes con cita tienen prioridad.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="tiene-cita" class="sr-only peer" onchange="toggleCitaFlow()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-[#28A745]"></div>
                </label>
            </div>

            <div id="paciente-search-container">
                <label class="block text-sm font-bold text-gray-700 mb-2">Buscar Paciente</label>
                <div class="relative">
                    <input type="text" id="paciente-search" placeholder="Nombre o identificación..." class="w-full pl-10 pr-4 py-2 border rounded-xl outline-none focus:ring-2 focus:ring-[#007BFF]">
                    <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <div id="search-results" class="mt-2 max-h-40 overflow-y-auto border rounded-xl hidden bg-white shadow-lg z-10"></div>
            </div>

            <div id="cita-select-container" class="hidden">
                 <label class="block text-sm font-bold text-gray-700 mb-2">Seleccionar Cita</label>
                 <select id="cita-id" class="w-full px-4 py-2 border rounded-xl outline-none focus:ring-2 focus:ring-[#007BFF]">
                     <option value="">Esperando búsqueda de paciente...</option>
                 </select>
            </div>

            <input type="hidden" id="selected-paciente-id">
        </div>
        <div class="p-6 bg-gray-50 text-right">
            <button onclick="generarTurnoSubmit()" class="w-full py-3 bg-[#007BFF] text-white rounded-xl font-bold shadow-lg hover:bg-blue-700 transition-all flex items-center justify-center">
                <span id="btn-text">Generar Turno</span>
                <div id="btn-loading" class="hidden ml-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            </button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#turnosTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        responsive: true,
        order: [[0, 'asc']]
    });

    let searchTimeout = null;
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
                        results.append('<div class="p-3 text-sm text-gray-500">No hay resultados</div>');
                        return;
                    }
                    data.forEach(p => {
                        const item = $(`<div class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-0 font-medium text-sm">
                            ${p.nombre} ${p.apellido} <span class="text-xs text-gray-400">(${p.identificacion})</span>
                        </div>`);
                        item.on('click', () => seleccionarPaciente(p));
                        results.append(item);
                    });
                });
        }, 300);
    });
});

function toggleCitaFlow() {
    const tieneCita = $('#tiene-cita').is(':checked');
    if (tieneCita) {
        // Al seleccionar cita, habilitamos el select de citas
        // pero necesitamos que primero busquen al paciente
    } else {
        $('#cita-select-container').addClass('hidden');
        $('#cita-id').val('');
    }
}

function seleccionarPaciente(p) {
    $('#selected-paciente-id').val(p.id);
    $('#paciente-search').val(`${p.nombre} ${p.apellido}`);
    $('#search-results').addClass('hidden');

    if ($('#tiene-cita').is(':checked')) {
        // Buscar citas de hoy para este paciente
        $('#cita-select-container').removeClass('hidden');
        $('#cita-id').empty().append('<option>Buscando citas...</option>');
        
        // Asumiendo que tenemos un endpoint para ver citas de hoy de un paciente
        // Si no lo tenemos, lo improvisamos con la búsqueda de hoy
        fetch(`<?php echo UrlHelper::url('api/appointments/hoy'); ?>?paciente_id=${p.id}`)
            .then(r => r.json())
            .then(data => {
                const select = $('#cita-id').empty();
                if (data.length === 0) {
                    select.append('<option value="">Sin citas para hoy</option>');
                    alert('Este paciente no tiene citas registradas para hoy.');
                } else {
                    data.forEach(c => {
                        select.append(`<option value="${c.id}">${c.hora} - ${c.medico_nombre} ${c.medico_apellido}</option>`);
                    });
                }
            });
    }
}

function abrirModalNuevoTurno(area) {
    $('#modal-area').val(area);
    $('#modalTurno').removeClass('hidden').addClass('flex');
}

function cerrarModal() {
    $('#modalTurno').addClass('hidden').removeClass('flex');
    $('#paciente-search').val('');
    $('#selected-paciente-id').val('');
    $('#tiene-cita').prop('checked', false);
    $('#cita-select-container').addClass('hidden');
    setPriority('general');
}

function setPriority(tipo) {
    $('#turno-prioridad').val(tipo);
    if (tipo === 'preferencial') {
        $('#btn-priority-pref').removeClass('bg-gray-50 text-gray-400 border-gray-100').addClass('bg-amber-50 text-amber-700 border-amber-500');
        $('#btn-priority-general').removeClass('bg-blue-50 text-blue-700 border-blue-500').addClass('bg-gray-50 text-gray-400 border-gray-100');
    } else {
        $('#btn-priority-general').removeClass('bg-gray-50 text-gray-400 border-gray-100').addClass('bg-blue-50 text-blue-700 border-blue-500');
        $('#btn-priority-pref').removeClass('bg-amber-50 text-amber-700 border-amber-500').addClass('bg-gray-50 text-gray-400 border-gray-100');
    }
}

function generarTurnoSubmit() {
    const data = {
        area: $('#modal-area').val(),
        paciente_id: $('#selected-paciente-id').val(),
        cita_id: $('#tiene-cita').is(':checked') ? $('#cita-id').val() : null,
        tipo: $('#turno-prioridad').val()
    };

    if (!data.paciente_id) {
        alert('Por favor seleccione un paciente.');
        return;
    }

    $('#btn-text').addClass('hidden');
    $('#btn-loading').removeClass('hidden');

    fetch('<?php echo UrlHelper::url('api/turnos/generar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(`Turno generado: ${res.turno.numero} (${res.turno.tipo})`);
            window.location.reload();
        } else {
            alert(res.message);
        }
    })
    .finally(() => {
        $('#btn-text').removeClass('hidden');
        $('#btn-loading').addClass('hidden');
    });
}

function llamarSiguiente(area) {
    fetch('<?php echo UrlHelper::url('api/turnos/llamar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>'
        },
        body: JSON.stringify({ area })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            reproducirBeep();
            animarCambio(area, res.turno.numero);
            alert(`Llamando al turno ${res.turno.numero}: ${res.turno.paciente}`);
            // No recargamos para no romper la UX, pero podemos actualizar el DOM
            setTimeout(() => window.location.reload(), 2000);
        } else {
            alert(res.message);
        }
    });
}

function cancelarTurno(id) {
    if (!confirm('¿Seguro que desea cancelar este turno?')) return;
    fetch('<?php echo UrlHelper::url('api/turnos/cancelar'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>'
        },
        body: JSON.stringify({ turno_id: id })
    })
    .then(() => window.location.reload());
}

function reproducirBeep() {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);

    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); 
    gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);

    oscillator.start();
    oscillator.stop(audioCtx.currentTime + 0.3);
}

function animarCambio(area, numero) {
    const el = document.getElementById(`actual-${area}`);
    el.innerText = numero;
    el.classList.add('scale-125', 'font-black');
    setTimeout(() => el.classList.remove('scale-125'), 500);
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

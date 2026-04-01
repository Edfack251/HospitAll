<?php
use App\Controllers\HospitalizationController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\UrlHelper;
use App\Repositories\EmergencyRepository;
use App\Repositories\AppointmentRepository;

AuthHelper::checkRole(['medico', 'enfermera', 'administrador', 'recepcionista']);

$controller = new HospitalizationController($pdo);
$data = $controller->index();
$internamientos = $data['internamientos'];

// Prepara datos para el modal de nuevo internamiento
$emergenciaRepo = new EmergencyRepository($pdo);
$appointmentsRepo = new AppointmentRepository($pdo);

$emergenciasActivas = $emergenciaRepo->getEmergenciasParaFlow();
$citasHoy = $appointmentsRepo->getCitasHoy();

$pageTitle = 'Hospitalización - HospitAll';
$activePage = 'hospitalization';
$headerTitle = 'Módulo de Hospitalización';
$headerSubtitle = 'Gestión y monitoreo de pacientes internados.';

include __DIR__ . '/../layout/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <h3 class="text-xl font-bold text-[#212529]">Pacientes Internados</h3>
    <?php if (in_array($_SESSION['user_role'], ['medico', 'administrador'])): ?>
    <button onclick="openModal('modalNuevoInternamiento')"
        class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Nuevo Internamiento
    </button>
    <?php endif; ?>
</div>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="internamientosTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Paciente</th>
                <th class="py-4">Ubicación</th>
                <th class="py-4">Médico Responsable</th>
                <th class="py-4">Ingreso</th>
                <th class="py-4">Estado</th>
                <th class="py-4">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($internamientos as $i): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4">
                        <div class="font-medium text-[#212529]">
                            <?php echo htmlspecialchars($i['paciente_nombre'] . ' ' . $i['paciente_apellido']); ?>
                        </div>
                        <div class="text-xs text-[#6C757D]">
                            Cédula: <?php echo htmlspecialchars($i['paciente_cedula']); ?>
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm font-semibold text-[#007BFF]">
                            Cama <?php echo htmlspecialchars($i['cama_numero']); ?>
                        </div>
                        <div class="text-xs text-[#6C757D]">
                            Hab. <?php echo htmlspecialchars($i['habitacion_numero']); ?>
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm">
                            Dr. <?php echo htmlspecialchars($i['medico_nombre'] . ' ' . $i['medico_apellido']); ?>
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm">
                            <?php echo date('d/m/Y H:i', strtotime($i['fecha_ingreso'])); ?>
                        </div>
                        <div class="text-xs text-blue-600 font-medium">
                            <?php echo $i['dias_internado']; ?> días internado
                        </div>
                    </td>
                    <td class="py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            Activo
                        </span>
                    </td>
                    <td class="py-4">
                        <button onclick="verDetalleInternamiento(<?php echo $i['id']; ?>)"
                            class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition-colors font-medium border border-gray-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            Ver detalle
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Nuevo Internamiento -->
<div id="modalNuevoInternamiento" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden transition-all transform scale-95 opacity-0 duration-300" id="modalNuevoInternamientoContent">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-[#F8F9FA]">
            <h3 class="text-lg font-bold text-[#212529]">Registrar Nuevo Internamiento</h3>
            <button onclick="closeModal('modalNuevoInternamiento')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="formInternar" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Origen</label>
                    <select id="origen" name="origen" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 transition-all outline-none" required onchange="toggleOrigenFields()">
                        <option value="emergencia">Emergencia</option>
                        <option value="consulta">Consulta Externa</option>
                    </select>
                </div>
                <div id="divEmergencia">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Emergencia Activa</label>
                    <select name="emergencia_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        <option value="">Seleccione el paciente...</option>
                        <?php foreach ($emergenciasActivas as $e): ?>
                            <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['paciente_nombre'] . ' ' . $e['paciente_apellido']); ?> (<?php echo $e['nivel_triage']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="divConsulta" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cita del Día</label>
                    <select name="cita_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        <option value="">Seleccione el paciente...</option>
                        <?php foreach ($citasHoy as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['paciente_nombre'] . ' ' . $c['paciente_apellido']); ?> (<?php echo $c['hora']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Cama Disponible</label>
                <select id="cama_id" name="cama_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 transition-all outline-none" required>
                    <option value="">Cargando camas...</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo de Internamiento</label>
                <textarea name="motivo" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 transition-all outline-none" required placeholder="Describa la razón del internamiento..."></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Diagnóstico de Ingreso</label>
                <textarea name="diagnostico_ingreso" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="Opcional: diagnóstico inicial..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('modalNuevoInternamiento')" class="px-6 py-2 rounded-lg font-semibold text-gray-600 hover:bg-gray-100 transition-all">Cancelar</button>
                <button type="submit" class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all">Internar Paciente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Detalle de Internamiento -->
<div id="modalDetalle" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden transition-all transform scale-95 opacity-0 duration-300 flex flex-col" id="modalDetalleContent">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-[#F8F9FA]">
            <h3 class="text-lg font-bold text-[#212529]" id="detallePacienteNombre">Detalle del Paciente</h3>
            <button onclick="closeModal('modalDetalle')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-1">
            <div id="detalleContent">
                <!-- Se cargará vía AJAX -->
                <div class="flex justify-center p-10">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-[#F8F9FA]" id="detalleAcciones">
            <!-- Botones dinámicos según el rol -->
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#internamientosTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        });

        loadCamasDisponibles();
    });

    function openModal(id) {
        $(`#${id}`).removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $(`#${id}Content`).removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
        
        if (id === 'modalNuevoInternamiento') {
            loadCamasDisponibles();
        }
    }

    function closeModal(id) {
        $(`#${id}Content`).removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $(`#${id}`).removeClass('flex').addClass('hidden');
        }, 300);
    }

    function toggleOrigenFields() {
        const origen = $('#origen').val();
        if (origen === 'emergencia') {
            $('#divEmergencia').removeClass('hidden');
            $('#divConsulta').addClass('hidden');
        } else {
            $('#divEmergencia').addClass('hidden');
            $('#divConsulta').removeClass('hidden');
        }
    }

    async function loadCamasDisponibles() {
        try {
            const res = await fetch('<?php echo UrlHelper::url('api/hospitalizacion/camas-disponibles'); ?>');
            const data = await res.json();
            if (data.success) {
                const select = $('#cama_id');
                select.empty();
                if (data.camas.length === 0) {
                    select.append('<option value="">No hay camas disponibles</option>');
                } else {
                    data.camas.forEach(c => {
                        select.append(`<option value="${c.id}">Hab. ${c.habitacion_numero} - Cama ${c.numero} (${c.habitacion_tipo})</option>`);
                    });
                }
            }
        } catch (e) {
            console.error(e);
        }
    }

    $('#formInternar').on('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        // Simple conversion a objeto JSON
        const jsonData = {};
        formData.forEach((value, key) => jsonData[key] = value);

        try {
            const res = await fetch('<?php echo UrlHelper::url('api/hospitalizacion/internar'); ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo CsrfHelper::generateToken(); ?>'
                },
                body: JSON.stringify(jsonData)
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Error al internar al paciente');
            }
        } catch (e) {
            alert('Error de conexión');
        }
    });

    async function verDetalleInternamiento(id) {
        openModal('modalDetalle');
        $('#detalleContent').html('<div class="flex justify-center p-10"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div></div>');
        
        try {
            const res = await fetch(`<?php echo UrlHelper::url('api/hospitalizacion/detalle'); ?>?internamiento_id=${id}`);
            const data = await res.json();
            if (data.success) {
                renderDetalle(data.detalle);
            } else {
                $('#detalleContent').html(`<div class="text-red-600 p-4">${data.error}</div>`);
            }
        } catch (e) {
            $('#detalleContent').html('<div class="text-red-600 p-4">Error al cargar el detalle.</div>');
        }
    }

    function renderDetalle(d) {
        $('#detallePacienteNombre').text(`${d.paciente_nombre} ${d.paciente_apellido}`);
        
        let html = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 text-sm">
                    <p class="text-blue-600 font-bold mb-1 uppercase tracking-wider text-[10px]">Información Paciente</p>
                    <p class="font-bold text-[#212529]">${d.paciente_nombre} ${d.paciente_apellido}</p>
                    <p class="text-gray-600">ID: ${d.paciente_identificacion}</p>
                </div>
                <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 text-sm">
                    <p class="text-indigo-600 font-bold mb-1 uppercase tracking-wider text-[10px]">Ubicación</p>
                    <p class="font-bold text-[#212529]">Habitación ${d.habitacion_numero} - Cama ${d.cama_numero}</p>
                    <p class="text-gray-600">${d.habitacion_tipo}</p>
                </div>
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 text-sm">
                    <p class="text-emerald-600 font-bold mb-1 uppercase tracking-wider text-[10px]">Ingreso</p>
                    <p class="font-bold text-[#212529]">${new Date(d.fecha_ingreso).toLocaleString()}</p>
                    <p class="text-gray-600">Vía: ${d.origen.toUpperCase()}</p>
                </div>
            </div>

            <div class="mb-6">
                <h4 class="font-bold text-gray-800 mb-2">Motivo y Diagnóstico</h4>
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-sm">
                    <p class="mb-2"><strong>Motivo:</strong> ${d.motivo_internamiento}</p>
                    <p><strong>Diagnóstico Ingreso:</strong> ${d.diagnostico_ingreso || 'No registrado'}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                        <span>Últimas Rondas</span>
                        <?php if (in_array($_SESSION['user_role'], ['enfermera', 'administrador'])): ?>
                        <button onclick="registrarRonda(${d.id})" class="text-blue-600 text-xs hover:underline">+ Nueva</button>
                        <?php endif; ?>
                    </h4>
                    <div class="space-y-4">
                        ${d.rondas.length ? d.rondas.slice(0, 3).map(r => `
                            <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm text-xs">
                                <div class="flex justify-between mb-2">
                                    <span class="font-bold">${new Date(r.created_at).toLocaleString()}</span>
                                    <span class="text-gray-500">${r.enfermera_nombre}</span>
                                </div>
                                <p class="text-[#6C757D] mb-2">${r.observaciones || 'Sin observaciones'}</p>
                                <div class="grid grid-cols-2 gap-2 text-[10px]">
                                    <span class="bg-gray-100 px-2 py-1 rounded">PA: ${r.presion_arterial || '-'}</span>
                                    <span class="bg-gray-100 px-2 py-1 rounded">FC: ${r.frecuencia_cardiaca || '-'}</span>
                                    <span class="bg-gray-100 px-2 py-1 rounded">Temp: ${r.temperatura || '-'}°</span>
                                    <span class="bg-gray-100 px-2 py-1 rounded">SatO2: ${r.saturacion_oxigeno || '-'}%</span>
                                </div>
                            </div>
                        `).join('') : '<p class="text-gray-500 text-sm italic">No hay rondas registradas.</p>'}
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                        <span>Evolución Médica</span>
                        <?php if (in_array($_SESSION['user_role'], ['medico', 'administrador'])): ?>
                        <button onclick="registrarEvolucion(${d.id})" class="text-blue-600 text-xs hover:underline">+ Nueva</button>
                        <?php endif; ?>
                    </h4>
                    <div class="space-y-4">
                        ${d.evoluciones.length ? d.evoluciones.slice(0, 3).map(e => `
                            <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm text-xs">
                                <div class="flex justify-between mb-2">
                                    <span class="font-bold">${new Date(e.created_at).toLocaleString()}</span>
                                    <span class="text-gray-500">Dr. ${e.medico_nombre}</span>
                                </div>
                                <p class="text-[#212529] mb-1">${e.evolucion}</p>
                                ${e.indicaciones ? `<p class="text-blue-600"><strong>Indicaciones:</strong> ${e.indicaciones}</p>` : ''}
                            </div>
                        `).join('') : '<p class="text-gray-500 text-sm italic">No hay evoluciones registradas.</p>'}
                    </div>
                </div>
            </div>
        `;

        $('#detalleContent').html(html);

        let accionesHtml = `
            <button onclick="closeModal('modalDetalle')" class="px-6 py-2 rounded-lg font-semibold text-gray-600 hover:bg-gray-100 transition-all">Cerrar</button>
            <?php if (in_array($_SESSION['user_role'], ['medico', 'administrador'])): ?>
            <button onclick="darAlta(${d.id})" class="bg-[#DC3545] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-red-700 transition-all">Dar Alta</button>
            <?php endif; ?>
        `;
        $('#detalleAcciones').html(accionesHtml);
    }

    function registrarRonda(internamiento_id) {
        const obs = prompt("Ingrese observaciones de la ronda:");
        if (obs === null) return;
        
        fetch('<?php echo UrlHelper::url('api/hospitalizacion/ronda'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                internamiento_id: internamiento_id,
                observaciones: obs
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                verDetalleInternamiento(internamiento_id);
            } else {
                alert(data.error);
            }
        });
    }

    function registrarEvolucion(internamiento_id) {
        const nota = prompt("Ingrese la nota de evolución médica:");
        if (nota === null) return;
        const ind = prompt("Indicaciones médicas (opcional):");
        
        fetch('<?php echo UrlHelper::url('api/hospitalizacion/evolucion'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                internamiento_id: internamiento_id,
                evolucion: nota,
                indicaciones: ind
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                verDetalleInternamiento(internamiento_id);
            } else {
                alert(data.error);
            }
        });
    }

    function darAlta(internamiento_id) {
        const obs = prompt("Observaciones para el alta médica:");
        if (!obs) return;

        if (confirm("¿Está seguro de dar el alta a este paciente? La cama pasará a estado de limpieza.")) {
            fetch('<?php echo UrlHelper::url('api/hospitalizacion/alta'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    internamiento_id: internamiento_id,
                    observaciones_alta: obs
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            });
        }
    }
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

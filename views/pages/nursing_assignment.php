<?php
use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['administrador', 'recepcionista', 'medico']);

$pageTitle = 'Asignación de Enfermería - HospitAll';
$activePage = 'nursing_assignment';
$headerTitle = 'Asignación de Pacientes a Enfermería';
$headerSubtitle = 'Delegue la atención y monitoreo de pacientes al personal de enfermería.';

include __DIR__ . '/../layout/header.php';

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Columna Izquierda: Internamientos Sin Asignar -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Pacientes Internados Sin Asignar</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table id="internamientosTable" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-[10px] uppercase tracking-widest border-b border-gray-50">
                            <th class="pb-4 font-black">Paciente</th>
                            <th class="pb-4 font-black">Habitación / Cama</th>
                            <th class="pb-4 font-black">Médico Responsable</th>
                            <th class="pb-4 font-black">Días Internado</th>
                            <th class="pb-4 font-black text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php foreach ($internamientosSinAsignar as $i): ?>
                            <tr class="group border-b border-gray-50 last:border-0 hover:bg-blue-50/30 transition-all">
                                <td class="py-4">
                                    <div class="font-bold text-gray-800"><?php echo htmlspecialchars($i['paciente_nombre'] . ' ' . $i['paciente_apellido']); ?></div>
                                    <div class="text-[10px] text-gray-400 font-medium italic"><?php echo ucfirst($i['origen']); ?></div>
                                </td>
                                <td class="py-4">
                                    <span class="px-2 py-1 bg-purple-50 text-purple-700 rounded-lg text-[10px] font-black uppercase tracking-tighter">
                                        Hab. <?php echo htmlspecialchars($i['habitacion_numero']); ?> - Cama <?php echo htmlspecialchars($i['cama_numero']); ?>
                                    </span>
                                </td>
                                <td class="py-4 text-gray-500 font-medium italic">Dr. <?php echo htmlspecialchars($i['medico_apellido']); ?></td>
                                <td class="py-4 font-bold text-gray-600">
                                    <?php 
                                        $diff = date_diff(date_create($i['fecha_ingreso']), date_create('now'));
                                        echo $diff->format('%a') . ' días';
                                    ?>
                                </td>
                                <td class="py-4 text-right">
                                    <button onclick="openAssignModal(<?php echo $i['id']; ?>, '<?php echo htmlspecialchars($i['paciente_nombre'] . ' ' . $i['paciente_apellido']); ?>')" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-black uppercase transition-all shadow-md shadow-blue-100">
                                        Asignar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($internamientosSinAsignar)): ?>
                            <tr><td colspan="5" class="py-12 text-center text-gray-400 font-medium italic">No hay pacientes internados pendientes de asignación.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Asignaciones Activas -->
    <div class="space-y-6">
        <div class="bg-gray-800 p-6 rounded-2xl shadow-xl text-white">
            <h3 class="font-black uppercase tracking-widest text-sm mb-6 flex items-center">
                <span class="w-2 h-2 bg-green-400 rounded-full mr-3 animate-pulse"></span>
                Asignaciones Activas
            </h3>
            <div id="asignacionesList" class="space-y-4">
                <?php foreach ($asignaciones as $asig): ?>
                    <div class="bg-white/5 border border-white/10 p-4 rounded-xl flex justify-between items-center group hover:bg-white/10 transition-all">
                        <div>
                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">
                                Hab. <?php echo htmlspecialchars($asig['habitacion_numero']); ?> - Cama <?php echo htmlspecialchars($asig['cama_numero']); ?>
                            </p>
                            <h4 class="text-sm font-bold"><?php echo htmlspecialchars($asig['paciente_nombre'] . ' ' . $asig['paciente_apellido']); ?></h4>
                            <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold">Enfermera: <span class="text-white"><?php echo htmlspecialchars($asig['enfermera_nombre'] . ' ' . $asig['enfermera_apellido']); ?></span></p>
                        </div>
                        <button onclick="removeAsignacion(<?php echo $asig['id']; ?>)" class="text-gray-500 hover:text-red-400 transition-colors p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($asignaciones)): ?>
                    <div class="py-8 text-center text-gray-500 italic text-sm">Sin asignaciones registradas.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Asignación -->
<div id="assignModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="bg-blue-600 p-8 text-white relative">
            <h3 class="text-2xl font-black uppercase tracking-tight mb-2">Asignar Enfermera</h3>
            <p class="text-blue-100 text-sm font-medium opacity-80" id="modalPacienteName"></p>
            <button onclick="closeAssignModal()" class="absolute top-8 right-8 text-white/50 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-8">
            <input type="hidden" id="modalInternamientoId">
            <div class="mb-6">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Seleccionar Personal</label>
                <div class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                    <?php if (empty($enfermeras)): ?>
                        <p class="text-xs text-gray-400 italic">No hay enfermeras disponibles.</p>
                    <?php endif; ?>
                    <?php foreach ($enfermeras as $enf): ?>
                        <label class="flex items-center p-4 bg-gray-50 rounded-2xl cursor-pointer hover:bg-blue-50 border-2 border-transparent hover:border-blue-200 transition-all group">
                            <input type="radio" name="enfermera_id" value="<?php echo $enf['id']; ?>" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <div class="ml-4">
                                <span class="block text-sm font-bold text-gray-800 group-hover:text-blue-700"><?php echo htmlspecialchars($enf['nombre'] . ' ' . $enf['apellido']); ?></span>
                                <span class="block text-[10px] text-gray-400 font-black uppercase">Enfermera Registrada</span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button onclick="submitAssignment()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl transition-all shadow-xl shadow-blue-200 uppercase tracking-widest text-sm">
                Confirmar Asignación
            </button>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?php echo $csrfToken; ?>';

function openAssignModal(internamientoId, pacienteName) {
    document.getElementById('modalInternamientoId').value = internamientoId;
    document.getElementById('modalPacienteName').textContent = 'Paciente: ' + pacienteName;
    document.getElementById('assignModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function submitAssignment() {
    const internamientoId = document.getElementById('modalInternamientoId').value;
    const enfermeraId = document.querySelector('input[name="enfermera_id"]:checked')?.value;

    if (!enfermeraId) {
        alert('Por favor seleccione una enfermera');
        return;
    }

    fetch('<?php echo UrlHelper::url('api/enfermeria/asignar-paciente'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({
            internamiento_id: internamientoId,
            enfermera_id: enfermeraId
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('Error: ' + (res.error || 'No se pudo completar la asignación'));
        }
    })
    .catch(() => alert('Error de conexión'));
}

function removeAsignacion(id) {
    if (!confirm('¿Desea eliminar esta asignación?')) return;

    fetch('<?php echo UrlHelper::url('api/enfermeria/eliminar-asignacion'); ?>?id=' + id, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': CSRF_TOKEN
        }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('Error al eliminar asignación');
        }
    })
    .catch(() => alert('Error de conexión'));
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>

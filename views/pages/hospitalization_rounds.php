<?php
use App\Controllers\HospitalizationController;
use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['enfermera', 'administrador']);

$controller = new HospitalizationController($pdo);
$data = $controller->showRondas();
$internamientos = $data['internamientos'];

// En un sistema real, filtraríamos por asignaciones_enfermeria
// Aquí usamos todos los activos como fallback según requerimiento.

$pageTitle = 'Rondas de Enfermería - HospitAll';
$activePage = 'hospitalization_rounds';
$headerTitle = 'Gestión de Rondas';
$headerSubtitle = 'Registro de signos vitales y seguimiento de pacientes internados.';

include __DIR__ . '/../layout/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <?php foreach ($internamientos as $i): ?>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h4 class="font-bold text-[#212529]"><?php echo htmlspecialchars($i['paciente_nombre'] . ' ' . $i['paciente_apellido']); ?></h4>
                    <p class="text-xs text-[#6C757D]">Cédula: <?php echo htmlspecialchars($i['paciente_cedula']); ?></p>
                </div>
                <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-[10px] font-bold">
                    Cama <?php echo htmlspecialchars($i['cama_numero']); ?>
                </span>
            </div>
            
            <div class="flex-1 mb-4">
                <p class="text-xs text-gray-500 mb-1">Último Ingreso:</p>
                <p class="text-sm font-medium"><?php echo date('d/m/Y H:i', strtotime($i['fecha_ingreso'])); ?></p>
            </div>

            <div class="flex gap-2">
                <button onclick="abrirModalRonda(<?php echo $i['id']; ?>, '<?php echo htmlspecialchars($i['paciente_nombre'] . ' ' . $i['paciente_apellido']); ?>')"
                    class="flex-1 bg-[#007BFF] text-white py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all">
                    Nueva Ronda
                </button>
                <button onclick="verHistorial(<?php echo $i['id']; ?>)"
                    class="px-4 py-2 bg-gray-50 text-gray-600 border border-gray-200 rounded-xl text-sm hover:bg-gray-100 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Nueva Ronda -->
<div id="modalRonda" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transition-all transform scale-95 opacity-0 duration-300" id="modalRondaContent">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-[#F8F9FA]">
            <div>
                <h3 class="text-lg font-bold text-[#212529]">Registrar Ronda</h3>
                <p class="text-xs text-gray-500" id="rondaPacienteNombre"></p>
            </div>
            <button onclick="closeModal('modalRonda')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="formRonda" class="p-6">
            <input type="hidden" name="internamiento_id" id="internamiento_id_input">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Presión Arterial</label>
                    <input type="text" name="presion_arterial" placeholder="120/80" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Frec. Cardíaca (bpm)</label>
                    <input type="number" name="frecuencia_cardiaca" placeholder="70" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Temperatura (°C)</label>
                    <input type="number" step="0.1" name="temperatura" placeholder="36.5" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sat. Oxígeno (%)</label>
                    <input type="number" step="0.1" name="saturacion_oxigeno" placeholder="98" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Medicamentos Administrados</label>
                <textarea name="medicamentos_administrados" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Liste medicamentos y dosis..."></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Observaciones / Evolución de Enfermería</label>
                <textarea name="observaciones" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Estado general del paciente..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('modalRonda')" class="px-6 py-2 rounded-lg font-semibold text-gray-600 hover:bg-gray-100 transition-all text-sm">Cancelar</button>
                <button type="submit" class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all text-sm">Guardar Ronda</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        $(`#${id}`).removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $(`#${id}Content`).removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
    }

    function closeModal(id) {
        $(`#${id}Content`).removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $(`#${id}`).removeClass('flex').addClass('hidden');
        }, 300);
    }

    function abrirModalRonda(id, nombre) {
        $('#internamiento_id_input').val(id);
        $('#rondaPacienteNombre').text(nombre);
        $('#formRonda')[0].reset();
        openModal('modalRonda');
    }

    function verHistorial(id) {
        window.location.href = `<?php echo UrlHelper::url('hospitalization'); ?>?internamiento_id=${id}&tab=rondas`;
    }

    $('#formRonda').on('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const jsonData = {};
        formData.forEach((value, key) => jsonData[key] = value);

        try {
            const res = await fetch('<?php echo UrlHelper::url('api/hospitalizacion/ronda'); ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo \App\Helpers\CsrfHelper::generateToken(); ?>'
                },
                body: JSON.stringify(jsonData)
            });
            const result = await res.json();
            if (result.success) {
                alert('Ronda registrada correctamente');
                closeModal('modalRonda');
            } else {
                alert(result.error || 'Error al registrar ronda');
            }
        } catch (e) {
            alert('Error de conexión');
        }
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

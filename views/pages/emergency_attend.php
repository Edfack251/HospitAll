<?php
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\UrlHelper;
use App\Helpers\PrivacyHelper;

AuthHelper::checkRole(['medico', 'administrador']);

$triageColors = [
    'Rojo' => '#DC3545',
    'Naranja' => '#FD7E14',
    'Amarillo' => '#FFC107',
    'Verde' => '#28A745'
];
$triageBg = [
    'Rojo' => 'bg-red-100 text-red-800',
    'Naranja' => 'bg-orange-100 text-orange-800',
    'Amarillo' => 'bg-yellow-100 text-yellow-800',
    'Verde' => 'bg-green-100 text-green-800'
];
$triaje = $emergencia['nivel_triage'] ?? 'Verde';
$badgeClass = $triageBg[$triaje] ?? 'bg-gray-100 text-gray-800';

$pageTitle = 'Atender emergencia - HospitAll';
$activePage = 'emergency_attend';
$headerTitle = 'Atención de emergencia';
$headerSubtitle = 'Registro de evaluación clínica y cierre.';

$csrfToken = CsrfHelper::generateToken();
$registrarUrl = UrlHelper::url('api/emergencia/registrar-atencion');
$cerrarUrl = UrlHelper::url('api/emergencia/cerrar');
$dashboardUrl = UrlHelper::url('api/doctor/dashboard');

include __DIR__ . '/../layout/header.php';
?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Datos del paciente y emergencia -->
    <div class="md:col-span-1 space-y-6">
        <div class="glass-card p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-[#007BFF]">Datos del paciente</h3>
            <div class="space-y-3 text-sm">
                <p><span class="font-semibold text-gray-600">Nombre:</span><br>
                    <?php echo htmlspecialchars($emergencia['paciente_nombre'] . ' ' . $emergencia['paciente_apellido']); ?>
                </p>
                <p><span class="font-semibold text-gray-600">Identificación:</span><br>
                    <?php echo htmlspecialchars(PrivacyHelper::maskCedula($emergencia['paciente_identificacion'] ?? '') ?: '—'); ?>
                </p>
                <p><span class="font-semibold text-gray-600">Grupo sanguíneo:</span><br>
                    <?php echo htmlspecialchars($identidad['grupo_sanguineo'] ?? '—'); ?>
                </p>
                <p><span class="font-semibold text-gray-600">Alergias:</span><br>
                    <?php echo nl2br(htmlspecialchars($identidad['alergias'] ?? 'Ninguna conocida')); ?>
                </p>
            </div>
        </div>

        <div class="glass-card p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-[#212529]">Datos de la emergencia</h3>
            <div class="space-y-3 text-sm">
                <p>
                    <span class="font-semibold text-gray-600">Nivel triaje:</span>
                    <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($triaje); ?>
                    </span>
                </p>
                <p><span class="font-semibold text-gray-600">Motivo ingreso:</span><br>
                    <?php echo nl2br(htmlspecialchars($emergencia['motivo_ingreso'])); ?>
                </p>
                <p><span class="font-semibold text-gray-600">Hora ingreso:</span><br>
                    <?php echo date('d/m/Y H:i', strtotime($emergencia['fecha_ingreso'])); ?>
                </p>
                <p><span class="font-semibold text-gray-600">Registrada por:</span><br>
                    <?php echo htmlspecialchars(trim(($emergencia['usuario_nombre'] ?? '') . ' ' . ($emergencia['usuario_apellido'] ?? '')) ?: '—'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Formulario de atención -->
    <div class="md:col-span-2 space-y-6">
        <form id="formEmergencia" class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100 space-y-6">
            <input type="hidden" id="emergenciaId" value="<?php echo (int) $emergencia['id']; ?>">

            <!-- Signos vitales -->
            <div>
                <h4 class="text-md font-bold text-gray-700 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#007BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    Signos vitales
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">PA</label>
                        <input type="text" name="presion_arterial" id="presion_arterial"
                            value="<?php echo htmlspecialchars($signos['presion_arterial'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="120/80">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">FC</label>
                        <input type="number" name="frecuencia_cardiaca" id="frecuencia_cardiaca"
                            value="<?php echo htmlspecialchars($signos['frecuencia_cardiaca'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="72">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Temp (°C)</label>
                        <input type="number" step="0.1" name="temperatura" id="temperatura"
                            value="<?php echo htmlspecialchars($signos['temperatura'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="36.5">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Peso (kg)</label>
                        <input type="number" step="0.1" name="peso" id="peso"
                            value="<?php echo htmlspecialchars($signos['peso'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="70">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Estatura (m)</label>
                        <input type="number" step="0.01" name="estatura" id="estatura"
                            value="<?php echo htmlspecialchars($signos['estatura'] ?? ''); ?>"
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="1.75">
                    </div>
                </div>
            </div>

            <!-- Evaluación clínica -->
            <div>
                <h4 class="text-md font-bold text-gray-700 mb-4">Evaluación clínica</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Motivo de consulta</label>
                        <textarea name="motivo_consulta" id="motivo_consulta" rows="2"
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="Motivo de consulta en emergencia"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Síntomas</label>
                        <textarea name="sintomas" id="sintomas" rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="Síntomas referidos"></textarea>
                    </div>
                </div>
            </div>

            <!-- Diagnóstico y tratamiento -->
            <div>
                <h4 class="text-md font-bold text-gray-700 mb-4">Diagnóstico y tratamiento</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Diagnóstico</label>
                        <textarea name="diagnostico" id="diagnostico" rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="Diagnóstico de emergencia"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Tratamiento</label>
                        <textarea name="tratamiento" id="tratamiento" rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="Tratamiento indicado"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Observaciones</label>
                        <textarea name="observaciones" id="observaciones" rows="2"
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-[#007BFF] outline-none"
                            placeholder="Observaciones adicionales"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4 pt-4">
                <a href="<?php echo htmlspecialchars($dashboardUrl); ?>"
                    class="px-6 py-2 rounded-lg border border-[#6C757D] text-[#6C757D] hover:bg-[#F8F9FA] font-semibold">
                    Cancelar
                </a>
                <button type="button" id="btnHospitalizar" class="px-6 py-2 rounded-lg bg-[#007BFF] text-white hover:bg-blue-700 font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Hospitalizar
                </button>
                <button type="submit" id="btnGuardar" class="px-6 py-2 rounded-lg bg-[#28A745] text-white hover:bg-[#218838] font-semibold">
                    Guardar y cerrar emergencia
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hospitalizar -->
<div id="modalHospitalizar" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-[#F8F9FA]">
            <h3 class="text-lg font-bold text-[#212529]">Decisión de Hospitalización</h3>
            <button type="button" onclick="document.getElementById('modalHospitalizar').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Seleccionar Cama</label>
                <select id="camaInternar" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Cargando camas...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Indicaciones / Motivo</label>
                <textarea id="motivoInternar" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="¿Por qué se interna al paciente?"></textarea>
            </div>
        </div>
        <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('modalHospitalizar').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-semibold">Cancelar</button>
            <button type="button" id="confirmarHospitalizacion" class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-all">Confirmar Internamiento</button>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('formEmergencia');
    const btnGuardar = document.getElementById('btnGuardar');
    const emergenciaId = document.getElementById('emergenciaId').value;
    const csrfToken = '<?php echo addslashes($csrfToken); ?>';
    const registrarUrl = '<?php echo addslashes($registrarUrl); ?>';
    const cerrarUrl = '<?php echo addslashes($cerrarUrl); ?>';
    const dashboardUrl = '<?php echo addslashes($dashboardUrl); ?>';

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';

        const payload = {
            emergencia_id: parseInt(emergenciaId, 10),
            motivo_consulta: document.getElementById('motivo_consulta').value.trim(),
            sintomas: document.getElementById('sintomas').value.trim(),
            diagnostico: document.getElementById('diagnostico').value.trim(),
            tratamiento: document.getElementById('tratamiento').value.trim(),
            observaciones: document.getElementById('observaciones').value.trim(),
            presion_arterial: document.getElementById('presion_arterial').value.trim() || null,
            frecuencia_cardiaca: document.getElementById('frecuencia_cardiaca').value.trim() ? parseInt(document.getElementById('frecuencia_cardiaca').value, 10) : null,
            temperatura: document.getElementById('temperatura').value.trim() ? parseFloat(document.getElementById('temperatura').value) : null,
            peso: document.getElementById('peso').value.trim() ? parseFloat(document.getElementById('peso').value) : null,
            estatura: document.getElementById('estatura').value.trim() ? parseFloat(document.getElementById('estatura').value) : null
        };

        try {
            const res1 = await fetch(registrarUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(payload)
            });
            const data1 = await res1.json();
            if (!data1.success) {
                showToast(data1.error || 'Error al registrar la atención.', 'error');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar y cerrar emergencia';
                return;
            }

            const res2 = await fetch(cerrarUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ emergencia_id: parseInt(emergenciaId, 10) })
            });
            const data2 = await res2.json();
            if (!data2.success) {
                showToast(data2.error || 'Error al cerrar la emergencia.', 'error');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar y cerrar emergencia';
                return;
            }

            window.location.href = dashboardUrl;
        } catch (err) {
            showToast('Error de conexión. Intente de nuevo.', 'error');
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar y cerrar emergencia';
        }
    });

    // Lógica Hospitalización
    const btnHospitalizar = document.getElementById('btnHospitalizar');
    const modalHospitalizar = document.getElementById('modalHospitalizar');
    const confirmarHosp = document.getElementById('confirmarHospitalizacion');

    btnHospitalizar.addEventListener('click', async () => {
        modalHospitalizar.classList.remove('hidden');
        const res = await fetch('<?php echo UrlHelper::url('api/hospitalizacion/camas-disponibles'); ?>');
        const data = await res.json();
        const select = document.getElementById('camaInternar');
        select.innerHTML = '<option value="">Seleccione una cama...</option>';
        if (data.success) {
            data.camas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `Hab. ${c.habitacion_numero} - Cama ${c.numero}`;
                select.appendChild(opt);
            });
        }
    });

    confirmarHosp.addEventListener('click', async () => {
        const camaId = document.getElementById('camaInternar').value;
        const motivo = document.getElementById('motivoInternar').value;
        const diagnostico = document.getElementById('diagnostico').value;

        if (!camaId || !motivo) {
            alert('Debe seleccionar una cama y proveer un motivo.');
            return;
        }

        confirmarHosp.disabled = true;
        confirmarHosp.textContent = 'Procesando...';

        try {
            // Primero guardamos la atención actual (sin cerrar la emergencia todavía?)
            // El servicio internarDesdeEmergencia se encarga de cambiar el estado a 'Transferido'.
            
            const payload = {
                origen: 'emergencia',
                emergencia_id: parseInt(emergenciaId, 10),
                cama_id: parseInt(camaId, 10),
                motivo: motivo,
                diagnostico_ingreso: diagnostico
            };

            const res = await fetch('<?php echo UrlHelper::url('api/hospitalizacion/internar'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                showToast('Paciente hospitalizado correctamente.', 'success');
                setTimeout(() => window.location.href = dashboardUrl, 1500);
            } else {
                alert(data.error);
                confirmarHosp.disabled = false;
                confirmarHosp.textContent = 'Confirmar Internamiento';
            }
        } catch (e) {
            alert('Error al procesar hospitalización');
            confirmarHosp.disabled = false;
        }
    });
})();
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

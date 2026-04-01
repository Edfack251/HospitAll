<?php
use App\Helpers\UrlHelper;
use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::requireLogin();

$controller = new AppointmentsController($pdo);
$data = $controller->getSchedulingData();
$pacientes = $data['pacientes'];
$medicos = $data['medicos'];

$pageTitle = 'Agendar Cita - HospitAll';
$activePage = 'citas';
$headerTitle = 'Agendar Nueva Cita';
$headerSubtitle = 'Selecciona el paciente, el médico y el horario.';

$baseUrl = rtrim(UrlHelper::url(''), '/');
include __DIR__ . '/../layout/header.php';
?>

<div class="w-full max-w-2xl mx-auto bg-white p-4 sm:p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
    <form action="<?php echo UrlHelper::url('api/appointments/schedule'); ?>" method="POST" class="space-y-6">
        <?php $csrf = CsrfHelper::generateToken(); ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <div>
            <label class="block text-sm font-medium mb-2">Paciente</label>
            <?php if ($_SESSION['user_role'] === 'paciente'): ?>
                <input type="hidden" name="paciente_id" id="paciente_id" value="<?php echo $_SESSION['paciente_id']; ?>">
                <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"
                    class="w-full px-4 py-2 rounded-lg border bg-gray-50 outline-none shadow-sm">
            <?php else: ?>
                <select name="paciente_id" id="paciente_id" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm transition-all">
                    <option value="">Selecciona un paciente</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div id="episodio_container" class="hidden">
            <label class="block text-sm font-medium mb-2">Episodio Clínico (Opcional)</label>
            <div class="flex space-x-2">
                <select name="episodio_id" id="episodio_id"
                    class="flex-1 px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm transition-all">
                    <option value="">Sin episodio (Cita aislada)</option>
                </select>
                <button type="button" id="btn_new_episode"
                    class="bg-[#007BFF] text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    + Nuevo
                </button>
            </div>
        </div>

        <!-- Formulario Inline para Nuevo Episodio -->
        <div id="new_episode_form" class="hidden bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
            <h4 class="font-semibold text-blue-800 mb-3">Crear Nuevo Episodio</h4>
            <div id="episode_feedback" class="hidden mb-3 px-3 py-2 rounded text-sm"></div>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium mb-1">Problema de Salud / Diagnóstico Inicial</label>
                    <input type="text" id="new_ep_desc" class="w-full px-3 py-2 rounded border text-sm"
                        placeholder="Ej. Hipertensión Arterial">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="btn_cancel_episode"
                        class="text-sm px-3 py-1 text-gray-600 hover:text-gray-800">Cancelar</button>
                    <button type="button" id="btn_save_episode"
                        class="text-sm bg-[#28A745] text-white px-3 py-1 rounded hover:bg-green-700">Guardar
                        Episodio</button>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Médico</label>
            <select name="medico_id" id="medico_id_select" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm transition-all">
                <option value="">Selecciona un médico</option>
                <?php foreach ($medicos as $m): ?>
                    <option value="<?php echo $m['id']; ?>">
                        <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido'] . ' - ' . $m['especialidad']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Fecha</label>
                <input type="date" name="fecha" id="fecha_input" required min="<?php echo date('Y-m-d'); ?>"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Hora</label>
                <select name="hora" id="hora_select" required disabled
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm transition-all bg-gray-50">
                    <option value="">Selecciona médico y fecha primero</option>
                </select>
                <p id="hora_message" class="text-xs mt-1 text-gray-500 hidden"></p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Observaciones (Opcional)</label>
            <textarea name="observaciones"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                rows="3"></textarea>
        </div>

        <div class="flex justify-end space-x-4 mt-4">
            <a href="<?php echo ($_SESSION['user_role'] === 'paciente') ? App\Helpers\UrlHelper::url('patient_portal') : App\Helpers\UrlHelper::url('appointments'); ?>"
                class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</a>
            <button type="submit"
                class="bg-[#28A745] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all">
                Agendar Cita
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pacienteSelect = document.getElementById('paciente_id');
        const episodioContainer = document.getElementById('episodio_container');
        const episodioSelect = document.getElementById('episodio_id');
        const btnNewEpisode = document.getElementById('btn_new_episode');
        const newEpisodeForm = document.getElementById('new_episode_form');
        const btnCancelEpisode = document.getElementById('btn_cancel_episode');
        const btnSaveEpisode = document.getElementById('btn_save_episode');
        const newEpDesc = document.getElementById('new_ep_desc');

        // --- Horarios disponibles ---
        const medicoSelect = document.getElementById('medico_id_select');
        const fechaInput = document.getElementById('fecha_input');
        const horaSelect = document.getElementById('hora_select');
        const horaMessage = document.getElementById('hora_message');

        function resetHoraSelect() {
            horaSelect.innerHTML = '<option value="">Selecciona médico y fecha primero</option>';
            horaSelect.disabled = true;
            horaSelect.classList.add('bg-gray-50');
            horaMessage.classList.add('hidden');
            horaMessage.textContent = '';
        }

        function loadHorarios() {
            const medicoId = medicoSelect ? medicoSelect.value : '';
            const fecha = fechaInput ? fechaInput.value : '';

            if (!medicoId || !fecha) {
                resetHoraSelect();
                return;
            }

            // Loading state
            horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';
            horaSelect.disabled = true;
            horaSelect.classList.add('bg-gray-50');
            horaMessage.classList.add('hidden');

            fetch(`<?php echo $baseUrl; ?>/api/appointments/horarios-disponibles?medico_id=${medicoId}&fecha=${fecha}`)
                .then(res => res.json())
                .then(data => {
                    horaSelect.innerHTML = '';
                    if (data.success && data.horarios.length > 0) {
                        horaSelect.innerHTML = '<option value="">Selecciona un horario</option>';
                        data.horarios.forEach(h => {
                            const opt = document.createElement('option');
                            opt.value = h;
                            opt.textContent = h;
                            horaSelect.appendChild(opt);
                        });
                        horaSelect.disabled = false;
                        horaSelect.classList.remove('bg-gray-50');
                        horaMessage.classList.add('hidden');
                    } else if (data.success && data.horarios.length === 0) {
                        horaSelect.innerHTML = '<option value="">No hay horarios disponibles</option>';
                        horaSelect.disabled = true;
                        horaMessage.textContent = 'No hay horarios disponibles para esta fecha.';
                        horaMessage.className = 'text-xs mt-1 text-amber-600';
                        horaMessage.classList.remove('hidden');
                    } else {
                        horaSelect.innerHTML = '<option value="">Error al cargar</option>';
                        horaSelect.disabled = true;
                        horaMessage.textContent = data.error || 'Error desconocido.';
                        horaMessage.className = 'text-xs mt-1 text-red-600';
                        horaMessage.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    console.error('Error cargando horarios:', err);
                    horaSelect.innerHTML = '<option value="">Error de conexión</option>';
                    horaSelect.disabled = true;
                    horaMessage.textContent = 'Error de conexión. Intente de nuevo.';
                    horaMessage.className = 'text-xs mt-1 text-red-600';
                    horaMessage.classList.remove('hidden');
                });
        }

        if (medicoSelect) medicoSelect.addEventListener('change', loadHorarios);
        if (fechaInput) fechaInput.addEventListener('change', loadHorarios);

        // Validación al enviar
        const form = horaSelect ? horaSelect.closest('form') : null;
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!horaSelect.value) {
                    e.preventDefault();
                    alert('Por favor selecciona un horario disponible.');
                    horaSelect.focus();
                }
            });
        }

        // --- Episodios clínicos ---
        function loadEpisodes(pacienteId) {
            if (!pacienteId) {
                episodioContainer.classList.add('hidden');
                return;
            }
            episodioContainer.classList.remove('hidden');

            fetch(`<?php echo $baseUrl; ?>/api/episodes?paciente_id=${pacienteId}`)
                .then(res => res.json())
                .then(data => {
                    episodioSelect.innerHTML = '<option value="">Sin episodio (Cita aislada)</option>';
                    if (data.success && data.data.length > 0) {
                        data.data.forEach(ep => {
                            if (ep.estado === 'Abierto') {
                                const option = document.createElement('option');
                                option.value = ep.id;
                                option.textContent = `${ep.descripcion_problema} (Desde: ${ep.fecha_inicio})`;
                                episodioSelect.appendChild(option);
                            }
                        });
                    }
                })
                .catch(err => console.error("Error cargando episodios:", err));
        }

        if (pacienteSelect) {
            if (pacienteSelect.tagName === 'INPUT' && pacienteSelect.value) {
                loadEpisodes(pacienteSelect.value);
            } else {
                pacienteSelect.addEventListener('change', (e) => loadEpisodes(e.target.value));
                if (pacienteSelect.value) loadEpisodes(pacienteSelect.value);
            }
        }

        if (btnNewEpisode) {
            btnNewEpisode.addEventListener('click', () => {
                newEpisodeForm.classList.remove('hidden');
                newEpDesc.focus();
            });
        }

        if (btnCancelEpisode) {
            btnCancelEpisode.addEventListener('click', () => {
                newEpisodeForm.classList.add('hidden');
                newEpDesc.value = '';
            });
        }

        const feedbackEl = document.getElementById('episode_feedback');

        if (btnSaveEpisode) {
            btnSaveEpisode.addEventListener('click', () => {
                const desc = newEpDesc.value.trim();
                const pacienteId = pacienteSelect.value;

                if (!desc || !pacienteId) {
                    alert('Por favor ingrese el problema de salud y seleccione un paciente.');
                    return;
                }

                if (feedbackEl) {
                    feedbackEl.classList.add('hidden');
                }
                btnSaveEpisode.disabled = true;
                btnSaveEpisode.textContent = 'Guardando...';

                fetch('<?php echo $baseUrl; ?>/api/episodes/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        paciente_id: pacienteId,
                        descripcion_problema: desc
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        btnSaveEpisode.disabled = false;
                        btnSaveEpisode.textContent = 'Guardar Episodio';

                        if (data.success) {
                            const hoy = new Date().toISOString().slice(0, 10);
                            const option = document.createElement('option');
                            option.value = data.episodio_id;
                            option.textContent = `${desc} (Desde: ${hoy})`;
                            episodioSelect.appendChild(option);
                            episodioSelect.value = data.episodio_id;

                            if (feedbackEl) {
                                feedbackEl.textContent = 'Episodio guardado correctamente.';
                                feedbackEl.className = 'mb-3 px-3 py-2 rounded text-sm bg-green-100 text-green-800';
                                feedbackEl.classList.remove('hidden');
                            }
                            newEpisodeForm.classList.add('hidden');
                            newEpDesc.value = '';
                        } else {
                            if (feedbackEl) {
                                feedbackEl.textContent = 'Error: ' + (data.error || 'No se pudo guardar.');
                                feedbackEl.className = 'mb-3 px-3 py-2 rounded text-sm bg-red-100 text-red-800';
                                feedbackEl.classList.remove('hidden');
                            } else {
                                alert('Error al crear episodio: ' + (data.error || ''));
                            }
                        }
                    })
                    .catch(err => {
                        btnSaveEpisode.disabled = false;
                        btnSaveEpisode.textContent = 'Guardar Episodio';
                        if (feedbackEl) {
                            feedbackEl.textContent = 'Error de conexión. Intente de nuevo.';
                            feedbackEl.className = 'mb-3 px-3 py-2 rounded text-sm bg-red-100 text-red-800';
                            feedbackEl.classList.remove('hidden');
                        } else {
                            alert('Error de conexión. Intente de nuevo.');
                        }
                        console.error(err);
                    });
            });
        }
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
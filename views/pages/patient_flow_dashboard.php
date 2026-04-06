<?php
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador', 'medico', 'recepcionista']);

$pageTitle = 'Flujo Clínico - HospitAll';
$activePage = 'patient_flow';
$headerTitle = 'Flujo Clínico de Pacientes';
$headerSubtitle = 'Monitorización en tiempo real del estado de los pacientes.';
$csrfToken = App\Helpers\CsrfHelper::generateToken();
include __DIR__ . '/../layout/header.php';
?>

<div class="mb-4 flex justify-between items-center">
    <p class="text-gray-500 text-sm">Actualización automática cada 10 segundos.</p>
    <button onclick="fetchFlowData()"
        class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-100 flex items-center shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
            </path>
        </svg>
        Actualizar
    </button>
</div>

<div class="flex overflow-x-auto pb-8 space-x-6 min-h-[70vh] items-stretch" id="kanban-board">
    <!-- Se llenará vía AJAX -->
</div>

<!-- Scripts del Tablero -->
<script>
    const flowColumns = [
        { id: 'check_in', title: 'CHECK-IN', colorClass: 'border-t-4 border-yellow-400', bgClass: 'bg-yellow-50' },
        { id: 'triaje', title: 'TRIAJE', colorClass: 'border-t-4 border-orange-400', bgClass: 'bg-orange-50' },
        { id: 'esperando_medico', title: 'ESPERANDO MÉDICO', colorClass: 'border-t-4 border-purple-400', bgClass: 'bg-purple-50' },
        { id: 'en_consulta', title: 'EN CONSULTA', colorClass: 'border-t-4 border-green-500', bgClass: 'bg-green-50' },
        { id: 'en_procedimiento', title: 'EN PROCEDIMIENTO', colorClass: 'border-t-4 border-red-400', bgClass: 'bg-red-50' },
        { id: 'observacion', title: 'OBSERVACIÓN', colorClass: 'border-t-4 border-blue-500', bgClass: 'bg-blue-50' }
    ];

    function buildKanban(data) {
        const board = document.getElementById('kanban-board');
        board.innerHTML = '';

        flowColumns.forEach(col => {
            const columnDiv = document.createElement('div');
            columnDiv.className = `flex-shrink-0 w-80 bg-white rounded-xl shadow border border-gray-100 flex flex-col ${col.colorClass}`;

            const headerDiv = document.createElement('div');
            headerDiv.className = `p-4 border-b border-gray-100 font-bold text-gray-700 flex justify-between items-center ${col.bgClass}`;

            const count = data[col.id] ? data[col.id].length : 0;
            headerDiv.innerHTML = `<span class="tracking-wide">${col.title}</span> <span class="bg-white text-xs px-2.5 py-1 rounded-full shadow-sm text-gray-800">${count}</span>`;

            const cardsDiv = document.createElement('div');
            cardsDiv.className = 'p-4 flex-1 overflow-y-auto space-y-4 bg-gray-50/30';

            if (data[col.id] && data[col.id].length > 0) {
                data[col.id].forEach(apt => {
                    const card = document.createElement('div');
                    card.className = 'bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-blue-200 transition-all cursor-default';

                    // Formatear hora (de HH:MM:SS a HH:MM)
                    const horaArr = apt.hora.split(':');
                    const horaFormat = `${horaArr[0]}:${horaArr[1]}`;

                    let optionsHtml = '';
                    flowColumns.forEach(c => {
                        optionsHtml += `<option value="${c.id}" ${c.id === col.id ? 'selected' : ''}>Mover a: ${c.title}</option>`;
                    });
                    optionsHtml += `<option value="alta">Dar de Alta</option>`;

                    card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-bold text-gray-800 text-sm leading-tight pr-2">${apt.paciente_nombre} ${apt.paciente_apellido}</h4>
                        <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-md mt-1">${horaFormat}</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4 flex items-center font-medium">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Dr(a). ${apt.medico_apellido}
                    </p>
                    <select onchange="changeStatus(${apt.id}, this.value)" class="w-full text-xs box-border rounded-md border-gray-200 bg-gray-50 shadow-sm p-2 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-700 font-semibold cursor-pointer appearance-none">
                        ${optionsHtml}
                    </select>
                `;
                    cardsDiv.appendChild(card);
                });
            } else {
                const emptyState = document.createElement('div');
                emptyState.className = 'h-32 flex items-center justify-center text-sm text-gray-400 italic bg-gray-50/50 rounded-lg border border-dashed border-gray-200';
                emptyState.innerText = 'Sin pacientes';
                cardsDiv.appendChild(emptyState);
            }

            columnDiv.appendChild(headerDiv);
            columnDiv.appendChild(cardsDiv);
            board.appendChild(columnDiv);
        });
    }

    function fetchFlowData() {
        fetch('<?php echo \App\Helpers\UrlHelper::url('api/patient-flow/data'); ?>')
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    buildKanban(res.data);
                } else {
                    console.error('Error fetching flow data:', res.error);
                }
            })
            .catch(err => console.error('Fetch error:', err));
    }

    function changeStatus(citaId, nuevoEstado) {
        if (!nuevoEstado) return;

        const csrfToken = '<?php echo $csrfToken; ?>';

        fetch('<?php echo \App\Helpers\UrlHelper::url('api/patient-flow/update-status'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                cita_id: citaId,
                nuevo_estado: nuevoEstado
            })
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    fetchFlowData();
                } else {
                    alert('Error al cambiar estado: ' + (res.error || 'Desconocido'));
                    fetchFlowData(); // Revert
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión');
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchFlowData();
        setInterval(fetchFlowData, 10000);
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
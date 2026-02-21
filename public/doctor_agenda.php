<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';

checkRole(['medico', 'administrador']);

$medico_id = $_SESSION['medico_id'] ?? null;

// Si es admin pero no tiene medico_id, redirigir o manejar (por ahora asumimos que el admin puede entrar pero tal vez no vea nada o vea todo)
if (!$medico_id && $_SESSION['user_role'] === 'medico') {
    die("Error: No se encontró el registro de médico asociado a este usuario.");
}

$events = [];
$citasHoy = 0;
$totalPacientesAtendidos = 0;
$proximasCitas = [];

try {
    if ($medico_id) {
        // Estadísticas para el médico
        $stmtHoy = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND fecha = CURDATE() AND estado IN ('Programada', 'Confirmada', 'En espera')");
        $stmtHoy->execute([$medico_id]);
        $citasHoy = $stmtHoy->fetchColumn();

        $stmtTotal = $pdo->prepare("SELECT COUNT(DISTINCT paciente_id) FROM historial_clinico WHERE medico_id = ?");
        $stmtTotal->execute([$medico_id]);
        $totalPacientesAtendidos = $stmtTotal->fetchColumn();

        // Próximas citas (Listado)
        $stmtProx = $pdo->prepare("SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido 
                                   FROM citas c 
                                   JOIN pacientes p ON c.paciente_id = p.id 
                                   WHERE c.medico_id = ? AND c.estado IN ('Programada', 'Confirmada', 'En espera') 
                                   AND (c.fecha > CURDATE() OR (c.fecha = CURDATE() AND c.hora >= CURTIME()))
                                   ORDER BY c.fecha ASC, c.hora ASC LIMIT 10");
        $stmtProx->execute([$medico_id]);
        $proximasCitas = $stmtProx->fetchAll();

        // Eventos para el Calendario (Solo pendientes/programadas)
        $stmtEvents = $pdo->prepare("SELECT c.id, c.fecha, c.hora, c.estado, c.paciente_id, p.nombre, p.apellido 
                                     FROM citas c 
                                     JOIN pacientes p ON c.paciente_id = p.id 
                                     WHERE c.medico_id = ? AND c.estado IN ('Programada', 'Confirmada', 'En espera')");
        $stmtEvents->execute([$medico_id]);
        $dbEvents = $stmtEvents->fetchAll();

        foreach ($dbEvents as $e) {
            $color = '#3b82f6'; // Programada / Confirmada
            if ($e['estado'] === 'En espera')
                $color = '#eab308'; // Amarillo

            $events[] = [
                'title' => $e['nombre'] . ' ' . $e['apellido'],
                'start' => $e['fecha'] . 'T' . $e['hora'],
                'url' => 'patient_portal.php?patient_id=' . $e['paciente_id'],
                'color' => $color
            ];
        }
    }
} catch (PDOException $e) {
    die("Error al cargar agenda: " . $e->getMessage());
}

$pageTitle = 'Mi Agenda - HospitAll';
$activePage = 'citas';
$headerTitle = 'Mi Agenda Médica';
$headerSubtitle = 'Gestiona tus citas y consulta tu calendario de atenciones.';

include '../views/layout/header.php';
?>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Columna Izquierda: Stats y Listado -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Citas Hoy</p>
                <p class="text-2xl font-bold text-[#007BFF]">
                    <?php echo $citasHoy; ?>
                </p>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Pacientes</p>
                <p class="text-2xl font-bold text-[#28A745]">
                    <?php echo $totalPacientesAtendidos; ?>
                </p>
            </div>
        </div>

        <!-- Listado Próximas -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-[#212529]">Próximas Atenciones</h3>
            <?php if (empty($proximasCitas)): ?>
                <p class="text-sm text-gray-400 text-center py-6">No hay citas pendientes.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($proximasCitas as $c): ?>
                        <div class="p-3 border rounded-xl hover:border-[#007BFF] transition-all bg-gray-50/50">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-bold text-[#007BFF]">
                                    <?php echo date('d/m/Y', strtotime($c['fecha'])); ?> -
                                    <?php echo date('H:i', strtotime($c['hora'])); ?>
                                </span>
                                <span class="text-[10px] bg-white px-2 py-0.5 rounded-full border text-gray-500">
                                    <?php echo $c['estado']; ?>
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-800 mt-1">
                                <?php echo htmlspecialchars($c['paciente_nombre'] . ' ' . $c['paciente_apellido']); ?>
                            </p>
                            <?php if (in_array($c['estado'], ['Programada', 'Confirmada', 'En espera'])): ?>
                                <a href="appointments_attend.php?id=<?php echo $c['id']; ?>"
                                    class="mt-2 block text-center bg-[#28A745] text-white text-[10px] py-1 rounded-lg font-bold hover:bg-green-700">Atender
                                    ahora</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Columna Derecha: Calendario -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<style>
    .fc .fc-button-primary {
        background-color: #007BFF;
        border-color: #007BFF;
    }

    .fc .fc-button-primary:hover {
        background-color: #0056b3;
        border-color: #004a9b;
    }

    .fc .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: bold;
    }

    .fc-event {
        cursor: pointer;
        padding: 2px 4px;
        font-size: 0.75rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: <?php echo json_encode($events); ?>,
            eventClick: function (info) {
                if (info.event.url !== '#') {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });
        calendar.render();
    });
</script>

<?php include '../views/layout/footer.php'; ?>
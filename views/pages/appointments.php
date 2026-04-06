<?php
use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista']);

$controller = new AppointmentsController($pdo);
$citas = $controller->index();

$pageTitle = 'Citas Médicas - HospitAll';
$activePage = 'citas';
$headerTitle = 'Gestión de Citas';
$headerSubtitle = 'Control y seguimiento de citas médicas.';

include __DIR__ . '/../layout/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <h3 class="text-xl font-bold text-[#212529]">Listado de Citas</h3>
    <a href="<?php echo \App\Helpers\UrlHelper::url('appointments_schedule'); ?>"
        class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
        + Agendar Cita
    </a>
</div>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="citasTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Paciente</th>
                <th class="py-4">Médico</th>
                <th class="py-4">Fecha y Hora</th>
                <th class="py-4">Estado</th>
                <th class="py-4">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citas as $cita): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4">
                        <div class="font-medium">
                            <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                        </div>
                        <div class="text-xs text-[#6C757D]">
                            Cédula:
                            <?php echo htmlspecialchars(\App\Helpers\PrivacyHelper::maskCedula($cita['paciente_identificacion'] ?? '', $cita['paciente_id_real'] ?? null)); ?>
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm font-semibold">
                            <?php echo htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']); ?>
                        </div>
                        <div class="text-xs text-[#6C757D]">
                            <?php echo htmlspecialchars($cita['especialidad']); ?>
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="text-sm">
                            <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?>
                        </div>
                        <div class="text-xs text-[#6C757D]">
                            <?php echo date('H:i', strtotime($cita['hora'])); ?>
                        </div>
                    </td>
                    <td class="py-4">
                        <?php
                        $estado = $cita['estado'];
                        // Compatibilidad con registros antiguos o DB no actualizada
                        if ($estado === 'Pendiente' || empty($estado)) {
                            $estado = 'Programada';
                        }

                        $statusClass = 'bg-gray-100 text-gray-600';
                        switch ($estado) {
                            case 'Programada':
                                $statusClass = 'bg-blue-100 text-blue-600';
                                break;
                            case 'Confirmada':
                                $statusClass = 'bg-indigo-100 text-indigo-600';
                                break;
                            case 'En espera':
                                $statusClass = 'bg-yellow-100 text-yellow-600';
                                break;
                            case 'Atendida':
                                $statusClass = 'bg-green-100 text-green-600';
                                break;
                            case 'Cancelada':
                                $statusClass = 'bg-red-100 text-red-600';
                                break;
                            case 'No asistió':
                                $statusClass = 'bg-orange-100 text-orange-600';
                                break;
                        }
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                            <?php echo $estado; ?>
                        </span>
                    </td>
                    <td class="py-4">
                        <div class="flex items-center space-x-2">
                            <?php if ($estado === 'En espera'): ?>
                                <a href="<?php echo \App\Helpers\UrlHelper::url('appointments_attend'); ?>?id=<?php echo $cita['id']; ?>"
                                    class="text-xs bg-[#28A745] text-white px-2 py-1 rounded hover:bg-green-700 transition-colors">
                                    Atender
                                </a>
                            <?php endif; ?>

                            <form action="<?php echo \App\Helpers\UrlHelper::url('appointments_status_update'); ?>" method="POST" class="inline">
                                <?php $csrf = CsrfHelper::generateToken(); ?>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                <select name="nuevo_estado" onchange="this.form.submit()"
                                    class="text-xs border rounded p-1 outline-none">
                                    <option value="">Acciones</option>
                                    <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                        <?php if ($estado === 'Programada'): ?>
                                            <option value="Confirmada">Confirmar</option>
                                            <option value="Cancelada">Cancelar</option>
                                        <?php elseif ($estado === 'Confirmada'): ?>
                                            <option value="En espera">Marcar llegada</option>
                                            <option value="Programada">Reprogramar</option>
                                        <?php elseif ($estado === 'En espera'): ?>
                                            <option value="No asistió">No asistió</option>
                                        <?php endif; ?>
                                    <?php elseif ($_SESSION['user_role'] === 'recepcionista'): ?>
                                        <?php if (in_array($estado, ['Programada', 'Confirmada', 'En espera'])): ?>
                                            <option value="Cancelada">Cancelar</option>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        $('#citasTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        });
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
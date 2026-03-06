<?php
session_start();
require_once '../app/autoload.php';
$pdo = \App\Config\Database::getConnection();

use App\Controllers\DashboardController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador', 'medico', 'recepcionista', 'tecnico_laboratorio', 'farmaceutico']);

$controller = new DashboardController($pdo);
$data = $controller->index();

$totalPacientes = $data['totalPacientes'];
$totalMedicos = $data['totalMedicos'];
$citasHoy = $data['citasHoy'];
$proximasCitas = $data['proximasCitas'];

$pageTitle = 'Dashboard - HospitAll';
$activePage = 'dashboard';
$headerTitle = 'Panel General';
$headerSubtitle = 'Bienvenido al sistema de gestión HospitAll.';
include '../views/layout/header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <div class="glass-card p-8 rounded-2xl shadow-sm border border-blue-50">
        <p class="text-sm font-bold text-gray-400 uppercase tracking-wider">Citas del Día</p>
        <p class="text-5xl font-extrabold gradient-text mt-4">
            <?php echo $citasHoy; ?>
        </p>
    </div>
    <div class="glass-card p-8 rounded-2xl shadow-sm border border-green-50">
        <p class="text-sm font-bold text-gray-400 uppercase tracking-wider">Pacientes Totales</p>
        <p class="text-5xl font-extrabold text-[#28A745] mt-4">
            <?php echo $totalPacientes; ?>
        </p>
    </div>
    <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-sm font-bold text-gray-400 uppercase tracking-wider">Médicos Activos</p>
        <p class="text-5xl font-extrabold text-gray-600 mt-4">
            <?php echo $totalMedicos; ?>
        </p>
    </div>
</div>

<!-- Recent Activity -->
<div class="glass-card p-10 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-8">
        <h3 class="text-2xl font-bold gradient-text">Próximas Citas</h3>
        <a href="appointments.php"
            class="text-blue-600 text-sm font-bold hover:underline bg-blue-50 px-4 py-2 rounded-full transition">Ver
            todas</a>
    </div>

    <?php if (empty($proximasCitas)): ?>
        <div class="text-[#6C757D] text-center py-10">
            <p>No hay citas programadas para los próximos días.</p>
            <a href="appointments_schedule.php"
                class="text-[#007BFF] font-semibold hover:underline mt-4 inline-block">Agendar nueva cita</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[#6C757D] text-sm border-b">
                        <th class="pb-4 font-medium">Paciente</th>
                        <th class="pb-4 font-medium">Médico</th>
                        <th class="pb-4 font-medium">Fecha y Hora</th>
                        <th class="pb-4 font-medium">Estado</th>
                        <th class="pb-4 font-medium text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($proximasCitas as $cita): ?>
                        <tr>
                            <td class="py-4 font-medium text-[#212529]">
                                <?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?>
                            </td>
                            <td class="py-4 text-[#495057]">
                                Dr.
                                <?php echo htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']); ?>
                            </td>
                            <td class="py-4 text-[#495057]">
                                <span class="block font-semibold">
                                    <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?>
                                </span>
                                <span class="text-xs text-[#6C757D]">
                                    <?php echo $cita['hora']; ?>
                                </span>
                            </td>
                            <td class="py-4">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-semibold 
                                    <?php echo $cita['estado'] === 'Programada' ? 'bg-blue-100 text-blue-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                    <?php echo $cita['estado']; ?>
                                </span>
                            </td>
                            <td class="py-4 text-right">
                                <a href="appointments_attend.php?id=<?php echo $cita['id']; ?>"
                                    class="text-[#007BFF] hover:underline font-medium">Atender</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../views/layout/footer.php'; ?>
<?php
session_start();
require_once '../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador', 'medico', 'recepcionista', 'tecnico_laboratorio']);

// Obtener estadísticas reales
try {
    $stmtPacientes = $pdo->query("SELECT COUNT(*) FROM pacientes");
    $totalPacientes = $stmtPacientes->fetchColumn();

    $stmtMedicos = $pdo->query("SELECT COUNT(*) FROM medicos");
    $totalMedicos = $stmtMedicos->fetchColumn();

    $stmtCitas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()");
    $stmtCitas->execute();
    $citasHoy = $stmtCitas->fetchColumn();

    // Obtener las próximas 10 citas programadas
    $stmtProximas = $pdo->prepare("SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, m.nombre as
medico_nombre, m.apellido as medico_apellido
FROM citas c
JOIN pacientes p ON c.paciente_id = p.id
JOIN medicos m ON c.medico_id = m.id
WHERE c.estado = 'Programada'
ORDER BY c.fecha ASC, c.hora ASC
LIMIT 10");
    $stmtProximas->execute();
    $proximasCitas = $stmtProximas->fetchAll();
} catch (PDOException $e) {
    $totalPacientes = 0;
    $totalMedicos = 0;
    $citasHoy = 0;
    $proximasCitas = [];
}

$pageTitle = 'Dashboard - HospitAll';
$activePage = 'dashboard';
$headerTitle = 'Panel General';
$headerSubtitle = 'Bienvenido al sistema de gestión HospitAll.';
include '../views/layout/header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-[#6C757D]">Citas del Día</p>
        <p class="text-4xl font-bold text-[#007BFF] mt-2">
            <?php echo $citasHoy; ?>
        </p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-[#6C757D]">Pacientes Totales</p>
        <p class="text-4xl font-bold text-[#28A745] mt-2">
            <?php echo $totalPacientes; ?>
        </p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-[#6C757D]">Médicos Activos</p>
        <p class="text-4xl font-bold text-[#6C757D] mt-2">
            <?php echo $totalMedicos; ?>
        </p>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold">Próximas Citas</h3>
        <a href="appointments.php" class="text-[#007BFF] text-sm font-semibold hover:underline">Ver todas</a>
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
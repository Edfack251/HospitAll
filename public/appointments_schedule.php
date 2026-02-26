<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';
require_once '../app/controllers/AppointmentsController.php';

requireLogin();

$controller = new AppointmentsController($pdo);
$data = $controller->getSchedulingData();
$pacientes = $data['pacientes'];
$medicos = $data['medicos'];

$pageTitle = 'Agendar Cita - HospitAll';
$activePage = 'citas';
$headerTitle = 'Agendar Nueva Cita';
$headerSubtitle = 'Selecciona el paciente, el médico y el horario.';

include '../views/layout/header.php';
?>

<div class="max-w-2xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <form action="api/appointments.php" method="POST" class="space-y-6">
        <div>
            <label class="block text-sm font-medium mb-2">Paciente</label>
            <?php if ($_SESSION['user_role'] === 'paciente'): ?>
                <input type="hidden" name="paciente_id" value="<?php echo $_SESSION['paciente_id']; ?>">
                <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"
                    class="w-full px-4 py-2 rounded-lg border bg-gray-50 outline-none shadow-sm">
            <?php else: ?>
                <select name="paciente_id" required
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

        <div>
            <label class="block text-sm font-medium mb-2">Médico</label>
            <select name="medico_id" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm transition-all">
                <option value="">Selecciona un médico</option>
                <?php foreach ($medicos as $m): ?>
                    <option value="<?php echo $m['id']; ?>">
                        <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido'] . ' - ' . $m['especialidad']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Fecha</label>
                <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Hora</label>
                <input type="time" name="hora" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Observaciones (Opcional)</label>
            <textarea name="observaciones"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                rows="3"></textarea>
        </div>

        <div class="flex justify-end space-x-4 mt-4">
            <a href="appointments.php"
                class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</a>
            <button type="submit"
                class="bg-[#28A745] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all">
                Agendar Cita
            </button>
        </div>
    </form>
</div>

<?php include '../views/layout/footer.php'; ?>
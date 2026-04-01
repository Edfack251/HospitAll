<?php
use App\Helpers\UrlHelper;
use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista']);

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
if (!$id) {
    UrlHelper::redirect('appointments');
}

$controller = new AppointmentsController($pdo);
$data = $controller->getReprogramData($id);
$cita = $data['cita'];
$pacientes = $data['pacientes'];
$medicos = $data['medicos'];

$pageTitle = 'Reprogramar cita - HospitAll';
$activePage = 'citas';
$headerTitle = 'Reprogramar cita';
$headerSubtitle = 'Cambia la fecha y hora de la cita.';

include __DIR__ . '/../layout/header.php';
?>

<?php if (isset($_GET['error'])): ?>
<div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
    <?php echo htmlspecialchars($_GET['msg'] ?? 'Error al procesar.'); ?>
</div>
<?php endif; ?>

<div class="w-full max-w-2xl mx-auto bg-white p-4 sm:p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
    <div class="mb-6 p-4 rounded-lg bg-gray-50 border border-gray-100">
        <p class="text-sm text-[#6C757D]">Paciente: <strong class="text-[#212529]"><?php echo htmlspecialchars($cita['paciente_nombre'] . ' ' . $cita['paciente_apellido']); ?></strong></p>
        <p class="text-sm text-[#6C757D] mt-1">Médico: <strong class="text-[#212529]">Dr. <?php echo htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']); ?></strong></p>
    </div>

    <form action="<?php echo UrlHelper::url('api/appointments/reprogram'); ?>" method="POST" class="space-y-6">
        <?php $csrf = CsrfHelper::generateToken(); ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="cita_id" value="<?php echo $cita['id']; ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Nueva fecha</label>
                <input type="date" name="fecha" required
                    value="<?php echo htmlspecialchars($cita['fecha'] ?? date('Y-m-d')); ?>"
                    min="<?php echo date('Y-m-d'); ?>"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Nueva hora</label>
                <input type="time" name="hora" required
                    value="<?php echo htmlspecialchars(date('H:i', strtotime($cita['hora'] ?? '09:00'))); ?>"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none shadow-sm">
            </div>
        </div>

        <div class="flex justify-end space-x-4 mt-4 pt-4 border-t">
            <a href="<?php echo UrlHelper::url('appointments'); ?>"
                class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</a>
            <button type="submit"
                class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>

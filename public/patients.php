<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';

checkRole(['administrador', 'recepcionista', 'medico']);

$pageTitle = 'Pacientes - HospitAll';
$activePage = 'pacientes';
$headerTitle = 'Gestión de Pacientes';
$headerSubtitle = 'Listado y administración de pacientes registrados.';

$isMedico = ($_SESSION['user_role'] === 'medico');
$search = $_GET['search'] ?? '';
$pacientes = [];

try {
    if ($isMedico) {
        if (!empty($search)) {
            $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE identificacion = ?");
            $stmt->execute([$search]);
            $pacientes = $stmt->fetchAll();
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM pacientes ORDER BY created_at DESC");
        $pacientes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $pacientes = [];
}

include '../views/layout/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <h3 class="text-xl font-bold text-[#212529]">
        <?php echo $isMedico ? 'Búsqueda de Pacientes' : 'Listado de Pacientes'; ?>
    </h3>
    <?php if (!$isMedico): ?>
        <a href="patients_create.php"
            class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
            + Nuevo Paciente
        </a>
    <?php endif; ?>
</div>

<?php if ($isMedico): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
        <form method="GET" class="flex gap-4">
            <div class="w-48">
                <select name="type" id="idType"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#007BFF] outline-none bg-white">
                    <option value="Cedula">Cédula</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>
            <input type="text" name="search" id="idSearch" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Ingrese Cédula/Pasaporte"
                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#007BFF] outline-none">
            <button type="submit"
                class="bg-[#28A745] text-white px-8 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all">
                Buscar
            </button>
        </form>
    </div>
<?php endif; ?>

<?php if ($isMedico && empty($search)): ?>
    <div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
        <p class="text-[#6C757D]">Ingrese una identificación para consultar los datos y el historial del paciente.</p>
    </div>
<?php elseif ($isMedico && !empty($search) && empty($pacientes)): ?>
    <div class="bg-white p-12 rounded-2xl shadow-sm border border-gray-100 text-center">
        <p class="text-red-500 font-medium">No se encontró ningún paciente con la identificación proporcionada.</p>
    </div>
<?php else: ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <table id="pacientesTable" class="display w-full">
            <thead>
                <tr class="text-left text-[#6C757D] border-b">
                    <th class="py-4">Nombre</th>
                    <th class="py-4">Identificación</th>
                    <th class="py-4">Teléfono</th>
                    <th class="py-4">Género</th>
                    <th class="py-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pacientes as $paciente): ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                        <td class="py-4 font-medium">
                            <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?>
                        </td>
                        <td class="py-4 text-[#6C757D]">
                            <?php echo htmlspecialchars($paciente['identificacion']); ?>
                        </td>
                        <td class="py-4 text-[#6C757D]">
                            <?php echo htmlspecialchars($paciente['telefono'] ?: '-'); ?>
                        </td>
                        <td class="py-4">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold 
                            <?php echo $paciente['genero'] === 'Masculino' ? 'bg-blue-100 text-blue-600' : ($paciente['genero'] === 'Femenino' ? 'bg-pink-100 text-pink-600' : 'bg-gray-100 text-gray-600'); ?>">
                                <?php echo $paciente['genero']; ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <?php if ($isMedico): ?>
                                <a href="patient_portal.php?patient_id=<?php echo $paciente['id']; ?>"
                                    class="text-[#28A745] hover:underline font-medium">Ver Historial</a>
                            <?php else: ?>
                                <a href="patients_edit.php?id=<?php echo $paciente['id']; ?>"
                                    class="text-[#007BFF] hover:underline font-medium">Editar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script src="https://unpkg.com/imask"></script>
<script>
    $(document).ready(function () {
        $('#pacientesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            responsive: true,
            dom: '<?php echo $isMedico ? "t" : "<\"flex justify-between mb-4\"fl>rt<\"flex justify-between mt-4\"ip>"; ?>'
        });

        // Máscara para Cédula
        const idSearch = document.getElementById('idSearch');
        const idType = document.getElementById('idType');
        let mask = null;

        function updateMask() {
            if (mask) mask.destroy();
            if (idType.value === 'Cedula') {
                mask = IMask(idSearch, { mask: '000-0000000-0' });
            } else {
                mask = null;
            }
        }

        if (idType) {
            idType.addEventListener('change', updateMask);
            updateMask();
        }
    });
</script>

<?php include '../views/layout/footer.php'; ?>
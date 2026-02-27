<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';
require_once '../app/autoload.php';

checkRole(['administrador', 'recepcionista']);

$pageTitle = 'Médicos - HospitAll';
$activePage = 'medicos';
$headerTitle = 'Gestión de Médicos';
$headerSubtitle = 'Listado y administración de profesionales médicos.';

try {
    $stmt = $pdo->query("SELECT * FROM medicos ORDER BY created_at DESC");
    $medicos = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicos = [];
}

include '../views/layout/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <h3 class="text-xl font-bold text-[#212529]">Listado de Médicos</h3>
    <?php if ($_SESSION['user_role'] === 'administrador'): ?>
        <a href="doctors_create.php"
            class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
            + Nuevo Médico
        </a>
    <?php endif; ?>
</div>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <table id="medicosTable" class="display w-full">
        <thead>
            <tr class="text-left text-[#6C757D] border-b">
                <th class="py-4">Nombre</th>
                <th class="py-4">Especialidad</th>
                <th class="py-4">Teléfono</th>
                <th class="py-4">Correo</th>
                <th class="py-4">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medicos as $medico): ?>
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4 font-medium">
                        <?php echo htmlspecialchars($medico['nombre'] . ' ' . $medico['apellido']); ?>
                    </td>
                    <td class="py-4 font-semibold text-[#007BFF]">
                        <?php echo htmlspecialchars($medico['especialidad']); ?>
                    </td>
                    <td class="py-4 text-[#6C757D]">
                        <?php echo htmlspecialchars($medico['telefono'] ?: '-'); ?>
                    </td>
                    <td class="py-4 text-[#6C757D]">
                        <?php echo htmlspecialchars($medico['correo_electronico'] ?: '-'); ?>
                    </td>
                    <td class="py-4">
                        <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                            <a href="doctors_edit.php?id=<?php echo $medico['id']; ?>"
                                class="text-[#007BFF] hover:underline font-medium">Editar</a>
                        <?php else: ?>
                            <span class="text-gray-400 font-medium cursor-not-allowed">Solo lectura</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        $('#medicosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            responsive: true,
            dom: '<"flex justify-between mb-4"fl>rt<"flex justify-between mt-4"ip>'
        });
    });
</script>

<?php include '../views/layout/footer.php'; ?>
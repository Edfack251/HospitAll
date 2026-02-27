<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';
require_once '../app/autoload.php';

checkRole(['administrador']);

$controller = new UsersController($pdo);
$usuarios = $controller->index();

$pageTitle = 'Gestión de Usuarios - HospitAll';
$activePage = 'usuarios';
$headerTitle = 'Gestión de Usuarios';
$headerSubtitle = 'Administra las cuentas de usuario y sus roles en el sistema.';
include '../views/layout/header.php';
?>

<div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-[#212529]">Listado de Usuarios</h3>
        <a href="users_create.php"
            class="bg-[#007BFF] text-white px-4 py-2 rounded-lg font-semibold hover:bg-[#0056b3] transition-all flex items-center shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Usuario
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">
                <?php echo $error; ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table id="usersTable" class="w-full text-left">
            <thead>
                <tr class="text-[#6C757D] text-sm border-b">
                    <th class="pb-4 font-medium">Nombre Completo</th>
                    <th class="pb-4 font-medium">Correo Electrónico</th>
                    <th class="pb-4 font-medium">Rol</th>
                    <th class="pb-4 font-medium">Fecha de Registro</th>
                    <th class="pb-4 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($usuarios as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 font-medium text-[#212529]">
                            <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                        </td>
                        <td class="py-4 text-[#495057]">
                            <?php echo htmlspecialchars($u['correo_electronico']); ?>
                        </td>
                        <td class="py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                <?php
                                switch (strtolower($u['rol_nombre'])) {
                                    case 'administrador':
                                        echo 'bg-purple-100 text-purple-600';
                                        break;
                                    case 'medico':
                                        echo 'bg-blue-100 text-blue-600';
                                        break;
                                    case 'recepcionista':
                                        echo 'bg-green-100 text-green-600';
                                        break;
                                    case 'tecnico_laboratorio':
                                        echo 'bg-orange-100 text-orange-600';
                                        break;
                                    case 'paciente':
                                        echo 'bg-gray-100 text-gray-600';
                                        break;
                                    default:
                                        echo 'bg-gray-50 text-gray-400';
                                }
                                ?>">
                                <?php echo htmlspecialchars($u['rol_nombre'] ?: 'Sin rol'); ?>
                            </span>
                        </td>
                        <td class="py-4 text-[#495057]">
                            <?php echo date('d/m/Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td class="py-4 text-right">
                            <a href="users_edit.php?id=<?php echo $u['id']; ?>"
                                class="text-[#007BFF] hover:underline font-medium mr-3">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#usersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [[3, 'desc']]
        });
    });
</script>

<?php include '../views/layout/footer.php'; ?>
<?php
use App\Controllers\UsersController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador']);

$controller = new UsersController($pdo);
$formData = $controller->getFormData(true);
$roles = $formData['roles'];

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
    $result = $controller->create($_POST);
    if ($result === "Usuario creado correctamente.") {
        $success = $result;
    } else {
        $error = $result;
    }
}

$pageTitle = 'Nuevo Usuario - HospitAll';
$activePage = 'usuarios';
$headerTitle = 'Registrar Nuevo Usuario';
$headerSubtitle = 'Completa el formulario para registrar un nuevo miembro del personal.';
include __DIR__ . '/../layout/header.php';
?>

<div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <div class="mb-6">
        <a href="<?php echo App\Helpers\UrlHelper::url('users'); ?>"
            class="text-[#6C757D] hover:text-[#007BFF] flex items-center text-sm font-medium transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al listado
        </a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <p>
                <?php echo $error; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <p>
                <?php echo $success; ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <?php $csrf = CsrfHelper::generateToken(); ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-[#495057]">Nombre</label>
                <input type="text" name="nombre" required
                    class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-[#495057]">Apellido</label>
                <input type="text" name="apellido" required
                    class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2 text-[#495057]">Correo Electrónico</label>
            <input type="email" name="correo_electronico" required
                class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2 text-[#495057]">Contraseña</label>
            <input type="password" name="password" required
                class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2 text-[#495057]">Rol del Sistema</label>
            <select name="rol_id" required
                class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all bg-white">
                <option value="">Seleccione un rol...</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?php echo $rol['id']; ?>">
                        <?php echo ucfirst($rol['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pt-4">
            <button type="submit"
                class="w-full bg-[#007BFF] text-white py-3 rounded-lg font-bold shadow-md hover:bg-[#0056b3] transition-all transform hover:-translate-y-0.5">
                Crear Usuario
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
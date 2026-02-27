<?php
session_start();
require_once '../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Helpers\AuthHelper;

AuthHelper::checkRole(['administrador']);

$pageTitle = 'Nuevo Médico - HospitAll';
$activePage = 'medicos';
$headerTitle = 'Registrar Médico';
$headerSubtitle = 'Ingresa los datos del nuevo profesional.';

include '../views/layout/header.php';
?>

<div class="max-w-2xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <form action="api/doctors.php" method="POST" class="grid grid-cols-2 gap-6">
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Nombre</label>
            <input type="text" name="nombre" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Apellido</label>
            <input type="text" name="apellido" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-2">
            <label class="block text-sm font-medium mb-2">Especialidad</label>
            <input type="text" name="especialidad" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Teléfono</label>
            <input type="text" name="telefono" id="doctorPhone" placeholder="(000) 000-0000"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Correo Electrónico</label>
            <input type="email" name="correo_electronico"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-2 flex justify-end space-x-4 mt-4">
            <a href="doctors.php"
                class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</a>
            <button type="submit"
                class="bg-[#28A745] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-green-700 transition-all">
                Guardar Médico
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('doctorPhone').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) value = value.slice(0, 10);

        let masked = '';
        if (value.length > 0) masked += '(' + value.slice(0, 3);
        if (value.length > 3) masked += ') ' + value.slice(3, 6);
        if (value.length > 6) masked += '-' + value.slice(6, 10);

        e.target.value = masked;
    });
</script>

<?php include '../views/layout/footer.php'; ?>
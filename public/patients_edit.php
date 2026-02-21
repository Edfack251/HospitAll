<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/helpers/auth_helper.php';

checkRole(['administrador', 'recepcionista']);

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: patients.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id]);
    $paciente = $stmt->fetch();
    if (!$paciente) {
        header("Location: patients.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = 'Editar Paciente - HospitAll';
$activePage = 'pacientes';
$headerTitle = 'Editar Paciente';
$headerSubtitle = 'Actualiza la información del paciente.';

include '../views/layout/header.php';
?>

<div class="max-w-2xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <form action="api/patients_edit_api.php" method="POST" class="grid grid-cols-2 gap-6">
        <input type="hidden" name="id" value="<?php echo $paciente['id']; ?>">
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Tipo de Identificación</label>
            <select name="identificacion_tipo" id="idType" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                <option value="Cédula" <?php echo $paciente['identificacion_tipo'] === 'Cédula' ? 'selected' : ''; ?>>
                    Cédula</option>
                <option value="Pasaporte" <?php echo $paciente['identificacion_tipo'] === 'Pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
            </select>
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Número</label>
            <input type="text" name="identificacion" id="idNumber"
                value="<?php echo htmlspecialchars($paciente['identificacion']); ?>" required
                placeholder="<?php echo $paciente['identificacion_tipo'] === 'Cédula' ? '000-0000000-0' : 'Número de Pasaporte'; ?>"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Nombre</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($paciente['nombre']); ?>" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Apellido</label>
            <input type="text" name="apellido" value="<?php echo htmlspecialchars($paciente['apellido']); ?>" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" value="<?php echo $paciente['fecha_nacimiento']; ?>" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Género</label>
            <select name="genero" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                <option value="Masculino" <?php echo $paciente['genero'] === 'Masculino' ? 'selected' : ''; ?>>Masculino
                </option>
                <option value="Femenino" <?php echo $paciente['genero'] === 'Femenino' ? 'selected' : ''; ?>>Femenino
                </option>
                <option value="Otro" <?php echo $paciente['genero'] === 'Otro' ? 'selected' : ''; ?>>Otro</option>
            </select>
        </div>
        <div class="col-span-2">
            <label class="block text-sm font-medium mb-2">Dirección</label>
            <textarea name="direccion"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                rows="2"><?php echo htmlspecialchars($paciente['direccion']); ?></textarea>
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Teléfono</label>
            <input type="text" name="telefono" id="patientPhone"
                value="<?php echo htmlspecialchars($paciente['telefono']); ?>" placeholder="(000) 000-0000"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Correo Electrónico</label>
            <input type="email" name="correo_electronico"
                value="<?php echo htmlspecialchars($paciente['correo_electronico']); ?>"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-2 flex justify-end space-x-4 mt-4">
            <a href="patients.php"
                class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</a>
            <button type="submit"
                class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
                Actualizar Paciente
            </button>
        </div>
    </form>
</div>

<script>
    const idType = document.getElementById('idType');
    const idNumber = document.getElementById('idNumber');

    idNumber.addEventListener('input', function (e) {
        if (idType.value === 'Cédula') {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            let masked = '';
            if (value.length > 0) masked += value.slice(0, 3);
            if (value.length > 3) masked += '-' + value.slice(3, 10);
            if (value.length > 10) masked += '-' + value.slice(10, 11);

            e.target.value = masked;
        }
    });

    idType.addEventListener('change', function () {
        idNumber.value = '';
        idNumber.placeholder = idType.value === 'Cédula' ? '000-0000000-0' : 'Número de Pasaporte';
    });

    document.getElementById('patientPhone').addEventListener('input', function (e) {
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
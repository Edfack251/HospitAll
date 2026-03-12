<?php
use App\Helpers\UrlHelper;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista']);

$pageTitle = 'Nuevo Paciente - HospitAll';
$activePage = 'pacientes';
$headerTitle = 'Registrar Paciente';
$headerSubtitle = 'Ingresa los datos del nuevo paciente.';

include __DIR__ . '/../layout/header.php';
?>

<div class="max-w-2xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
    <form action="<?php echo UrlHelper::url('api/patients/create'); ?>" method="POST" class="grid grid-cols-2 gap-6">
        <?php $csrf = CsrfHelper::generateToken(); ?>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Tipo de Identificación</label>
            <select name="identificacion_tipo" id="idType" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                <option value="Cédula">Cédula</option>
                <option value="Pasaporte">Pasaporte</option>
            </select>
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Número</label>
            <input type="text" name="identificacion" id="idNumber" required placeholder="000-0000000-0"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
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
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Fecha de Nacimiento</label>
            <input type="date" name="fecha_nacimiento" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Género</label>
            <select name="genero" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
                <option value="Otro">Otro</option>
            </select>
        </div>
        <div class="col-span-2">
            <label class="block text-sm font-medium mb-2">Dirección</label>
            <textarea name="direccion"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none"
                rows="2"></textarea>
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Teléfono</label>
            <input type="text" name="telefono" id="patientPhone" placeholder="(000) 000-0000"
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>
        <div class="col-span-1">
            <label class="block text-sm font-medium mb-2">Correo Electrónico</label>
            <input type="email" name="correo_electronico" required
                class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
        </div>

        <!-- Identity and Medical Data -->
        <div class="col-span-2 p-4 bg-gray-50 rounded-xl border border-gray-100 mb-2">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Datos Médicos y de Identidad
                (Opcional)</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Grupo Sanguíneo</label>
                    <select name="grupo_sanguineo"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white">
                        <option value="">Seleccionar...</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Estado Civil</label>
                    <select name="estado_civil"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white">
                        <option value="Soltero">Soltero(a)</option>
                        <option value="Casado">Casado(a)</option>
                        <option value="Divorciado">Divorciado(a)</option>
                        <option value="Viudo">Viudo(a)</option>
                        <option value="Unión Libre">Unión Libre</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-2">Alergias</label>
                    <textarea name="alergias"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white"
                        rows="2" placeholder="Describa si el paciente tiene alergias conocidas..."></textarea>
                </div>
            </div>

            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-4 mb-3">Contacto de Emergencia</h4>
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="block text-sm font-medium mb-2">Nombre</label>
                    <input type="text" name="contacto_emergencia_nombre"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white">
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium mb-2">Parentesco</label>
                    <input type="text" name="contacto_emergencia_parentesco"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white"
                        placeholder="Ej. Padre, Esposa...">
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium mb-2">Teléfono</label>
                    <input type="text" name="contacto_emergencia_telefono"
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white">
                </div>
            </div>
        </div>

        <div class="col-span-2 p-4 bg-blue-50/50 rounded-xl border border-blue-100">
            <h4 class="text-xs font-bold text-blue-800 uppercase tracking-widest mb-3">Acceso al Portal</h4>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Contraseña Inicial</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none bg-white"
                        placeholder="Define una contraseña para el paciente">
                    <p class="text-[10px] text-blue-600 mt-2">Se creará automáticamente una cuenta de usuario vinculada
                        a este paciente para que pueda acceder a su portal.</p>
                </div>
            </div>
        </div>
        <div class="col-span-2 flex justify-end space-x-4 mt-4">
            <a href="<?php echo App\Helpers\UrlHelper::url('patients'); ?>"
                class="px-6 py-2 rounded-lg border font-semibold text-[#6C757D] hover:bg-gray-50">Cancelar</a>
            <button type="submit"
                class="bg-[#007BFF] text-white px-6 py-2 rounded-lg font-semibold shadow-md hover:bg-blue-700 transition-all">
                Guardar Paciente
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

<?php include __DIR__ . '/../layout/footer.php'; ?>
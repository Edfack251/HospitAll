<?php
require_once '../app/autoload.php';
use App\Helpers\CsrfHelper;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - HospitAll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F9FA;
            color: #212529;
        }

        .primary-btn {
            background-color: #007BFF;
            transition: all 0.3s ease;
        }

        .primary-btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="h-screen flex items-center justify-center p-6">
    <div class="glass w-full max-w-lg p-8 rounded-2xl shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#007BFF]">HospitAll</h1>
            <p class="text-[#6C757D] mt-2">Crea una cuenta para comenzar</p>
        </div>

        <form action="api/register.php" method="POST" class="grid grid-cols-2 gap-4">
            <?php $csrf = CsrfHelper::generateToken(); ?>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Tipo de Identificación</label>
                <select name="identificacion_tipo" id="idType" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                    <option value="Cédula">Cédula</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Número</label>
                <input type="text" name="identificacion" id="idNumber" required placeholder="000-0000000-0"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Nombre</label>
                <input type="text" name="nombre" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Apellido</label>
                <input type="text" name="apellido" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Género</label>
                <select name="genero" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium mb-1">Teléfono</label>
                <input type="text" name="telefono" id="userPhone" placeholder="(000) 000-0000"
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Correo electrónico</label>
                <input type="email" name="correo_electronico" required
                    class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none">
            </div>
            <button type="submit"
                class="col-span-2 primary-btn py-3 rounded-lg text-white font-semibold shadow-md mt-4">
                Crear Cuenta
            </button>
        </form>

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

            document.getElementById('userPhone').addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) value = value.slice(0, 10);

                let masked = '';
                if (value.length > 0) masked += '(' + value.slice(0, 3);
                if (value.length > 3) masked += ') ' + value.slice(3, 6);
                if (value.length > 6) masked += '-' + value.slice(6, 10);

                e.target.value = masked;
            });
        </script>

        <div class="mt-8 text-center text-sm text-[#6C757D]">
            ¿Ya tienes una cuenta? <a href="login.php" class="text-[#007BFF] font-semibold hover:underline">Inicia
                sesión</a>
        </div>
    </div>
</body>

</html>
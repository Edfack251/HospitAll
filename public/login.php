<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HospitAll</title>
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
    <div class="glass w-full max-w-md p-8 rounded-2xl shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#007BFF]">HospitAll</h1>
            <p class="text-[#6C757D] mt-2">Bienvenido de nuevo, por favor inicia sesión</p>
        </div>

        <form action="api/login.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2">Correo electrónico</label>
                <input type="email" name="correo_electronico" required
                    class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-[#007BFF] outline-none transition-all">
            </div>
            <button type="submit" class="primary-btn w-full py-3 rounded-lg text-white font-semibold shadow-md">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-[#6C757D]">
            ¿No tienes una cuenta? <a href="register.php"
                class="text-[#007BFF] font-semibold hover:underline">Regístrate aquí</a>
        </div>
    </div>
</body>

</html>
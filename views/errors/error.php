<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - HospitAll</title>
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
    <div class="glass w-full max-w-md p-10 rounded-2xl shadow-xl text-center">
        <div class="mb-6">
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">¡Ups! Algo salió mal</h1>
            <p class="text-[#6C757D] mt-4">Ha ocurrido un error inesperado.</p>
        </div>

        <a href="javascript:history.back()"
            class="primary-btn inline-block w-full py-3 rounded-lg text-white font-semibold shadow-md">
            Volver
        </a>

        <div class="mt-6 text-sm text-[#6C757D]">
            Si el problema persiste, por favor contacte con soporte técnico.
        </div>
    </div>
</body>

</html>
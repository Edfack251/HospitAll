<?php
use App\Helpers\UrlHelper;

if (!isset($_SESSION['user_id'])) {
    UrlHelper::redirect('login');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle ?? 'HospitAll'; ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DataTables CSS con SRI -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"
        crossorigin="anonymous">
    <!-- jQuery con SRI -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" crossorigin="anonymous"></script>
    <!-- DataTables JS con SRI -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?php echo UrlHelper::url('css/app.css'); ?>">
    <script src="<?php echo UrlHelper::url('js/app.js'); ?>"></script>
</head>

<body class="flex flex-col md:flex-row min-h-screen md:h-screen">
    <!-- Mobile menu toggle -->
    <button id="sidebarToggle" type="button" aria-label="Abrir menú"
        class="md:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-white border border-gray-200 shadow-sm text-[#007BFF] hover:bg-gray-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
    <!-- Sidebar overlay (mobile) -->
    <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/50 z-40 md:hidden" aria-hidden="true"></div>
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar w-64 flex-shrink-0 flex flex-col fixed md:relative inset-y-0 left-0 z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:transition-none">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-[#007BFF]">HospitAll</h1>
        </div>
        <nav class="flex-1 px-4 space-y-2">
            <?php
            $role = $_SESSION['user_role'] ?? '';

            // Dashboard
            if ($role === 'administrador') {
                echo '<a href="' . UrlHelper::url('api/admin/dashboard') . '" class="nav-link ' . ($activePage === 'admin_dashboard' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Inicio
                </a>';
            } elseif ($role === 'medico') {
                echo '<a href="' . UrlHelper::url('api/doctor/dashboard') . '" class="nav-link ' . ($activePage === 'doctor_dashboard' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Panel Médico
                </a>';
            }

            // Pacientes (Admin, Recepcionista, Médico)
            if (in_array($role, ['administrador', 'recepcionista', 'medico'])) {
                echo '<a href="' . UrlHelper::url('patients') . '" class="nav-link ' . ($activePage === 'pacientes' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Pacientes
                </a>';
            }

            // Flujo Clínico
            if (in_array($role, ['administrador', 'recepcionista', 'medico', 'enfermera'])) {
                echo '<a href="' . UrlHelper::url('patient-flow') . '" class="nav-link ' . ($activePage === 'patient_flow' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Flujo Clínico
                </a>';
            }

            // Médicos (Admin only)
            if (in_array($role, ['administrador'])) {
                echo '<a href="' . UrlHelper::url('doctors') . '" class="nav-link ' . ($activePage === 'medicos' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Médicos
                </a>';
            }

            // Citas Médicas / Agenda
            if (in_array($role, ['administrador', 'recepcionista', 'medico'])) {
                $citasUrl = ($role === 'medico') ? 'doctor_agenda' : 'appointments';
                $citasText = ($role === 'medico') ? 'Mi Agenda' : 'Citas Médicas';
                echo '<a href="' . UrlHelper::url($citasUrl) . '" class="nav-link ' . ($activePage === 'citas' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    ' . $citasText . '
                </a>';
            }

            // Laboratorio (Admin, Tecnico, Recepcionista)
            if (in_array($role, ['administrador', 'tecnico_laboratorio', 'recepcionista'])) {
                echo '<a href="' . UrlHelper::url('laboratory') . '" class="nav-link ' . ($activePage === 'laboratorio' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    Laboratorio
                </a>';
            }

            // Farmacia (Farmaceutico, Administrador)
            if (in_array($role, ['farmaceutico', 'administrador'])) {
                echo '<a href="' . UrlHelper::url('pharmacy') . '" class="nav-link ' . ($activePage === 'farmacia' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Farmacia
                </a>';
            }

            // Facturación (Recepcionista, Farmaceutico)
            if (in_array($role, ['administrador', 'recepcionista', 'farmaceutico'])) {
                echo '<a href="' . UrlHelper::url('billing') . '" class="nav-link ' . ($activePage === 'facturacion' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Facturación
                </a>';
            }

            // Gestión de Usuarios (Admin only)
            if ($role === 'administrador') {
                echo '<a href="' . UrlHelper::url('users') . '" class="nav-link ' . ($activePage === 'usuarios' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuarios
                </a>';
                echo '<a href="' . UrlHelper::url('logs') . '" class="nav-link ' . ($activePage === 'auditoria' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Auditoría
                </a>';
            }

            // Mi Portal (Paciente only)
            if ($role === 'paciente') {
                echo '<a href="' . UrlHelper::url('patient_portal') . '" class="nav-link ' . ($activePage === 'portal' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Mi Portal
                </a>';
            }
            ?>
        </nav>
        <div class="p-4 border-t">
            <div class="flex items-center p-2">
                <div class="ml-3">
                    <p class="text-sm font-semibold text-[#212529]">
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </p>
                    <p class="text-xs text-[#6C757D]">
                        <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Usuario'); ?>
                    </p>
                </div>
            </div>
            <a href="<?php echo UrlHelper::url('logout'); ?>"
                class="mt-4 flex items-center text-sm text-red-500 font-medium p-2 hover:bg-red-50 rounded-lg">
                Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-10 pt-16 md:pt-10 min-w-0">
        <header class="mb-6 md:mb-10">
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold bg-gradient-to-r from-[#007BFF] to-[#28A745] bg-clip-text text-transparent">
                <?php echo $headerTitle ?? 'Panel'; ?>
            </h2>
            <p class="text-[#6C757D] mt-2">
                <?php echo $headerSubtitle ?? ''; ?>
            </p>
        </header>
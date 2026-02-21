<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F9FA;
        }

        .sidebar {
            background-color: #FFFFFF;
            border-right: 1px solid #E5E7EB;
        }

        .nav-link {
            color: #6C757D;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #007BFF;
            background-color: #F0F7FF;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007BFF !important;
            color: white !important;
            border: none !important;
            border-radius: 8px;
        }
    </style>
</head>

<body class="flex h-screen">
    <!-- Sidebar -->
    <aside class="sidebar w-64 flex-shrink-0 flex flex-col">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-[#007BFF]">HospitAll</h1>
        </div>
        <nav class="flex-1 px-4 space-y-2">
            <?php
            $role = $_SESSION['user_role'] ?? '';

            // Dashboard (Admin and staff)
            if ($role === 'administrador') {
                echo '<a href="dashboard.php" class="nav-link ' . ($activePage === 'dashboard' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Inicio
                </a>';
            }

            // Pacientes (Admin, Recepcionista, Médico)
            if (in_array($role, ['administrador', 'recepcionista', 'medico'])) {
                echo '<a href="patients.php" class="nav-link ' . ($activePage === 'pacientes' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Pacientes
                </a>';
            }

            // Médicos (Admin, Recepcionista)
            if (in_array($role, ['administrador', 'recepcionista'])) {
                echo '<a href="doctors.php" class="nav-link ' . ($activePage === 'medicos' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Médicos
                </a>';
            }

            // Citas Médicas / Agenda
            if (in_array($role, ['administrador', 'recepcionista', 'medico'])) {
                $citasUrl = ($role === 'medico') ? 'doctor_agenda.php' : 'appointments.php';
                $citasText = ($role === 'medico') ? 'Mi Agenda' : 'Citas Médicas';
                echo '<a href="' . $citasUrl . '" class="nav-link ' . ($activePage === 'citas' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    ' . $citasText . '
                </a>';
            }

            // Laboratorio (Admin, Tecnico)
            if (in_array($role, ['administrador', 'tecnico_laboratorio'])) {
                echo '<a href="laboratory.php" class="nav-link ' . ($activePage === 'laboratorio' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    Laboratorio
                </a>';
            }

            // Gestión de Usuarios (Admin only)
            if ($role === 'administrador') {
                echo '<a href="users.php" class="nav-link ' . ($activePage === 'usuarios' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuarios
                </a>';
            }

            // Mi Portal (Paciente only)
            if ($role === 'paciente') {
                echo '<a href="patient_portal.php" class="nav-link ' . ($activePage === 'portal' ? 'active' : '') . ' flex items-center p-3 rounded-lg font-medium">
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
            <a href="logout.php"
                class="mt-4 flex items-center text-sm text-red-500 font-medium p-2 hover:bg-red-50 rounded-lg">
                Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-10">
        <header class="mb-10">
            <h2 class="text-3xl font-bold bg-gradient-to-r from-[#007BFF] to-[#28A745] bg-clip-text text-transparent">
                <?php echo $headerTitle ?? 'Panel'; ?>
            </h2>
            <p class="text-[#6C757D] mt-2">
                <?php echo $headerSubtitle ?? ''; ?>
            </p>
        </header>
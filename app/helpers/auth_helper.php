<?php
function checkRole($allowedRoles)
{
    requireLogin();

    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}

function requireLogin()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Tiempo de inactividad máximo (15 minutos)
    $timeout_duration = 15 * 60;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=session_expired");
        exit();
    }

    // Actualizar el tiempo de última actividad
    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Cabeceras para evitar el caché del navegador (Problema del botón "atrás")
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
}

function requireRole($role)
{
    requireLogin();

    if ($_SESSION['user_role'] !== $role) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}
?>
<?php
function checkRole($allowedRoles)
{
    if (!isset($_SESSION['user_role'])) {
        header("Location: login.php");
        exit();
    }

    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}
?>
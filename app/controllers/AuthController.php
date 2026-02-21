<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo_electronico = $_POST['correo_electronico'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.correo_electronico = ?");
        $stmt->execute([$correo_electronico]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_role'] = strtolower($user['rol_nombre']);

            switch ($_SESSION['user_role']) {
                case 'paciente':
                    $stmt_paciente = $pdo->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
                    $stmt_paciente->execute([$user['id']]);
                    $_SESSION['paciente_id'] = $stmt_paciente->fetchColumn();
                    header("Location: ../patient_portal.php");
                    break;
                case 'medico':
                    // Obtener el ID del médico asociado por correo electrónico
                    $stmt_medico = $pdo->prepare("SELECT id FROM medicos WHERE correo_electronico = ?");
                    $stmt_medico->execute([$user['correo_electronico']]);
                    $_SESSION['medico_id'] = $stmt_medico->fetchColumn();
                    header("Location: ../doctor_agenda.php");
                    break;
                case 'recepcionista':
                    header("Location: ../appointments.php");
                    break;
                case 'tecnico_laboratorio':
                    header("Location: ../laboratory.php");
                    break;
                case 'administrador':
                    header("Location: ../dashboard.php");
                    break;
                default:
                    header("Location: ../login.php?error=rol_invalido");
                    break;
            }
            exit();
        } else {
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
    } catch (PDOException $e) {
        die("Error en el login: " . $e->getMessage());
    }
}
?>
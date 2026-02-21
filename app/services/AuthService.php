<?php

class AuthService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function attemptLogin($email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.correo_electronico = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_role'] = strtolower($user['rol_nombre']);
            $_SESSION['last_activity'] = time();

            $this->setSpecificIds($user);
            return $user;
        }
        return false;
    }

    private function setSpecificIds($user)
    {
        if ($_SESSION['user_role'] === 'paciente') {
            $stmt = $this->pdo->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['paciente_id'] = $stmt->fetchColumn();
        } elseif ($_SESSION['user_role'] === 'medico') {
            $stmt = $this->pdo->prepare("SELECT id FROM medicos WHERE correo_electronico = ?");
            $stmt->execute([$user['correo_electronico']]);
            $_SESSION['medico_id'] = $stmt->fetchColumn();
        }
    }

    public function registerPatient($data)
    {
        try {
            $this->pdo->beginTransaction();

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Obtener el ID del rol 'paciente' (o 'Paciente')
            $stmt_rol = $this->pdo->prepare("SELECT id FROM roles WHERE LOWER(nombre) = 'paciente' LIMIT 1");
            $stmt_rol->execute();
            $rol_id = $stmt_rol->fetchColumn();

            if (!$rol_id)
                throw new Exception("Rol Paciente no encontrado.");

            // 1. Crear el usuario
            $sql_usuario = "INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) VALUES (?, ?, ?, ?, ?)";
            $stmt_usuario = $this->pdo->prepare($sql_usuario);
            $stmt_usuario->execute([
                $data['nombre'],
                $data['apellido'],
                $data['correo_electronico'],
                $password_hash,
                $rol_id
            ]);
            $usuario_id = $this->pdo->lastInsertId();

            // 2. Crear el paciente asociado
            $sql_paciente = "INSERT INTO pacientes (usuario_id, identificacion, identificacion_tipo, nombre, apellido, correo_electronico, fecha_nacimiento, genero, telefono) 
                             VALUES (?, ?, ?, ?, ?, ?, '1900-01-01', ?, ?)";
            $stmt_paciente = $this->pdo->prepare($sql_paciente);
            $stmt_paciente->execute([
                $usuario_id,
                $data['identificacion'],
                $data['identificacion_tipo'],
                $data['nombre'],
                $data['apellido'],
                $data['correo_electronico'],
                $data['genero'],
                $data['telefono']
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction())
                $this->pdo->rollBack();
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico o la identificación ya están registrados.");
            }
            throw $e;
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar todos los datos de sesión
        $_SESSION = array();

        // Borrar la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        header("Location: login.php");
        exit();
    }
}

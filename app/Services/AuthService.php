<?php
namespace App\Services;

use Exception;
use PDO;
use App\Core\Validator;
use App\Repositories\AuthRepository;

class AuthService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new AuthRepository($pdo);
    }

    public function attemptLogin($email, $password)
    {
        $user = $this->repo->getUserByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = strtolower($user['rol_nombre']);
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['last_activity'] = time();

            // Store user object for PolicyManager and other parts of the system
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'apellido' => $user['apellido'],
                'correo_electronico' => $user['correo_electronico'],
                'role' => strtolower($user['rol_nombre'])
            ];

            $this->setSpecificIds($user);

            // Auditoría: Login exitoso
            $logService = new \App\Services\LogService($this->pdo);
            $logService->register($user['id'], 'Login exitoso', 'Auth');

            return $user;
        }
        return false;
    }

    private function setSpecificIds($user)
    {
        if ($_SESSION['user_role'] === 'paciente') {
            $_SESSION['paciente_id'] = $this->repo->getPatientIdByUserId($user['id']);
        } elseif ($_SESSION['user_role'] === 'medico') {
            $_SESSION['medico_id'] = $this->repo->getDoctorIdByEmail($user['correo_electronico']);
        }
    }

    public function registerPatient($data)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        Validator::validate($data, [
            'identificacion' => 'required',
            'identificacion_tipo' => 'required',
            'nombre' => 'required',
            'apellido' => 'required',
            'correo_electronico' => 'required|email',
            'genero' => 'required',
            'password' => 'required|min:6'
        ]);

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Obtener el ID del rol 'paciente' (o 'Paciente')
            $rol_id = $this->repo->getPatientRoleId();

            if (!$rol_id)
                throw new Exception("Rol Paciente no encontrado.");

            $userData = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo_electronico' => $data['correo_electronico'],
                'password_hash' => $password_hash,
                'rol_id' => $rol_id
            ];

            $patientData = [
                'identificacion' => $data['identificacion'],
                'identificacion_tipo' => $data['identificacion_tipo'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo_electronico' => $data['correo_electronico'],
                'genero' => $data['genero'],
                'telefono' => $data['telefono']
            ];

            // 1 y 2. Crear el usuario asociado al paciente
            $usuario_id = $this->repo->createPatientUser($userData, $patientData);

            $this->pdo->commit();

            // Auditoría: Nuevo paciente registrado
            $logService = new LogService($this->pdo);
            $logService->register($usuario_id, 'Registro de paciente', 'Auth', "Se registró el paciente ID: {$data['identificacion']}");

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
        \App\Helpers\UrlHelper::redirect('login');
    }
}

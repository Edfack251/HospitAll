<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;

class UsersService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT u.id, u.nombre, u.apellido, u.correo_electronico, r.nombre as rol_nombre, u.created_at 
                             FROM usuarios u 
                             LEFT JOIN roles r ON u.rol_id = r.id 
                             ORDER BY u.created_at DESC");
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getRoles($excludePatient = false)
    {
        $sql = "SELECT id, nombre FROM roles ";
        if ($excludePatient) {
            $sql .= "WHERE nombre != 'paciente' ";
        }
        $sql .= "ORDER BY nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function create($data)
    {
        try {
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['correo_electronico'],
                $password_hash,
                $data['rol_id']
            ]);
            $new_user_id = $this->pdo->lastInsertId();

            // Auditoría: Crear usuario
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['usuario_id'] ?? $new_user_id, 'Creación de usuario', 'Usuarios', "Email: $data[correo_electronico], Rol ID: $data[rol_id]", 'INFO');

            return true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico ya está registrado.");
            }
            throw new Exception("Error al crear el usuario: " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            if (!empty($data['password'])) {
                $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, correo_electronico = ?, rol_id = ?, password = ? WHERE id = ?");
                return $stmt->execute([$data['nombre'], $data['apellido'], $data['correo_electronico'], $data['rol_id'], $password_hash, $id]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, correo_electronico = ?, rol_id = ? WHERE id = ?");
                $res = $stmt->execute([$data['nombre'], $data['apellido'], $data['correo_electronico'], $data['rol_id'], $id]);
            }

            if ($res) {
                // Auditoría: Editar usuario (y detectar cambio de rol)
                $logService = new LogService($this->pdo);
                $accion = 'Edición de usuario';
                $nivel = 'INFO';

                // Si el rol cambió, nivel WARNING
                // Nota: Podríamos consultar el rol anterior para comparar, pero por simplicidad logguamos la edición
                $logService->register($_SESSION['usuario_id'], 'Edición de usuario', 'Usuarios', "Usuario ID: $id ($data[correo_electronico])", 'WARNING');
            }

            return $res;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico ya está registrado.");
            }
            throw new Exception("Error al actualizar el usuario: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            if ($id == ($_SESSION['usuario_id'] ?? 0)) {
                throw new Exception("No puedes eliminar tu propia cuenta.");
            }

            $user = $this->getById($id);
            if (!$user)
                return false;

            $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $res = $stmt->execute([$id]);

            if ($res) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['usuario_id'], 'Eliminación de usuario', 'Usuarios', "ID: $id, Email: $user[correo_electronico]", 'ERROR');
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error UsersService::delete: " . $e->getMessage());
            return false;
        }
    }
}

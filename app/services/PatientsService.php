<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use DateTime;

class PatientsService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM pacientes ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function searchByIdentification($search)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE identificacion = ?");
        $stmt->execute([$search]);
        return $stmt->fetchAll();
    }

    public function create($data)
    {
        // Sanitizar y Validar
        foreach ($data as $key => $value) {
            if (is_string($value))
                $data[$key] = trim($value);
        }

        if (empty($data['identificacion'])) {
            throw new Exception("La identificación es obligatoria.");
        }

        if (!filter_var($data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El correo electrónico no es válido.");
        }

        if (strlen($data['password'] ?? '') < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.");
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
        if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_nacimiento']) {
            throw new Exception("La fecha de nacimiento no es válida.");
        }

        try {
            $this->pdo->beginTransaction();

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt_rol = $this->pdo->prepare("SELECT id FROM roles WHERE LOWER(nombre) = 'paciente' LIMIT 1");
            $stmt_rol->execute();
            $rol_id = $stmt_rol->fetchColumn() ?: 1;

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

            $sql_paciente = "INSERT INTO pacientes (usuario_id, nombre, apellido, identificacion, identificacion_tipo, fecha_nacimiento, genero, direccion, telefono, correo_electronico) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_paciente = $this->pdo->prepare($sql_paciente);
            $stmt_paciente->execute([
                $usuario_id,
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['identificacion_tipo'],
                $data['fecha_nacimiento'],
                $data['genero'],
                $data['direccion'],
                $data['telefono'],
                $data['correo_electronico']
            ]);

            $this->pdo->commit();

            // Auditoría: Crear paciente
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['usuario_id'] ?? $usuario_id, 'Registro de paciente', 'Pacientes', "Nombre: $data[nombre] $data[apellido], Cédula: $data[identificacion]", 'INFO');

            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PatientsService::create: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico o la identificación ya están registrados.");
            }
            throw new Exception("Error al guardar el paciente: " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Obtener usuario_id asociado
            $stmt_get_user = $this->pdo->prepare("SELECT usuario_id FROM pacientes WHERE id = ?");
            $stmt_get_user->execute([$id]);
            $usuario_id = $stmt_get_user->fetchColumn();

            if (!$usuario_id) {
                throw new Exception("Paciente no encontrado.");
            }

            // 2. Actualizar tabla pacientes
            $sql = "UPDATE pacientes SET nombre=?, apellido=?, identificacion=?, identificacion_tipo=?, fecha_nacimiento=?, genero=?, direccion=?, telefono=?, correo_electronico=? 
                    WHERE id=?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['identificacion_tipo'],
                $data['fecha_nacimiento'],
                $data['genero'],
                $data['direccion'],
                $data['telefono'],
                $data['correo_electronico'],
                $id
            ]);

            // 3. Actualizar tabla usuarios (sincronización)
            $sql_user = "UPDATE usuarios SET nombre=?, apellido=?, correo_electronico=? WHERE id=?";
            $stmt_user = $this->pdo->prepare($sql_user);
            $stmt_user->execute([
                $data['nombre'],
                $data['apellido'],
                $data['correo_electronico'],
                $usuario_id
            ]);

            $this->pdo->commit();

            // Auditoría: Editar paciente
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['usuario_id'], 'Edición de paciente', 'Pacientes', "Paciente ID: $id ($data[nombre] $data[apellido])", 'WARNING');

            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PatientsService::update: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico o la identificación ya están registrados.");
            }
            throw new Exception("Error al actualizar el paciente.");
        }
    }

    public function delete($id)
    {
        try {
            $this->pdo->beginTransaction();

            $paciente = $this->getById($id);
            if (!$paciente)
                return false;

            $stmt = $this->pdo->prepare("DELETE FROM pacientes WHERE id = ?");
            $res = $stmt->execute([$id]);

            if ($res) {
                $stmt_user = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt_user->execute([$paciente['usuario_id']]);

                $this->pdo->commit();

                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['usuario_id'], 'Eliminación de paciente', 'Pacientes', "ID: $id, Nombre: $paciente[nombre] $paciente[apellido]", 'ERROR');
            } else {
                $this->pdo->rollBack();
            }
            return $res;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction())
                $this->pdo->rollBack();
            error_log("Error PatientsService::delete: " . $e->getMessage());
            return false;
        }
    }
}

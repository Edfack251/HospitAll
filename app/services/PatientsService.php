<?php

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
        try {
            $this->pdo->beginTransaction();

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Obtener el ID del rol 'Paciente'
            $stmt_rol = $this->pdo->prepare("SELECT id FROM roles WHERE nombre = 'Paciente' LIMIT 1");
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
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico o la identificación ya están registrados.");
            }
            throw new Exception("Error al guardar el paciente: " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            $sql = "UPDATE pacientes SET nombre=?, apellido=?, identificacion=?, identificacion_tipo=?, fecha_nacimiento=?, genero=?, direccion=?, telefono=?, correo_electronico=? 
                    WHERE id=?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
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
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar el paciente: " . $e->getMessage());
        }
    }
}

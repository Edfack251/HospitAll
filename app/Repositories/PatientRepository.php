<?php
namespace App\Repositories;

use PDO;

class PatientRepository extends BaseRepository
{
    public function getPatientCount()
    {
        $sql = $this->applySoftDeleteFilter("SELECT COUNT(*) FROM pacientes");
        return $this->pdo->query($sql)->fetchColumn();
    }

    public function getAllBasic()
    {
        $sql = $this->applySoftDeleteFilter("SELECT id, nombre, apellido FROM pacientes ORDER BY nombre ASC LIMIT 500");
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getAll($limit = null, $offset = 0)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT * FROM pacientes ORDER BY created_at DESC";
        $sql = $this->applySoftDeleteFilter($sql);
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = $this->applySoftDeleteFilter("SELECT * FROM pacientes WHERE id = ?");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene un paciente por id incluyendo registros eliminados lógicamente.
     */
    public function getByIdIncludingDeleted($id)
    {
        return $this->findByIdIncludingDeleted('pacientes', (int) $id);
    }

    public function getByIdentificacion($identificacion)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = $this->applySoftDeleteFilter("SELECT * FROM pacientes WHERE identificacion = ?");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$identificacion]);
        return $stmt->fetchAll();
    }

    public function search($query)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT * FROM pacientes 
                WHERE (nombre LIKE ? 
                OR apellido LIKE ? 
                OR identificacion LIKE ? 
                OR telefono LIKE ?) ";
        $sql = $this->applySoftDeleteFilter($sql);
        $sql .= " LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $term = "%$query%";
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un registro de paciente para emergencias (sin cuenta de portal).
     * Solo los datos mínimos necesarios para identificar al paciente.
     */
    public function createForEmergencia(array $data): int
    {
        $sql = "INSERT INTO pacientes (usuario_id, nombre, apellido, identificacion, identificacion_tipo, fecha_nacimiento, genero, telefono) 
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['identificacion'],
            $data['identificacion_tipo'] ?? 'Cédula',
            $data['fecha_nacimiento'],
            $data['genero'],
            $data['telefono'] ?? null
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function create($usuario_id, $data)
    {
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
        return $this->pdo->lastInsertId();
    }

    public function getUserIdByPatientId($id)
    {
        $sql = $this->applySoftDeleteFilter("SELECT usuario_id FROM pacientes WHERE id = ?");
        $stmt_get_user = $this->pdo->prepare($sql);
        $stmt_get_user->execute([$id]);
        return $stmt_get_user->fetchColumn();
    }

    public function update($id, $data)
    {
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
    }

    public function delete($id)
    {
        return $this->softDelete('pacientes', $id);
    }

    /**
     * Restaura un paciente previamente eliminado (soft delete).
     */
    public function restore($id)
    {
        return $this->restoreRecord('pacientes', (int) $id);
    }

    /**
     * Lista los pacientes eliminados lógicamente.
     */
    public function getDeleted()
    {
        return $this->getDeletedRecords(
            'pacientes',
            'id, nombre, apellido, identificacion, deleted_at',
            'deleted_at DESC'
        );
    }

    public function getIdentityData($paciente_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes_identidad WHERE paciente_id = ?");
        $stmt->execute([$paciente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveIdentityData($paciente_id, $data)
    {
        $sql = "INSERT INTO pacientes_identidad (
                    paciente_id, grupo_sanguineo, alergias, estado_civil,
                    contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_parentesco
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $paciente_id,
            $data['grupo_sanguineo'] ?? null,
            $data['alergias'] ?? null,
            $data['estado_civil'] ?? 'Soltero',
            $data['contacto_emergencia_nombre'] ?? null,
            $data['contacto_emergencia_telefono'] ?? null,
            $data['contacto_emergencia_parentesco'] ?? null
        ]);
    }

    public function updateIdentityData($paciente_id, $data)
    {
        // Upsert logic: Check if it exists first
        $existing = $this->getIdentityData($paciente_id);

        if ($existing) {
            $sql = "UPDATE pacientes_identidad SET 
                        grupo_sanguineo = ?, 
                        alergias = ?, 
                        estado_civil = ?,
                        contacto_emergencia_nombre = ?, 
                        contacto_emergencia_telefono = ?, 
                        contacto_emergencia_parentesco = ?
                    WHERE paciente_id = ?";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['grupo_sanguineo'] ?? null,
                $data['alergias'] ?? null,
                $data['estado_civil'] ?? 'Soltero',
                $data['contacto_emergencia_nombre'] ?? null,
                $data['contacto_emergencia_telefono'] ?? null,
                $data['contacto_emergencia_parentesco'] ?? null,
                $paciente_id
            ]);
        } else {
            return $this->saveIdentityData($paciente_id, $data);
        }
    }

    public function getMonthlyNewPatients($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-d', strtotime("$startDate +1 month"));

        $sql = "SELECT COUNT(*) FROM pacientes 
                WHERE created_at >= ? AND created_at < ?";
        $sql = $this->applySoftDeleteFilter($sql);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }
}

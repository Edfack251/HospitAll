<?php
namespace App\Repositories;

use PDO;

class DoctorRepository extends BaseRepository
{
    public function getAllBasic()
    {
        $sql = $this->applySoftDeleteFilter("SELECT id, nombre, apellido, especialidad FROM medicos ORDER BY nombre ASC LIMIT 500");
        return $this->pdo->query($sql)->fetchAll();
    }

    public function create($data)
    {
        $sql = "INSERT INTO medicos (nombre, apellido, especialidad, telefono, correo_electronico) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['especialidad'],
            $data['telefono'],
            $data['correo_electronico']
        ]);

        return $this->pdo->lastInsertId();
    }

    public function update($id, $data)
    {
        $sql = "UPDATE medicos SET nombre=?, apellido=?, especialidad=?, telefono=?, correo_electronico=? 
                WHERE id=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['especialidad'],
            $data['telefono'],
            $data['correo_electronico'],
            $id
        ]);
    }

    public function delete($id)
    {
        return $this->softDelete('medicos', $id);
    }

    /**
     * Restaura un médico previamente eliminado (soft delete).
     */
    public function restore($id)
    {
        return $this->restoreRecord('medicos', (int) $id);
    }

    /**
     * Obtiene un médico por id incluyendo registros eliminados lógicamente.
     */
    public function getByIdIncludingDeleted($id)
    {
        return $this->findByIdIncludingDeleted('medicos', (int) $id);
    }

    /**
     * Lista los médicos eliminados lógicamente.
     */
    public function getDeleted()
    {
        return $this->getDeletedRecords(
            'medicos',
            'id, nombre, apellido, especialidad, correo_electronico, deleted_at',
            'deleted_at DESC'
        );
    }

    public function getDoctorIdByUserId($usuario_id)
    {
        $sql = "SELECT m.id FROM medicos m JOIN usuarios u ON m.correo_electronico = u.correo_electronico WHERE u.id = ?";
        $sql = $this->applySoftDeleteFilter($sql, 'm'); // Also filter deleted doctors
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchColumn();
    }
}

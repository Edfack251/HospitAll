<?php
namespace App\Repositories;

use PDO;

class UserRepository extends BaseRepository
{
    public function getAll($limit = null, $offset = 0, $staffOnly = false)
    {
        $sql = "SELECT u.id, u.nombre, u.apellido, u.correo_electronico, r.nombre as rol_nombre, u.created_at 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id";
        if ($staffOnly) {
            $sql .= " WHERE (r.nombre IS NULL OR r.nombre != 'paciente')";
        }
        $sql .= " ORDER BY u.created_at DESC";
        $sql = $this->applySoftDeleteFilter($sql, 'u');

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
        $sql = $this->applySoftDeleteFilter("SELECT * FROM usuarios WHERE id = ?");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene un usuario por id incluyendo registros eliminados lógicamente.
     */
    public function getByIdIncludingDeleted($id)
    {
        return $this->findByIdIncludingDeleted('usuarios', (int) $id);
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

    public function create($data, $password_hash)
    {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) VALUES (?, ?, ?, ?, ?)");
        $res = $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['correo_electronico'],
            $password_hash,
            $data['rol_id']
        ]);

        if ($res) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $data)
    {
        if (!empty($data['password'])) {
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, correo_electronico = ?, rol_id = ?, password = ? WHERE id = ?");
            return $stmt->execute([$data['nombre'], $data['apellido'], $data['correo_electronico'], $data['rol_id'], $password_hash, $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, correo_electronico = ?, rol_id = ? WHERE id = ?");
            return $stmt->execute([$data['nombre'], $data['apellido'], $data['correo_electronico'], $data['rol_id'], $id]);
        }
    }

    public function delete($id)
    {
        return $this->softDelete('usuarios', $id);
    }

    /**
     * Restaura un usuario previamente eliminado (soft delete).
     */
    public function restore($id)
    {
        return $this->restoreRecord('usuarios', (int) $id);
    }

    /**
     * Lista los usuarios eliminados lógicamente.
     */
    public function getDeleted()
    {
        return $this->getDeletedRecords(
            'usuarios',
            'id, nombre, apellido, correo_electronico, deleted_at',
            'deleted_at DESC'
        );
    }
}

<?php
namespace App\Repositories;

use PDO;

class PrescriptionRepository extends BaseRepository
{
    public function create($cita_id, $medico_id, $paciente_id, $observaciones, $estado = 'Pendiente')
    {
        $sql = "INSERT INTO prescripciones (cita_id, medico_id, paciente_id, observaciones, estado) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $cita_id,
            $medico_id,
            $paciente_id,
            $observaciones,
            $estado
        ]);
        return $this->pdo->lastInsertId();
    }

    public function createDetalle($prescripcion_id, $detalle)
    {
        $sql_detalle = "INSERT INTO prescripcion_detalle (prescripcion_id, medicamento_id, medicamento_texto, dosis, frecuencia, duracion, cantidad_requerida, indicaciones) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_detalle = $this->pdo->prepare($sql_detalle);

        return $stmt_detalle->execute([
            $prescripcion_id,
            $detalle['medicamento_id'] ?? null,
            $detalle['medicamento_texto'] ?? '',
            $detalle['dosis'],
            $detalle['frecuencia'],
            $detalle['duracion'],
            $detalle['cantidad_requerida'] ?? null,
            $detalle['indicaciones'] ?? ''
        ]);
    }

    public function getByCita($cita_id)
    {
        $sql = $this->applySoftDeleteFilter("SELECT * FROM prescripciones WHERE cita_id = ?");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cita_id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene una prescripción por id incluyendo registros eliminados lógicamente.
     */
    public function getByIdIncludingDeleted($id)
    {
        return $this->findByIdIncludingDeleted('prescripciones', (int) $id);
    }

    public function getDetalles($prescripcion_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescripcion_detalle WHERE prescripcion_id = ?");
        $stmt->execute([$prescripcion_id]);
        return $stmt->fetchAll();
    }

    public function updateEstado($id, $estado)
    {
        $stmt = $this->pdo->prepare("UPDATE prescripciones SET estado = ? WHERE id = ?");
        return $stmt->execute([$estado, $id]);
    }

    public function getPrescripcionesActivas($medico_id, $limit = 5)
    {
        $sql = "SELECT p.*, pac.nombre as paciente_nombre, pac.apellido as paciente_apellido, c.fecha as fecha_cita
                FROM prescripciones p
                JOIN pacientes pac ON p.paciente_id = pac.id
                JOIN citas c ON p.cita_id = c.id
                WHERE p.medico_id = :medico_id AND p.estado = 'Pendiente'";
        
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql = $this->applySoftDeleteFilter($sql, 'pac');

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':medico_id', (int) $medico_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        return $this->softDelete('prescripciones', $id);
    }

    /**
     * Restaura una prescripción previamente eliminada (soft delete).
     */
    public function restore($id)
    {
        return $this->restoreRecord('prescripciones', (int) $id);
    }

    /**
     * Lista las prescripciones eliminadas lógicamente.
     */
    public function getDeleted()
    {
        return $this->getDeletedRecords(
            'prescripciones',
            'id, paciente_id, medico_id, estado, deleted_at',
            'deleted_at DESC'
        );
    }
}

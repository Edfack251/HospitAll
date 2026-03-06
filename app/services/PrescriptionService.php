<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;

class PrescriptionService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea una prescripción médica.
     * $data['detalles'] debe ser un array de medicamentos con dosis, frecuencia, duracion, etc.
     */
    public function create($data)
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $sql = "INSERT INTO prescripciones (cita_id, medico_id, paciente_id, observaciones, estado) 
                    VALUES (?, ?, ?, ?, 'Pendiente')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['cita_id'],
                $data['medico_id'],
                $data['paciente_id'],
                $data['observaciones'] ?? ''
            ]);
            $prescripcion_id = $this->pdo->lastInsertId();

            if (isset($data['detalles']) && is_array($data['detalles'])) {
                $sql_detalle = "INSERT INTO prescripcion_detalle (prescripcion_id, medicamento_id, medicamento_texto, dosis, frecuencia, duracion, cantidad_requerida, indicaciones) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_detalle = $this->pdo->prepare($sql_detalle);

                foreach ($data['detalles'] as $detalle) {
                    $stmt_detalle->execute([
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
            }

            if (!$this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $prescripcion_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getByCita($cita_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescripciones WHERE cita_id = ?");
        $stmt->execute([$cita_id]);
        return $stmt->fetch();
    }

    public function getDetalles($prescripcion_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescripcion_detalle WHERE prescripcion_id = ?");
        $stmt->execute([$prescripcion_id]);
        return $stmt->fetchAll();
    }
}

<?php
namespace App\Controllers;

use App\Services\PharmacyService;
use Exception;
use PDO;

class PharmacyController
{
    private $service;
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new PharmacyService($pdo);
    }

    /**
     * Obtiene el inventario actual de medicamentos.
     */
    public function getInventory()
    {
        return $this->service->getInventory();
    }

    /**
     * Obtiene la lista de pacientes.
     */
    public function getPatients()
    {
        return $this->service->getPatients();
    }

    /**
     * Endpoint para procesar la dispensación y emisión automática de factura.
     */
    public function dispense(array $data)
    {
        try {
            $paciente_id = (int) $data['paciente_id'];
            $medicamento_id = (int) $data['medicamento_id'];
            $cantidad = (int) $data['cantidad'];

            $factura_id = $this->service->dispense($paciente_id, $medicamento_id, $cantidad);

            // Redirigir a la vista de farmacia con el recibo
            header("Location: ../pharmacy.php?success=dispensed&factura_id=" . $factura_id);
            exit();
        } catch (Exception $e) {
            header("Location: ../pharmacy.php?error=1&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }

    /**
     * Obtiene las prescripciones pendientes.
     */
    public function getPendingPrescriptions()
    {
        return $this->service->getPendingPrescriptions();
    }

    /**
     * Obtiene una prescripción específica.
     */
    public function getPrescription($id)
    {
        return $this->service->getPrescription($id);
    }

    /**
     * Procesa la dispensación de una prescripción completa.
     */
    public function processDispense(array $data)
    {
        try {
            $prescripcion_id = (int) $data['prescripcion_id'];
            $items = $data['items']; // Array de ['medicamento_id' => X, 'cantidad' => Y]

            $factura_id = $this->service->dispensePrescription($prescripcion_id, $items);

            header("Location: ../pharmacy.php?success=dispensed&factura_id=" . $factura_id);
            exit();
        } catch (Exception $e) {
            header("Location: ../pharmacy_dispense.php?id=" . $data['prescripcion_id'] . "&error=1&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

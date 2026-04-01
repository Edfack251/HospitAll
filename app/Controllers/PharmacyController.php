<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\PharmacyService;
use App\Policies\PolicyManager;
use Exception;
use PDO;

class PharmacyController
{
    private $service;
    private $pdo;
    private $turnosRepo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new PharmacyService($pdo);
        $this->turnosRepo = new \App\Repositories\QueueRepository($pdo);
    }

    public function getInventory()
    {
        PolicyManager::authorize($_SESSION, 'view_inventory');
        return $this->service->getInventory();
    }

    public function getPatients()
    {
        return $this->service->getPatients();
    }

    public function handleDispense()
    {
        $this->dispense($_POST);
    }

    public function dispense(array $data)
    {
        try {
            PolicyManager::authorize($_SESSION, 'dispense_medicine');
            $paciente_id = (int) $data['paciente_id'];
            $medicamento_id = (int) $data['medicamento_id'];
            $cantidad = (int) $data['cantidad'];

            $factura_id = $this->service->dispense($paciente_id, $medicamento_id, $cantidad);

            UrlHelper::redirect('pharmacy', ['success' => 'dispensed', 'factura_id' => $factura_id]);
        } catch (Exception $e) {
            UrlHelper::redirect('pharmacy', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function getPendingPrescriptions($limit = 5, $offset = 0)
    {
        return $this->service->getPendingPrescriptions($limit, $offset);
    }

    public function getPendingPrescriptionsCount()
    {
        return $this->service->getPendingPrescriptionsCount();
    }

    public function getPrescription($id)
    {
        return $this->service->getPrescription($id);
    }

    public function handleProcessDispense()
    {
        $this->processDispense($_POST);
    }

    public function processDispense(array $data)
    {
        try {
            PolicyManager::authorize($_SESSION, 'dispense_medicine');
            $prescripcion_id = (int) $data['prescripcion_id'];
            $items = $data['items'];

            $factura_id = $this->service->dispensePrescription($prescripcion_id, $items);

            UrlHelper::redirect('pharmacy', ['success' => 'dispensed', 'factura_id' => $factura_id]);
        } catch (Exception $e) {
            UrlHelper::redirect('pharmacy_prescriptions', ['id' => $data['prescripcion_id'], 'error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function deleteMedicamento($id)
    {
        try {
            PolicyManager::authorize($_SESSION, 'delete_medicine');
            $res = $this->service->deleteMedicamento($id);
            if ($res) {
                UrlHelper::redirect('pharmacy', ['deleted' => '1']);
            } else {
                UrlHelper::redirect('pharmacy', ['error' => '1', 'msg' => 'No se pudo eliminar el medicamento.']);
            }
        } catch (Exception $e) {
            error_log("PharmacyController::deleteMedicamento: " . $e->getMessage());
            UrlHelper::redirect('pharmacy', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para restaurar un medicamento eliminado lógicamente.
     * Se espera recibir el ID por POST (api: /api/pharmacy/medicamento/restore).
     */
    public function restoreMedicamentoApi()
    {
        try {
            PolicyManager::authorize($_SESSION, 'restore_medicine');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de medicamento inválido.");
            }

            $res = $this->service->restoreMedicamento($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Medicamento restaurado correctamente.' : 'No se pudo restaurar el medicamento.'
            ]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en PharmacyController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log("PharmacyController::restoreMedicamentoApi: " . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Endpoint API para registrar un nuevo medicamento.
     * Recibe datos por POST (formulario).
     */
    public function registrarMedicamento()
    {
        try {
            PolicyManager::authorize($_SESSION, 'manage_inventory');

            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'presentacion' => trim($_POST['presentacion'] ?? ''),
                'concentracion' => trim($_POST['concentracion'] ?? ''),
                'lote' => trim($_POST['lote'] ?? ''),
                'proveedor' => trim($_POST['proveedor'] ?? ''),
                'precio' => $_POST['precio'] ?? 0,
                'stock' => $_POST['stock'] ?? 0,
                'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? null,
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'codigo' => trim($_POST['codigo'] ?? '')
            ];

            $usuario_id = (int) ($_SESSION['user_id'] ?? 0);
            $medicamento_id = $this->service->registrarMedicamento($data, $usuario_id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'medicamento_id' => $medicamento_id,
                'message' => 'Medicamento registrado correctamente.'
            ]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en PharmacyController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log("PharmacyController::registrarMedicamento: " . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Endpoint API para editar un medicamento existente.
     * Recibe JSON por php://input.
     */
    public function editarMedicamento()
    {
        try {
            PolicyManager::authorize($_SESSION, 'manage_inventory');

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception("Datos de entrada inválidos.");
            }

            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de medicamento inválido.");
            }

            $data = [
                'nombre' => trim($input['nombre'] ?? ''),
                'presentacion' => trim($input['presentacion'] ?? ''),
                'concentracion' => trim($input['concentracion'] ?? ''),
                'lote' => trim($input['lote'] ?? ''),
                'proveedor' => trim($input['proveedor'] ?? ''),
                'precio' => $input['precio'] ?? 0,
                'fecha_vencimiento' => $input['fecha_vencimiento'] ?? null,
                'descripcion' => trim($input['descripcion'] ?? '')
            ];

            $result = $this->service->editarMedicamento($id, $data);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $result,
                'message' => $result ? 'Medicamento actualizado correctamente.' : 'No se pudo actualizar el medicamento.'
            ]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en PharmacyController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log("PharmacyController::editarMedicamento: " . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Endpoint API para ajustar stock (entrada de inventario).
     * Recibe JSON por php://input.
     */
    public function ajustarStock()
    {
        try {
            PolicyManager::authorize($_SESSION, 'manage_inventory');

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception("Datos de entrada inválidos.");
            }

            $medicamento_id = (int) ($input['medicamento_id'] ?? 0);
            $cantidad = (int) ($input['cantidad'] ?? 0);
            $motivo = trim($input['motivo'] ?? '');

            if ($medicamento_id <= 0) {
                throw new Exception("ID de medicamento inválido.");
            }

            $usuario_id = (int) ($_SESSION['user_id'] ?? 0);
            $result = $this->service->ajustarStock($medicamento_id, $cantidad, $motivo, $usuario_id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $result,
                'message' => 'Stock ajustado correctamente.'
            ]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en PharmacyController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log("PharmacyController::ajustarStock: " . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    public function getHistorialMovimientos()
    {
        \App\Policies\PolicyManager::authorize($_SESSION, 'view_inventory');

        $filtros = [
            'medicamento_id' => $_GET['medicamento_id'] ?? null,
            'tipo_movimiento' => $_GET['tipo_movimiento'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'),
            'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d')
        ];

        $movimientos = $this->service->getMovimientos($filtros);
        $medicamentos = $this->service->getInventory();

        $path = __DIR__ . '/../../views/pages/pharmacy_movimientos.php';
        include $path;
    }

    public function getMedicamentosConStock()
    {
        header('Content-Type: application/json');
        try {
            $medicamentos = $this->service->getMedicamentosConStockParaRecetar();
            echo json_encode(['success' => true, 'medicamentos' => $medicamentos]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }

    public function getTurnoActual()
    {
        return $this->turnosRepo->getTurnoActual('farmacia');
    }

    public function getTurnosEsperandoCount()
    {
        return count($this->turnosRepo->getTurnosEsperando('farmacia'));
    }
}

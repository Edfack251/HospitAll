<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Repositories\LaboratoryRepository;
use App\Services\LogService;

class LaboratoryService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new LaboratoryRepository($pdo);
    }

    public function getAllOrders($limit = null, $offset = 0)
    {
        return $this->repo->getAllOrders($limit, $offset);
    }

    public function createWalkinOrder($walkin_id, $descripcion, array $examenes = [])
    {
        if (empty($walkin_id)) {
            throw new Exception("El ID de walkin es requerido.");
        }
        return $this->repo->createPendingWalkin($walkin_id, $descripcion, $examenes);
    }

    public function uploadResult($data, $files)
    {
        $orden_id = $data['orden_id'] ?? '';
        $resultado = $data['resultado'] ?? '';

        if (empty($orden_id) || empty($resultado)) {
            throw new Exception("Datos insuficientes para guardar el resultado.");
        }

        // Directorio de subida
        $upload_dir = __DIR__ . '/../../public/uploads/lab_results/';

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Error: No se pudo crear el directorio de subida.");
            }
        }

        if (empty($files['archivo_pdf']) || $files['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("El archivo PDF es obligatorio para subir resultados.");
        }

        $file_path = null;

        if (isset($files['archivo_pdf']) && $files['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $file_name = time() . '_' . basename($files['archivo_pdf']['name']);
            $target_file = $upload_dir . $file_name;

            // Validación de extensión
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if ($file_type !== 'pdf') {
                throw new Exception("Error: Solo se permiten archivos con extensión PDF.");
            }

            // Validación del MIME type real
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $files['archivo_pdf']['tmp_name']);
            finfo_close($finfo);

            if ($mime_type !== 'application/pdf') {
                throw new Exception("Error: El archivo no es un PDF válido por su tipo de contenido.");
            }

            if (move_uploaded_file($files['archivo_pdf']['tmp_name'], $target_file)) {
                $file_path = 'uploads/lab_results/' . $file_name;
            } else {
                throw new Exception("Error al subir el archivo.");
            }
        }

        try {
            if ($file_path) {
                $this->repo->uploadResultWithFile($orden_id, $resultado, $file_path);
            } else {
                $this->repo->uploadResultWithoutFile($orden_id, $resultado);
            }

            // Auditoría: Subir resultado laboratorio
            if (isset($_SESSION['user_id'])) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Subir resultado PDF', 'Laboratorio', "Orden ID: $orden_id", 'WARNING');
            }

            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al guardar el resultado: " . $e->getMessage());
        }
    }

    public function deleteOrder($id)
    {
        try {
            // No strict check for completed orders as soft delete is reversible, 
            // but we might want to warn or prevent if it has a PDF result.
            $order = $this->repo->getAllOrders(); // Simplified to find specific one
            $orderData = null;
            foreach ($order as $o) {
                if ($o['id'] == $id) {
                    $orderData = $o;
                    break;
                }
            }

            if (!$orderData) {
                return false;
            }

            $res = $this->repo->deleteOrder($id);
            if ($res) {
                if (isset($_SESSION['user_id'])) {
                    $logService = new LogService($this->pdo);
                    $logService->register($_SESSION['user_id'], 'Eliminación lógica de orden laboratorio', 'Laboratorio', "Orden ID: $id", 'ERROR');
                }
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error LaboratoryService::deleteOrder: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaura una orden de laboratorio previamente eliminada (soft delete).
     */
    public function restoreOrder($id)
    {
        $id = (int) $id;

        $order = $this->repo->getByIdIncludingDeleted($id);
        if (!$order) {
            throw new Exception("Orden de laboratorio no encontrada.");
        }

        if ($order['deleted_at'] === null) {
            throw new Exception("La orden de laboratorio ya está activa, no se puede restaurar.");
        }

        $res = $this->repo->restoreOrder($id);

        if ($res && isset($_SESSION['user_id'])) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'],
                "Restauración de orden laboratorio | ID: {$id}",
                'Laboratorio',
                "Orden ID: {$id}",
                'INFO'
            );
        }

        return $res;
    }

    /**
     * Actualiza el estado de una orden (Pendiente → En proceso → Completada).
     */
    public function updateEstadoOrden(int $id, string $estado): bool
    {
        return $this->repo->actualizarEstadoOrden($id, $estado);
    }
}

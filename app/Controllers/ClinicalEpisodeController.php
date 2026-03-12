<?php
namespace App\Controllers;

use App\Core\ErrorHandler;
use App\Services\ClinicalEpisodeService;
use Exception;

class ClinicalEpisodeController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new ClinicalEpisodeService($pdo);
    }

    public function create()
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Método no permitido", 405);
            }

            $data = count($_POST) > 0 ? $_POST : json_decode(file_get_contents('php://input'), true);

            $id = $this->service->generateEpisode($data);

            echo json_encode(['success' => true, 'episodio_id' => $id, 'mensaje' => 'Episodio creado correctamente.']);
        } catch (Exception $e) {
            http_response_code(intval($e->getCode()) ?: 500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function close()
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Método no permitido", 405);
            }

            $data = count($_POST) > 0 ? $_POST : json_decode(file_get_contents('php://input'), true);
            $episodio_id = $data['episodio_id'] ?? null;

            if (!$episodio_id)
                throw new Exception("ID de episodio no proporcionado");

            $this->service->completeEpisode($episodio_id);

            echo json_encode(['success' => true, 'mensaje' => 'Episodio cerrado correctamente.']);
        } catch (Exception $e) {
            http_response_code(intval($e->getCode()) ?: 500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getByPatient()
    {
        header('Content-Type: application/json');
        try {
            $paciente_id = $_GET['paciente_id'] ?? null;
            if (!$paciente_id)
                throw new Exception("ID de paciente no proporcionado");

            $episodios = $this->service->fetchAllByPatient($paciente_id);

            echo json_encode(['success' => true, 'data' => $episodios]);
        } catch (Exception $e) {
            http_response_code(intval($e->getCode()) ?: 500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

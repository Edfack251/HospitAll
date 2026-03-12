<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Repositories\PatientPortalRepository;

class PatientPortalService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new PatientPortalRepository($pdo);
    }

    /**
     * Obtiene todos los datos agregados para el dashboard del paciente.
     * 
     * @param int $usuario_id ID del usuario autenticado
     * @return array
     * @throws Exception Si el usuario no tiene un perfil de paciente asociado
     */
    public function getDashboardData(int $usuario_id): array
    {
        try {
            // 1. Resolver el ID del paciente desde el usuario
            $paciente_id = $this->repo->getPatientIdByUserId($usuario_id);
            if (!$paciente_id) {
                throw new Exception("No se encontró un perfil de paciente para este usuario.");
            }

            // 2. Obtener datos básicos (Paralelizable en repositorio, secuencial aquí)
            $citas_proximas = $this->repo->getCitasProximas($paciente_id);
            $historial = $this->repo->getHistorialClinico($paciente_id);
            $laboratorio = $this->repo->getOrdenesLaboratorio($paciente_id);
            $prescripciones = $this->repo->getPrescripcionesMedicas($paciente_id);
            $facturas_pendientes = $this->repo->getFacturasPendientes($paciente_id);

            // 3. Filtrado y Organización (Reglas de Negocio)

            // Filtrar prescripciones activas (esto es una simplificación, en un sistema real depende de la fecha fin)
            // Por ahora, las tomamos todas pero podríamos filtrar por fecha si tuviéramos campo 'fecha_fin'
            $prescripciones_activas = array_filter($prescripciones, function ($p) {
                return $p['estado'] === 'Pendiente';
            });

            // Resultados de laboratorio disponibles (Completados)
            $laboratorio_disponible = array_filter($laboratorio, function ($l) {
                return $l['estado'] === 'Completada';
            });

            return [
                'paciente_id' => $paciente_id,
                'citas_proximas' => $citas_proximas,
                'historial_reciente' => array_slice($historial, 0, 5), // Solo las 5 más recientes
                'laboratorio_disponible' => $laboratorio_disponible,
                'prescripciones_activas' => array_values($prescripciones_activas),
                'facturas_pendientes' => $facturas_pendientes
            ];

        } catch (PDOException $e) {
            error_log("Error en PatientPortalService::getDashboardData (DB): " . $e->getMessage());
            throw new Exception("Error al cargar los datos del dashboard. Por favor, reintente más tarde.");
        } catch (Exception $e) {
            error_log("Error en PatientPortalService::getDashboardData: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene todos los datos necesarios para el portal del paciente.
     * 
     * @param int $paciente_id ID del paciente
     * @return array [citas_proximas, historial, laboratorio, prescripciones]
     */
    public function getPatientPortalData(int $paciente_id): array
    {
        try {
            // 1. Obtener citas próximas (Programadas, Confirmadas, En espera)
            $citas_proximas = $this->repo->getCitasProximas($paciente_id);

            // 2. Obtener historial clínico con conteo de órdenes pendientes
            $historial = $this->repo->getHistorialClinico($paciente_id);

            // 3. Obtener órdenes de laboratorio
            $laboratorio = $this->repo->getOrdenesLaboratorio($paciente_id);

            // 4. Obtener prescripciones médicas
            $prescripciones = $this->repo->getPrescripcionesMedicas($paciente_id);

            return [
                'citas_proximas' => $citas_proximas,
                'historial' => $historial,
                'laboratorio' => $laboratorio,
                'prescripciones' => $prescripciones
            ];
        } catch (PDOException $e) {
            error_log("Error PatientPortalService::getPatientPortalData: " . $e->getMessage());
            // En caso de error, retornamos arrays vacíos para no romper la vista
            return [
                'citas_proximas' => [],
                'historial' => [],
                'laboratorio' => [],
                'prescripciones' => []
            ];
        }
    }
}

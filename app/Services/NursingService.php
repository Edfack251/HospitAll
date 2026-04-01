<?php
namespace App\Services;

use App\Repositories\NursingRepository;
use App\Repositories\PatientRepository;
use Exception;

class NursingService
{
    private $repo;
    private $patientRepo;

    public function __construct($pdo)
    {
        $this->repo = new NursingRepository($pdo);
        $this->patientRepo = new PatientRepository($pdo);
    }

    /**
     * Valida y registra signos vitales.
     */
    public function registrarSignosVitales(int $cita_id, int $medico_id, int $paciente_id, array $data): void
    {
        $campos = ['frecuencia_cardiaca', 'temperatura', 'peso', 'estatura'];
        foreach ($campos as $c) {
            if (isset($data[$c]) && $data[$c] !== '') {
                $v = is_numeric($data[$c]) ? (float) $data[$c] : null;
                if ($v !== null && $v < 0) {
                    throw new Exception("El valor de {$c} no puede ser negativo.");
                }
            }
        }
        $this->repo->registrarSignosVitales($cita_id, $medico_id, $paciente_id, $data);
    }

    /**
     * Obtiene signos vitales de una cita.
     */
    public function getSignosVitalesCita(int $cita_id): ?array
    {
        return $this->repo->getSignosVitalesCita($cita_id);
    }

    /**
     * Valida y registra una observación de enfermería.
     */
    public function registrarObservacion(int $cita_id, int $paciente_id, int $usuario_id, string $texto): void
    {
        $texto = trim($texto);
        if ($texto === '') {
            throw new Exception("Las observaciones no pueden estar vacías.");
        }
        $this->repo->registrarObservacion($cita_id, $paciente_id, $usuario_id, $texto);
    }

    /**
     * Valida y registra un ingreso de emergencia.
     */
    public function registrarEmergencia(int $paciente_id, int $usuario_id, string $nivel_triage, string $motivo_ingreso): int
    {
        $validos = ['Rojo', 'Naranja', 'Amarillo', 'Verde'];
        if (!in_array($nivel_triage, $validos, true)) {
            throw new Exception("Nivel de triaje inválido.");
        }
        $motivo = trim($motivo_ingreso);
        if ($motivo === '') {
            throw new Exception("El motivo de ingreso no puede estar vacío.");
        }
        return $this->repo->registrarEmergencia($paciente_id, $usuario_id, $nivel_triage, $motivo);
    }

    /**
     * Valida y asigna un médico a una emergencia.
     */
    public function asignarMedico(int $emergencia_id, int $medico_id): void
    {
        if ($emergencia_id <= 0 || $medico_id <= 0) {
            throw new Exception("IDs inválidos.");
        }
        $this->repo->asignarMedico($emergencia_id, $medico_id);
    }

    /**
     * Valida y actualiza el estado de una emergencia.
     */
    public function actualizarEstadoEmergencia(int $id, string $estado): void
    {
        $validos = ['En espera', 'En atención', 'Atendido', 'Transferido'];
        if (!in_array($estado, $validos, true)) {
            throw new Exception("Estado inválido.");
        }
        $emergencia = $this->repo->getEmergenciaById($id);
        if (!$emergencia) {
            throw new Exception("Emergencia no encontrada.");
        }
        $actual = $emergencia['estado'];
        $transiciones = [
            'En espera' => ['En atención'],
            'En atención' => ['Atendido', 'Transferido'],
        ];
        if (isset($transiciones[$actual]) && !in_array($estado, $transiciones[$actual], true)) {
            throw new Exception("Transición de estado no permitida: {$actual} → {$estado}");
        }
        if ($actual === 'Atendido' || $actual === 'Transferido') {
            throw new Exception("No se puede cambiar el estado de una emergencia ya finalizada.");
        }
        $this->repo->actualizarEstadoEmergencia($id, $estado);
    }

    /**
     * Obtiene la lista de médicos disponibles para asignar.
     */
    public function getMedicosDisponibles(): array
    {
        return $this->repo->getMedicosDisponibles();
    }

    /**
     * Crea un registro de paciente para emergencias (sin cuenta en el portal).
     */
    public function crearPacienteEmergencia(array $data): int
    {
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $identificacion = trim($data['identificacion'] ?? '');
        $genero = $data['genero'] ?? '';
        $fecha = $data['fecha_nacimiento'] ?? '';

        if ($nombre === '' || $apellido === '') {
            throw new Exception("Nombre y apellido son obligatorios.");
        }
        if ($identificacion === '') {
            throw new Exception("La identificación es obligatoria.");
        }
        $generosValidos = ['Masculino', 'Femenino', 'Otro'];
        if (!in_array($genero, $generosValidos, true)) {
            throw new Exception("Género inválido.");
        }
        if (empty($fecha) || strtotime($fecha) === false) {
            throw new Exception("La fecha de nacimiento es obligatoria.");
        }

        $existing = $this->patientRepo->getByIdentificacion($identificacion);
        if (!empty($existing)) {
            throw new Exception("Ya existe un paciente con esa identificación.");
        }

        return $this->patientRepo->createForEmergencia([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'identificacion' => $identificacion,
            'identificacion_tipo' => $data['identificacion_tipo'] ?? 'Cédula',
            'fecha_nacimiento' => $fecha,
            'genero' => $genero,
            'telefono' => trim($data['telefono'] ?? '') ?: null
        ]);
    }
}

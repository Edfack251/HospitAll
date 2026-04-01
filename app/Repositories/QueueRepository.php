<?php
namespace App\Repositories;

use PDO;
use Exception;

class QueueRepository extends BaseRepository
{
    /**
     * Genera un nuevo turno para un área específica.
     */
    public function generarTurno(string $area, string $tipo, ?int $paciente_id, ?int $cita_id, int $usuario_id): array
    {
        // 1. Obtener el último número del área para hoy
        $sql = "SELECT numero FROM turnos WHERE area = ? AND fecha = CURDATE() ORDER BY id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$area]);
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

        $siguienteNumero = 1;
        if ($ultimo) {
            $partes = explode('-', $ultimo['numero']);
            if (isset($partes[1])) {
                $siguienteNumero = intval($partes[1]) + 1;
            }
        }

        // 2. Generar siguiente número con formato A-NNN
        $prefijo = strtoupper(substr($area, 0, 1));
        $numeroFormateado = $prefijo . '-' . str_pad($siguienteNumero, 3, '0', STR_PAD_LEFT);

        // 3. INSERT en turnos
        $sql = "INSERT INTO turnos (numero, area, tipo, paciente_id, cita_id, generado_por, fecha) 
                VALUES (?, ?, ?, ?, ?, ?, CURDATE())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$numeroFormateado, $area, $tipo, $paciente_id, $cita_id, $usuario_id]);

        $id = $this->pdo->lastInsertId();

        return $this->findById($id);
    }

    /**
     * Busca un turno por su ID con datos del paciente y cita.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT t.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion,
                       vw.id as visita_walkin_id
                FROM turnos t
                LEFT JOIN pacientes p ON t.paciente_id = p.id
                LEFT JOIN visitas_walkin vw ON vw.turno_id = t.id
                WHERE t.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene los turnos en espera para un área, aplicando la lógica de intercalado:
     * 1 preferencial por cada 2 generales.
     */
    public function getTurnosEsperando(string $area): array
    {
        // Seleccionamos todos los turnos en espera para hoy en esta área
        $sql = "SELECT t.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
                       p.identificacion as paciente_identificacion, c.hora as cita_hora
                FROM turnos t
                LEFT JOIN pacientes p ON t.paciente_id = p.id
                LEFT JOIN citas c ON t.cita_id = c.id
                WHERE t.area = ? AND t.estado = 'esperando' AND t.fecha = CURDATE()
                ORDER BY t.created_at ASC, t.id ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$area]);
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($todos)) return [];

        // Separar por tipo para aplicar la lógica de intercalado
        $preferenciales = [];
        $generales = [];
        foreach ($todos as $turno) {
            if ($turno['tipo'] === 'preferencial') {
                $preferenciales[] = $turno;
            } else {
                $generales[] = $turno;
            }
        }

        // Intercalar: 1 preferencial cada 2 generales
        $resultado = [];
        $p = 0;
        $g = 0;
        $countP = count($preferenciales);
        $countG = count($generales);

        while ($p < $countP || $g < $countG) {
            // 1. Agregar uno preferencial si existe
            if ($p < $countP) {
                $resultado[] = $preferenciales[$p++];
            }
            // 2. Agregar hasta dos generales si existen
            for ($i = 0; $i < 2 && $g < $countG; $i++) {
                $resultado[] = $generales[$g++];
            }
        }

        return $resultado;
    }

    /**
     * Obtiene el turno actual que está siendo llamado en un área.
     */
    public function getTurnoActual(string $area): ?array
    {
        $sql = "SELECT t.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                       vw.id as visita_walkin_id
                FROM turnos t
                LEFT JOIN pacientes p ON t.paciente_id = p.id
                LEFT JOIN visitas_walkin vw ON vw.turno_id = t.id
                WHERE t.area = ? AND t.estado = 'llamado' AND t.fecha = CURDATE()
                ORDER BY t.llamado_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$area]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Llama al siguiente turno según el orden de prioridad definido.
     */
    public function llamarSiguiente(string $area): ?array
    {
        $esperando = $this->getTurnosEsperando($area);
        if (empty($esperando)) {
            return null;
        }

        $siguiente = $esperando[0];
        
        $sql = "UPDATE turnos SET estado = 'llamado', llamado_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$siguiente['id']]);

        return $this->findById($siguiente['id']);
    }

    /**
     * Actualiza el cita_id de un turno.
     */
    public function updateCitaId(int $turno_id, int $cita_id): bool
    {
        $sql = "UPDATE turnos SET cita_id = ? WHERE id = ?";
        return $this->pdo->prepare($sql)->execute([$cita_id, $turno_id]);
    }

    /**
     * Marca un turno como atendido.
     */
    public function marcarAtendido(int $turno_id): bool
    {
        $sql = "UPDATE turnos SET estado = 'atendido', atendido_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$turno_id]);
    }

    /**
     * Cancela un turno.
     */
    public function cancelarTurno(int $turno_id): bool
    {
        $sql = "UPDATE turnos SET estado = 'cancelado' WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$turno_id]);
    }

    /**
     * Obtiene todos los turnos del día, opcionalmente filtrados por área.
     */
    public function getTurnosDia(?string $area = null): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT t.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido
                FROM turnos t
                LEFT JOIN pacientes p ON t.paciente_id = p.id
                WHERE t.fecha = CURDATE()";
        
        $params = [];
        if ($area) {
            $sql .= " AND t.area = ?";
            $params[] = $area;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas de turnos del día por área.
     */
    public function getEstadisticasDia(): array
    {
        $areas = ['consulta', 'laboratorio', 'farmacia', 'imagenes'];
        $stats = [];

        foreach ($areas as $area) {
            $sql = "SELECT 
                        COUNT(CASE WHEN estado = 'esperando' THEN 1 END) as esperando,
                        COUNT(CASE WHEN estado = 'atendido' THEN 1 END) as atendidos,
                        AVG(TIMESTAMPDIFF(MINUTE, created_at, atendido_at)) as tiempo_espera_promedio
                    FROM turnos 
                    WHERE area = ? AND fecha = CURDATE()";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$area]);
            $stats[$area] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $stats;
    }
}

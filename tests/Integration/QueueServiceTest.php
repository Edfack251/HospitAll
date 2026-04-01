<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\QueueService;
use App\Repositories\QueueRepository;
use PDO;

class QueueServiceTest extends TestCase
{
    private $pdo;
    private $service;
    private $repo;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->pdo = new PDO("mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'hospitall'), ($_ENV['DB_USER'] ?? 'root'), ($_ENV['DB_PASS'] ?? ''));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->service = new QueueService($this->pdo);
        $this->repo = new QueueRepository($this->pdo);
        
        // Iniciar transacción para no afectar la DB real
        $this->pdo->beginTransaction();
        
        // Limpiar para hoy en el área de prueba
        $this->pdo->exec("DELETE FROM turnos WHERE area IN ('consulta', 'laboratorio') AND fecha = CURDATE()");
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Prueba el algoritmo de intercalado 1:2 (1 preferencial por cada 2 generales)
     */
    public function testIntercaladoPrioridad()
    {
        // Generar 5 turnos generales (C-001 a C-005)
        for($i=1; $i<=5; $i++) {
            $this->repo->generarTurno('consulta', 'general', 1, null, 1);
        }
        
        // Generar 2 turnos preferenciales (C-006 y C-007)
        for($i=1; $i<=2; $i++) {
            $this->repo->generarTurno('consulta', 'preferencial', 1, 1, 1);
        }

        // Obtener lista de espera
        $espera = $this->repo->getTurnosEsperando('consulta');
        
        /**
         * Orden esperado (1P:2G):
         * 1. C-006 (Pref 1) -> global_order = 1
         * 2. C-001 (Gen 1)  -> global_order = 2
         * 3. C-002 (Gen 2)  -> global_order = 3
         * 4. C-007 (Pref 2) -> global_order = 4
         * 5. C-003 (Gen 3)  -> global_order = 5
         * 6. C-004 (Gen 4)
         * 7. C-005 (Gen 5)
         */
        
        $this->assertEquals('C-006', $espera[0]['numero'], "El primer turno debe ser el primer preferencial generado (C-006)");
        $this->assertEquals('C-001', $espera[1]['numero']);
        $this->assertEquals('C-002', $espera[2]['numero']);
        $this->assertEquals('C-007', $espera[3]['numero'], "El cuarto turno debe ser el segundo preferencial generado (C-007)");
        $this->assertEquals('C-003', $espera[4]['numero']);
        
        $this->assertCount(7, $espera);
    }

    public function testLlamarSiguiente()
    {
        $this->repo->generarTurno('laboratorio', 'general', 1, null, 1); // L-001
        
        $turno = $this->service->llamarSiguiente('laboratorio');
        
        $this->assertNotNull($turno);
        $this->assertEquals('L-001', $turno['numero']);
        $this->assertEquals('llamado', $turno['estado']); // En el repo está en minúsculas
        
        // Verificar que ya no está en espera
        $espera = $this->repo->getTurnosEsperando('laboratorio');
        $this->assertCount(0, $espera);
    }
}

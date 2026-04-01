<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Repositories\PharmacyRepository;
use App\Services\PharmacyService;
use App\Config\Database;

class PharmacyMovementsTest extends TestCase
{
    private $pdo;
    private $service;
    private $repo;

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->repo = new PharmacyRepository($this->pdo);
        $this->service = new PharmacyService($this->pdo);
    }

    public function testGetMovimientos()
    {
        // Get initial movements
        $initialCount = count($this->service->getMovimientos());

        // We assume there's at least one medication and one user
        $stmtMed = $this->pdo->query("SELECT id FROM medicamentos LIMIT 1");
        $med = $stmtMed->fetch();
        
        $stmtUser = $this->pdo->query("SELECT id FROM usuarios LIMIT 1");
        $user = $stmtUser->fetch();

        if ($med && $user) {
            // Create a movement manually for testing
            $this->repo->createMovimiento($med['id'], 'Entrada', 10, 'Test Movement', $user['id']);

            $movements = $this->service->getMovimientos();
            $this->assertCount($initialCount + 1, $movements);
            
            $latest = $movements[0];
            $this->assertEquals('Entrada', $latest['tipo_movimiento']);
            $this->assertEquals(10, $latest['cantidad']);
            $this->assertEquals('Test Movement', $latest['motivo']);
        } else {
            $this->markTestSkipped('No medications or users found in database to test movements.');
        }
    }

    public function testGetMovimientosWithFilters()
    {
        $stmtMed = $this->pdo->query("SELECT id FROM medicamentos LIMIT 1");
        $med = $stmtMed->fetch();

        if ($med) {
            $filtros = ['medicamento_id' => $med['id']];
            $movements = $this->service->getMovimientos($filtros);
            foreach ($movements as $mov) {
                $this->assertEquals($med['id'], $mov['medicamento_id']);
            }
        } else {
            $this->markTestSkipped('No medications found in database to test filters.');
        }
    }
}

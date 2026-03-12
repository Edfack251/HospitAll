<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Config\Database;
use App\Services\PatientsService;

class PatientsServiceTest extends TestCase
{
    private $pdo;
    private $service;

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->service = new PatientsService($this->pdo);
    }

    public function testCreatePatientSucceedsWithValidData(): void
    {
        $email = 'phpunit_' . time() . '@test.local';
        $identificacion = 'TEST' . time();

        $data = [
            'nombre' => 'PHPUnit',
            'apellido' => 'TestPatient',
            'identificacion' => $identificacion,
            'identificacion_tipo' => 'CEDULA',
            'correo_electronico' => $email,
            'fecha_nacimiento' => '1990-01-01',
            'password' => 'password123',
            'direccion' => 'Test Area',
            'telefono' => '809-000-0000',
            'genero' => 'M',
            'tipo_sangre' => 'O+',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result);

        $found = $this->service->searchByIdentification($identificacion);
        $this->assertNotEmpty($found);
        $patient = $found[0];
        $this->assertSame($identificacion, $patient['identificacion']);
        $this->assertSame($email, $patient['correo_electronico']);
    }
}

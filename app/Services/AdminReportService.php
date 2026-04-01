<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repositories\AppointmentRepository;
use App\Repositories\BillingRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PharmacyRepository;
use App\Repositories\LaboratoryRepository;

class AdminReportService
{
    private $appointmentsRepo;
    private $billingRepo;
    private $patientRepo;
    private $pharmacyRepo;
    private $laboratoryRepo;

    public function __construct(
        AppointmentRepository $appointmentsRepo,
        BillingRepository $billingRepo,
        PatientRepository $patientRepo,
        PharmacyRepository $pharmacyRepo,
        LaboratoryRepository $laboratoryRepo
    ) {
        $this->appointmentsRepo = $appointmentsRepo;
        $this->billingRepo = $billingRepo;
        $this->patientRepo = $patientRepo;
        $this->pharmacyRepo = $pharmacyRepo;
        $this->laboratoryRepo = $laboratoryRepo;
    }

    public function generateMonthlyReportPdf($year, $month, $userName)
    {
        // 1. Obtener métricas de los repositorios
        $metrics = [
            'revenue' => $this->billingRepo->getMonthlyRevenue($year, $month),
            'appointments' => $this->appointmentsRepo->getMonthlyAppointments($year, $month),
            'new_patients' => $this->patientRepo->getMonthlyNewPatients($year, $month),
            'pharmacy_sales' => $this->pharmacyRepo->getMonthlyDispensedMedications($year, $month),
            'lab_tests' => $this->laboratoryRepo->getMonthlyLabTests($year, $month)
        ];

        // 2. Preparar datos para la plantilla
        $data = [
            'system_name' => 'HospitAll',
            'year' => $year,
            'month' => $month,
            'month_name' => strftime('%B', mktime(0, 0, 0, $month, 10)), // Mejorar con array si estraftime falla
            'timestamp' => date('d/m/Y H:i:s'),
            'generated_by' => $userName,
            'metrics' => $metrics
        ];
        
        // Corrección de nombre de mes en español
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $data['month_name'] = $meses[(int)$month];

        // 3. Generar el PDF usando Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        // Cargar la vista
        ob_start();
        extract($data);
        require __DIR__ . '/../../views/pdf/admin_monthly_report_template.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}

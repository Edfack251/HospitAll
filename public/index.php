<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use App\Core\Router;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $pdo = Database::getConnection();
    $GLOBALS['pdo'] = $pdo;
    $router = new Router($pdo);

    // Default route
    $router->addView('/', 'login.php');

    // Authentication
    $router->addView('/login', 'login.php');
    $router->addView('/logout', 'logout.php');
    $router->addView('/register', 'register.php');

    // Protected Views (General Access)
    $protectedViews = [
        'dashboard',
        'dashboard_receptionist',
        'dashboard_laboratory',
        'dashboard_imaging',
        'dashboard_nursing',
        'patients',
        'patients_create',
        'patients_edit',
        'patient_portal',
        'doctors',
        'doctors_create',
        'doctors_edit',
        'doctor_agenda',
        'appointments',
        'appointments_attend',
        'appointments_schedule',
        'appointments_status_update',
        'appointments_reprogram',
        'clinical_history',
        'laboratory',
        'imaging',
        'billing',
        'billing_details',
        'pharmacy',
        'pharmacy_prescriptions',
        'pharmacy_pending_prescriptions',
        'users',
        'users_create',
        'users_edit',
        'logs',
        'hospitalization',
        'hospitalization_rounds'
    ];

    foreach ($protectedViews as $view) {
        $middlewares = ['AuthMiddleware'];

        // Role-based access control for specific views
        if (strpos($view, 'doctors') === 0 || strpos($view, 'users') === 0 || strpos($view, 'logs') === 0) {
            $middlewares[] = ['RoleMiddleware' => ['administrador']];
        }
        if (strpos($view, 'pharmacy') === 0) {
            $middlewares[] = ['RoleMiddleware' => ['farmaceutico', 'administrador']];
        }
        if ($view === 'dashboard_receptionist') {
            $middlewares[] = ['RoleMiddleware' => ['recepcionista', 'administrador']];
        }
        if ($view === 'dashboard_laboratory') {
            $middlewares[] = ['RoleMiddleware' => ['tecnico_laboratorio', 'administrador']];
        }
        if ($view === 'dashboard_imaging') {
            $middlewares[] = ['RoleMiddleware' => ['tecnico_imagenes', 'administrador']];
        }
        if ($view === 'dashboard_nursing') {
            $middlewares[] = ['RoleMiddleware' => ['enfermera', 'administrador']];
        }
        if ($view === 'hospitalization_rounds') {
            $middlewares[] = ['RoleMiddleware' => ['enfermera', 'administrador']];
        }

        $router->addView('/' . $view, $view . '.php', $middlewares);
    }

    // API Routes (Auth)
    $router->add('/api/auth/login', 'AuthController@login', ['CsrfMiddleware']);
    $router->add('/api/auth/register', 'RegisterController@register', ['CsrfMiddleware']);

    // API Routes (Patients)
    $router->add('/api/patients/create', 'PatientsController@handleCreate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/patients/update', 'PatientsController@handleUpdate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/patients/search', 'PatientsController@searchApi', ['AuthMiddleware']);
    $router->add('/api/patients/restore', 'PatientsController@restoreApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);
    $router->add('/api/clinical-history/export-pdf', 'ClinicalHistoryController@exportPdf', ['AuthMiddleware']);

    // API Routes (Doctors)
    $router->add('/api/doctors/store', 'DoctorsController@store', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/doctors/update', 'DoctorsController@update', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/doctors/restore', 'DoctorsController@restoreApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Appointments)
    $router->add('/api/appointments/schedule', 'AppointmentsController@handleSchedule', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/appointments/reprogram', 'AppointmentsController@handleReprogram', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/appointments/saveAttention', 'AppointmentsController@handleSaveAttention', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/appointments/updateStatus', 'AppointmentsController@handleUpdateStatus', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/appointments/horarios-disponibles', 'AppointmentsController@getHorariosDisponibles', ['AuthMiddleware', ['RoleMiddleware' => ['recepcionista', 'medico', 'administrador']]]);

    // API Routes (Billing)
    $router->add('/api/billing/create', 'BillingController@handleCreate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/billing/pay', 'BillingController@handlePay', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/billing/addItem', 'BillingController@handleAddItem', ['AuthMiddleware', 'CsrfMiddleware']);

    // API Routes (Laboratory)
    $router->add('/api/laboratory/upload', 'LaboratoryController@handleUpload', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/laboratory/bill', 'LaboratoryController@bill', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/laboratory/update-status', 'LaboratoryController@updateEstado', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['tecnico_laboratorio', 'administrador']]]);
    $router->add('/api/laboratory/create-walkin', 'LaboratoryController@createWalkinOrder', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['tecnico_laboratorio', 'administrador']]]);
    $router->add('/api/laboratory/order/restore', 'LaboratoryController@restoreOrderApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Imágenes médicas)
    $router->add('/api/imagenes/update-status', 'ImagingController@updateEstado', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['tecnico_imagenes', 'administrador']]]);
    $router->add('/api/imagenes/upload', 'ImagingController@subirArchivo', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['tecnico_imagenes', 'administrador']]]);
    $router->add('/api/imaging/bill', 'ImagingController@bill', ['AuthMiddleware', 'CsrfMiddleware']);

    // API Routes (Enfermería)
    $router->add('/api/enfermeria/signos-vitales', 'NursingController@registrarSignosVitales', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/observacion', 'NursingController@registrarObservacion', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/signos-vitales-cita', 'NursingController@getSignosVitales', ['AuthMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/emergencia/registrar', 'NursingController@registrarEmergencia', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/emergencia/asignar-medico', 'NursingController@asignarMedico', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/emergencia/actualizar-estado', 'NursingController@actualizarEstadoEmergencia', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/emergencia/medicos-disponibles', 'NursingController@getMedicosDisponibles', ['AuthMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/enfermeria/emergencia/crear-paciente', 'NursingController@crearPacienteEmergencia', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);

    // API Routes (Pharmacy)
    $router->add('/api/pharmacy/dispense', 'PharmacyController@handleDispense', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/pharmacy/processDispense', 'PharmacyController@handleProcessDispense', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/pharmacy/medicamento/restore', 'PharmacyController@restoreMedicamentoApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);
    $router->add('/api/pharmacy/registrar-medicamento', 'PharmacyController@registrarMedicamento', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['farmaceutico', 'administrador']]]);
    $router->add('/api/pharmacy/editar-medicamento', 'PharmacyController@editarMedicamento', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['farmaceutico', 'administrador']]]);
    $router->add('/api/pharmacy/ajustar-stock', 'PharmacyController@ajustarStock', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['farmaceutico', 'administrador']]]);
    $router->add('/pharmacy_movimientos', 'PharmacyController@getHistorialMovimientos', ['AuthMiddleware', ['RoleMiddleware' => ['farmaceutico', 'administrador']]]);
    $router->add('/api/pharmacy/medicamentos-stock', 'PharmacyController@getMedicamentosConStock', ['AuthMiddleware']);

    // Asignación de Enfermería
    $router->add('/nursing_assignment', 'NursingAssignmentController@index', ['AuthMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);
    $router->add('/api/enfermeria/asignar-paciente', 'NursingAssignmentController@asignar', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);
    $router->add('/api/enfermeria/eliminar-asignacion', 'NursingAssignmentController@eliminar', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);

    // Turnos
    $router->add('/queue_reception', 'QueueController@index', ['AuthMiddleware', ['RoleMiddleware' => ['recepcionista', 'administrador']]]);
    $router->add('/queue_portal', 'QueueController@portalTurnos', ['AuthMiddleware', ['RoleMiddleware' => ['medico', 'tecnico_laboratorio', 'tecnico_imagenes', 'farmaceutico', 'recepcionista', 'administrador']]]);
    $router->add('/queue_display', 'QueueController@pantallaPublica'); // Acceso público

    $router->add('/api/turnos/generar', 'QueueController@generarTurno', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['recepcionista', 'administrador']]]);
    $router->add('/api/turnos/llamar', 'QueueController@llamarSiguiente', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['recepcionista', 'medico', 'tecnico_laboratorio', 'tecnico_imagenes', 'farmaceutico', 'administrador']]]);
    $router->add('/api/turnos/atendido', 'QueueController@marcarAtendido', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/turnos/cancelar', 'QueueController@cancelarTurno', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['recepcionista', 'administrador']]]);
    $router->add('/api/turnos/estado-salas', 'QueueController@getEstadoSalas'); // Acceso público

    $router->add('/api/appointments/hoy', 'AppointmentsController@getTodayByPatient', ['AuthMiddleware']);

    // Hospitalización
    $router->add('/api/hospitalizacion/internar', 'HospitalizationController@internar', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'enfermera', 'administrador']]]);
    $router->add('/api/hospitalizacion/ronda', 'HospitalizationController@registrarRonda', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['enfermera', 'administrador']]]);
    $router->add('/api/hospitalizacion/evolucion', 'HospitalizationController@registrarEvolucion', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);
    $router->add('/api/hospitalizacion/alta', 'HospitalizationController@darAlta', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);
    $router->add('/api/hospitalizacion/camas-disponibles', 'HospitalizationController@getCamasDisponibles', ['AuthMiddleware', ['RoleMiddleware' => ['medico', 'enfermera', 'administrador']]]);
    $router->add('/api/hospitalizacion/detalle', 'HospitalizationController@getDetalleInternamiento', ['AuthMiddleware', ['RoleMiddleware' => ['medico', 'enfermera', 'administrador', 'recepcionista']]]);

    // API Routes (Users)
    $router->add('/api/users/create', 'UsersController@handleCreate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/users/update', 'UsersController@handleUpdate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/users/restore', 'UsersController@restoreApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Prescriptions)
    $router->add('/api/prescriptions/restore', 'PrescriptionsController@restoreApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Logs)
    $router->add('/api/logs/export', 'LogController@export', ['AuthMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Patient Portal)
    $router->add('/api/patient-portal/dashboard', 'PatientPortalController@index', ['AuthMiddleware']);

    // API Routes (Episodios Clínicos)
    $router->add('/api/episodes/create', 'ClinicalEpisodeController@create', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/episodes/close', 'ClinicalEpisodeController@close', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/episodes', 'ClinicalEpisodeController@getByPatient', ['AuthMiddleware']);

    // API Routes (Patient Flow)
    $router->addView('/patient-flow', 'patient_flow_dashboard.php', ['AuthMiddleware']);
    $router->add('/api/patient-flow/data', 'PatientFlowController@getData', ['AuthMiddleware']);
    $router->add('/api/patient-flow/update-status', 'PatientFlowController@updateStatus', ['AuthMiddleware', 'CsrfMiddleware']);

    // API Routes (Doctor Dashboard)
    $router->add('/api/doctor/dashboard', 'DoctorDashboardController@index', ['AuthMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);

    // Emergencia atención (médico)
    $router->add('/emergency_attend', 'EmergencyCareController@index', ['AuthMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);
    $router->add('/api/emergencia/registrar-atencion', 'EmergencyCareController@registrarAtencion', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);
    $router->add('/api/emergencia/cerrar', 'EmergencyCareController@cerrarEmergencia', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['medico', 'administrador']]]);

    // API Routes (Admin Dashboard)
    $router->add('/api/admin/dashboard', 'AdminDashboardController@index', ['AuthMiddleware']);
    $router->add('/api/admin/reports/monthly', 'AdminReportController@generateMonthlyReport', ['AuthMiddleware']);

    // Process requests
    $requestUri = $_SERVER['REQUEST_URI'];

    try {
        if (!$router->dispatch($requestUri)) {
            exit();
        }
    } catch (\App\Core\Exceptions\AuthorizationException $e) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }

} catch (\App\Core\Exceptions\AuthorizationException $e) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit();
} catch (Exception $e) {
    \App\Core\ErrorHandler::handle($e);
}

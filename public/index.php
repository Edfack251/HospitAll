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
        'clinical_history',
        'laboratory',
        'billing',
        'billing_details',
        'pharmacy',
        'pharmacy_dispense',
        'users',
        'users_create',
        'users_edit',
        'logs'
    ];

    foreach ($protectedViews as $view) {
        $middlewares = ['AuthMiddleware'];

        // Add specific role middlewares based on previous rules
        if (strpos($view, 'doctors') === 0 || strpos($view, 'users') === 0 || strpos($view, 'logs') === 0) {
            $middlewares[] = ['RoleMiddleware' => ['administrador']];
        }
        if (strpos($view, 'pharmacy') === 0) {
            $middlewares[] = ['RoleMiddleware' => ['farmaceutico', 'administrador']];
        }

        $router->addView('/' . $view, $view . '.php', $middlewares);
    }

    // API Routes (Auth)
    $router->add('/api/auth/login', 'AuthController@login', ['CsrfMiddleware']);
    $router->add('/api/auth/register', 'RegisterController@register', ['CsrfMiddleware']);

    // API Routes (Patients)
    $router->add('/api/patients/create', 'PatientsController@handleCreate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/patients/update', 'PatientsController@handleUpdate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/patients/restore', 'PatientsController@restoreApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);
    $router->add('/api/clinical-history/export-pdf', 'ClinicalHistoryController@exportPdf', ['AuthMiddleware']);

    // API Routes (Doctors)
    $router->add('/api/doctors/store', 'DoctorsController@store', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/doctors/update', 'DoctorsController@update', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/doctors/restore', 'DoctorsController@restoreApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Appointments)
    $router->add('/api/appointments/schedule', 'AppointmentsController@handleSchedule', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/appointments/saveAttention', 'AppointmentsController@handleSaveAttention', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/appointments/updateStatus', 'AppointmentsController@handleUpdateStatus', ['AuthMiddleware', 'CsrfMiddleware']);

    // API Routes (Billing)
    $router->add('/api/billing/create', 'BillingController@handleCreate', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/billing/pay', 'BillingController@handlePay', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/billing/addItem', 'BillingController@handleAddItem', ['AuthMiddleware', 'CsrfMiddleware']);

    // API Routes (Laboratory)
    $router->add('/api/laboratory/upload', 'LaboratoryController@handleUpload', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/laboratory/bill', 'LaboratoryController@bill', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/laboratory/order/restore', 'LaboratoryController@restoreOrderApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

    // API Routes (Pharmacy)
    $router->add('/api/pharmacy/dispense', 'PharmacyController@handleDispense', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/pharmacy/processDispense', 'PharmacyController@handleProcessDispense', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/api/pharmacy/medicamento/restore', 'PharmacyController@restoreMedicamentoApi', ['AuthMiddleware', 'CsrfMiddleware', ['RoleMiddleware' => ['administrador']]]);

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
    $router->add('/api/episodes/create', 'ClinicalEpisodeController@create', ['AuthMiddleware']);
    $router->add('/api/episodes/close', 'ClinicalEpisodeController@close', ['AuthMiddleware']);
    $router->add('/api/episodes', 'ClinicalEpisodeController@getByPatient', ['AuthMiddleware']);

    // API Routes (Patient Flow)
    $router->addView('/patient-flow', 'patient_flow_dashboard.php', ['AuthMiddleware']);
    $router->add('/api/patient-flow/data', 'PatientFlowController@getData', ['AuthMiddleware']);
    $router->add('/api/patient-flow/update-status', 'PatientFlowController@updateStatus', ['AuthMiddleware', 'CsrfMiddleware']);

    // API Routes (Doctor Dashboard)
    $router->add('/api/doctor/dashboard', 'DoctorDashboardController@index', ['AuthMiddleware', ['RoleMiddleware' => ['medico']]]);

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

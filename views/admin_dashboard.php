<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Dashboard Admin - HospitAll';
$activePage = 'admin_dashboard';
$headerTitle = 'Panel de Administración';
$headerSubtitle = 'Resumen de métricas operativas clave del sistema.';
include __DIR__ . '/layout/header.php';
?>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Error:</strong>
        <span class="block sm:inline">
            <?php echo htmlspecialchars($error); ?>
        </span>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <div class="glass-card p-6 rounded-2xl shadow-sm border border-blue-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Pacientes Registrados</p>
        <p class="text-4xl font-extrabold text-[#007BFF] mt-2">
            <?php echo $data['patient_count']; ?>
        </p>
    </div>

    <div class="glass-card p-6 rounded-2xl shadow-sm border border-green-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Citas (Hoy / Atendidas)</p>
        <p class="text-2xl font-extrabold text-[#28A745] mt-2">
            <?php echo $data['today_appointments']; ?> <span class="text-xl text-gray-400">/
                <?php echo $data['today_attended']; ?>
            </span>
        </p>
    </div>

    <div class="glass-card p-6 rounded-2xl shadow-sm border border-yellow-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Facturación Hoy</p>
        <p class="text-3xl font-extrabold text-orange-500 mt-2">
            $
            <?php echo number_format($data['today_revenue'], 2); ?>
        </p>
    </div>

    <div class="glass-card p-6 rounded-2xl shadow-sm border border-purple-50">
        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Labs Pendientes</p>
        <p class="text-4xl font-extrabold text-purple-600 mt-2">
            <?php echo $data['pending_lab_results']; ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
    <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            Inventario Crítico (Bajo Stock)
        </h3>
        <p class="text-3xl font-bold text-red-500">
            <?php echo $data['low_stock_medicines']; ?> <span class="text-lg text-gray-500">items</span>
        </p>
    </div>

    <div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            Facturas Pendientes
        </h3>
        <p class="text-3xl font-bold text-yellow-600">
            <?php echo $data['pending_invoices']; ?> <span class="text-lg text-gray-500">facturas</span>
        </p>
    </div>
</div>

<!-- Logs -->
<div class="glass-card p-8 rounded-2xl shadow-sm border border-gray-100 mb-10">
    <h3 class="text-xl font-bold text-gray-800 mb-6">Actividad Reciente del Sistema</h3>
    <?php if (empty($data['recent_logs'])): ?>
        <p class="text-gray-500">No hay actividad reciente.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left font-bold text-gray-500">Fecha</th>
                        <th class="px-4 py-2 text-left font-bold text-gray-500">Usuario</th>
                        <th class="px-4 py-2 text-left font-bold text-gray-500">Acción</th>
                        <th class="px-4 py-2 text-left font-bold text-gray-500">Módulo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($data['recent_logs'] as $log): ?>
                        <tr>
                            <td class="px-4 py-2 text-gray-600">
                                <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-4 py-2 font-medium text-gray-800">
                                <?php echo htmlspecialchars($log['usuario_nombre'] . ' ' . $log['usuario_apellido']); ?>
                            </td>
                            <td class="px-4 py-2 <?php echo ($log['nivel'] === 'ERROR' ? 'text-red-600' : 'text-gray-600'); ?>">
                                <?php echo htmlspecialchars($log['accion']); ?>
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                <?php echo htmlspecialchars($log['modulo']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
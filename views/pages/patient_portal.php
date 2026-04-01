<?php
use App\Helpers\UrlHelper;
use App\Controllers\PatientPortalController;
use App\Helpers\AuthHelper;

AuthHelper::checkRole(['paciente', 'administrador', 'medico']);

$role = $_SESSION['user_role'] ?? '';

if ($role === 'paciente') {
    $paciente_id = $_SESSION['paciente_id'] ?? null;
} elseif (in_array($role, ['administrador', 'medico'])) {
    $paciente_id = isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])
        ? (int) $_GET['patient_id']
        : null;
} else {
    $paciente_id = null;
}

if (!$paciente_id) {
    UrlHelper::redirect('dashboard');
}

$pageTitle = 'Portal del Paciente - HospitAll';
$activePage = 'portal';
$headerTitle = 'Expediente Clínico Digital';

$stmt_pac = $pdo->prepare("SELECT nombre, apellido, identificacion, id FROM pacientes WHERE id = ?");
$stmt_pac->execute([$paciente_id]);
$pac_datos = $stmt_pac->fetch();
$cedula_mask = \App\Helpers\PrivacyHelper::maskCedula($pac_datos['identificacion'] ?? '', $pac_datos['id'] ?? null);

$headerSubtitle = 'Paciente: ' . htmlspecialchars($pac_datos['nombre'] . ' ' . $pac_datos['apellido']) . ' | Cédula: ' . $cedula_mask;

$controller = new PatientPortalController($pdo);

if ($role === 'paciente') {
    $dashboard_data = $controller->index();
    $paciente_id = $dashboard_data['paciente_id'];
} else {
    $old_data = $controller->show($paciente_id);
    $dashboard_data = [
        'citas_proximas' => $old_data['citas_proximas'] ?? [],
        'historial_reciente' => $old_data['historial'] ?? [],
        'laboratorio_disponible' => array_filter($old_data['laboratorio'] ?? [], fn($l) => $l['estado'] === 'Completada'),
        'prescripciones_activas' => array_filter($old_data['prescripciones'] ?? [], fn($p) => $p['estado'] === 'Pendiente'),
        'facturas_pendientes' => []
    ];
}

$citas_proximas = $dashboard_data['citas_proximas'];
$historial = $dashboard_data['historial_reciente'];
$laboratorio = $dashboard_data['laboratorio_disponible'];
$prescripciones = $dashboard_data['prescripciones_activas'];
$facturas_pendientes = $dashboard_data['facturas_pendientes'] ?? [];

include __DIR__ . '/../layout/header.php';
?>

<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    
    <!-- Top Action Bar (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-blue-200">
                <?php echo strtoupper(substr($pac_datos['nombre'] ?? 'P', 0, 1) . substr($pac_datos['apellido'] ?? '', 0, 1)); ?>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight leading-tight"><?php echo htmlspecialchars($pac_datos['nombre'] . ' ' . $pac_datos['apellido']); ?></h2>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest pl-0.5">ID Interno: #<?php echo str_pad($paciente_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <?php if ($role !== 'medico'): ?>
                <a href="<?php echo UrlHelper::url('appointments_schedule'); ?>"
                    class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    NUEVA CITA
                </a>
            <?php endif; ?>
            
            <?php if (in_array($role, ['administrador', 'medico'])): ?>
                <a href="<?php echo UrlHelper::url('api/clinical-history/export-pdf'); ?>?id=<?php echo $paciente_id; ?>" target="_blank" class="flex-1 sm:flex-none flex items-center justify-center gap-2 bg-emerald-600 text-white px-6 py-3.5 rounded-2xl font-bold hover:bg-emerald-700 hover:shadow-xl hover:shadow-emerald-200 transition-all active:scale-95 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar PDF
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
        
        <!-- Sidebar Column (4 cols) -->
        <div class="xl:col-span-4 space-y-10">
            
            <!-- Proximas Citas -->
            <div class="glass-card p-8 rounded-[2.5rem] border border-white/40 shadow-2xl shadow-gray-200/50 bg-white/60 backdrop-blur-xl relative overflow-hidden group">
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-1000"></div>
                
                <div class="relative flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-gray-800 tracking-tight flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                        Agenda Próxima
                    </h3>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <?php if (empty($citas_proximas)): ?>
                    <div class="py-10 text-center space-y-3">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-gray-400 text-sm font-medium italic">Sin citas programadas</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($citas_proximas as $c): ?>
                            <div class="p-5 rounded-3xl bg-white border border-gray-50 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                        <span class="text-sm font-black text-gray-800 tracking-tight"><?php echo date('d M, Y', strtotime($c['fecha'])); ?></span>
                                    </div>
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 bg-gray-100 text-gray-500 rounded-lg">
                                        <?php echo $c['estado']; ?>
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1 pl-4 border-l border-blue-100">
                                    <span class="text-xs font-bold text-gray-400">Médico Especialista</span>
                                    <span class="text-sm font-bold text-gray-700">Dr. <?php echo htmlspecialchars($c['medico_nombre'] . ' ' . $c['medico_apellido']); ?></span>
                                    <div class="flex items-center gap-1.5 mt-2 text-[#007BFF]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span class="text-xs font-black"><?php echo $c['hora']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Laboratorio -->
            <div class="glass-card p-8 rounded-[2.5rem] border border-white/40 shadow-2xl shadow-gray-200/50 bg-white/60 backdrop-blur-xl relative overflow-hidden group">
                 <div class="relative flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-gray-800 tracking-tight flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-purple-600 rounded-full"></div>
                        Digital Health (Lab)
                    </h3>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                </div>

                <?php if (empty($laboratorio)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-400 text-sm italic">Sin resultados analíticos disponibles</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($laboratorio as $l): ?>
                            <div class="p-5 rounded-3xl bg-white border border-gray-50 flex items-center justify-between group/item hover:bg-purple-50/30 transition-all cursor-default shadow-sm sm:shadow-none sm:hover:shadow-md">
                                <div class="truncate max-w-[70%]">
                                    <div class="font-black text-gray-800 text-sm truncate uppercase tracking-tight"><?php echo htmlspecialchars($l['descripcion']); ?></div>
                                    <div class="text-[10px] font-bold text-gray-400 mt-0.5"><?php echo date('d M, Y', strtotime($l['fecha_resultado'] ?? $l['fecha'])); ?></div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($l['archivo_pdf'])): ?>
                                        <a href="<?php echo UrlHelper::url($l['archivo_pdf']); ?>" target="_blank" class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center hover:bg-purple-600 hover:text-white transition-all shadow-sm active:scale-90" title="Ver Resultados">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recetas Médicas -->
            <div class="glass-card p-8 rounded-[2.5rem] border border-white/40 shadow-2xl shadow-gray-200/50 bg-white/60 backdrop-blur-xl relative overflow-hidden group">
                 <div class="relative flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-gray-800 tracking-tight flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-rose-600 rounded-full"></div>
                        Tratamientos
                    </h3>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                </div>

                <?php if (empty($prescripciones)): ?>
                    <div class="text-center py-6 text-gray-400 italic text-sm font-medium">No se registran prescripciones actuales</div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($prescripciones as $p): ?>
                            <div class="p-6 rounded-[2rem] bg-gradient-to-br from-rose-50 to-white border border-rose-100/50 shadow-sm hover:shadow-lg hover:shadow-rose-100/50 transition-all duration-300">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-rose-600 bg-rose-100/50 px-3 py-1 rounded-full border border-rose-200/30"><?php echo $p['estado']; ?></span>
                                    <span class="text-xs font-bold text-gray-400"><?php echo date('d M, Y', strtotime($p['fecha'])); ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-tighter">Médico Responsable</span>
                                    <span class="text-sm font-black text-gray-800 leading-tight">Dr. <?php echo htmlspecialchars($p['medico_nombre'] . ' ' . $p['medico_apellido']); ?></span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-rose-100 flex justify-between items-center">
                                    <button class="text-[10px] font-black text-rose-600 hover:text-rose-800 uppercase tracking-widest transition-colors">Ver Medicamentos</button>
                                    <svg class="w-4 h-4 text-rose-200" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path></svg>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Facturas Pendientes (Mini Card) -->
            <?php if (!empty($facturas_pendientes)): ?>
                <div class="p-8 rounded-[2.5rem] bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-2xl shadow-orange-500/20 ring-1 ring-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-white/20 rounded-xl shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <h3 class="text-xl font-black tracking-tight">Pagos Pendientes</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($facturas_pendientes as $f): ?>
                            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 flex justify-between items-center">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-100 mb-1">Monto Total</p>
                                    <p class="text-2xl font-black tracking-tighter leading-none">$ <?php echo number_format($f['total'], 2); ?></p>
                                </div>
                                <a href="<?php echo UrlHelper::url('billing_pay'); ?>?id=<?php echo $f['id']; ?>" class="bg-white text-orange-600 px-6 py-2.5 rounded-xl font-black text-xs hover:bg-orange-50 transition-all shadow-lg active:scale-95">LIQUIDAR</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Timeline Column (8 cols) -->
        <div class="xl:col-span-8">
            <div class="glass-card p-10 rounded-[3rem] border border-white/50 shadow-2xl shadow-gray-200/40 bg-white/80 backdrop-blur-3xl min-h-full">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
                    <div class="flex items-center gap-4">
                         <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-500 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-800 tracking-tight">Cronología Médica</h3>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-black text-gray-400 uppercase tracking-widest bg-gray-50 border border-gray-100 px-5 py-2.5 rounded-2xl">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Expediente actualizado
                    </div>
                </div>

                <?php if (empty($historial)): ?>
                    <div class="flex flex-col items-center justify-center py-32 space-y-6 opacity-40">
                        <svg class="w-24 h-24 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <p class="text-gray-500 font-bold uppercase tracking-[0.2em] text-sm">No existen eventos registrados</p>
                    </div>
                <?php else: ?>
                    <div class="relative pl-8 sm:pl-12 border-l-2 border-dashed border-blue-100 space-y-16">
                        <?php foreach ($historial as $h): ?>
                            <div class="relative">
                                <!-- Dot Descriptor -->
                                <div class="absolute -left-[41px] sm:-left-[49px] top-0 w-6 sm:w-8 h-6 sm:h-8 rounded-full bg-white border-4 border-blue-600 shadow-lg z-10"></div>
                                
                                <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                                    <div class="space-y-1">
                                        <time class="text-xs font-black text-blue-600 uppercase tracking-widest bg-blue-50 px-4 py-1.5 rounded-full border border-blue-100">
                                            <?php echo date('d M, Y', strtotime($h['fecha'])); ?>
                                        </time>
                                        <h4 class="text-lg font-black text-gray-800 mt-2 pl-1">Consulta General / Digital</h4>
                                        <p class="text-xs font-bold text-gray-400 pl-1 uppercase tracking-tighter">Atención presencial: <span class="text-gray-700">Dr. <?php echo htmlspecialchars($h['medico_nombre'] . ' ' . $h['medico_apellido']); ?></span></p>
                                    </div>
                                    
                                    <?php
                                    $id_label = "patient_id=" . $paciente_id;
                                    $isOwner = (isset($_SESSION['medico_id']) && $h['medico_id'] == $_SESSION['medico_id']);
                                    $isAdmin = ($_SESSION['user_role'] === 'administrador');
                                    $canComplete = (($isOwner || $isAdmin) && ($h['cita_estado'] ?? '') === 'Atendida' && strpos($h['diagnostico'], 'Pendiente') !== false && $h['ordenes_pendientes'] == 0);
                                    
                                    if ($canComplete): ?>
                                        <a href="<?php echo UrlHelper::url('appointments_attend'); ?>?id=<?php echo $h['cita_id']; ?>&from=history&<?php echo $id_label; ?>" class="bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white border border-amber-200 px-6 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-95">
                                            COMPLETAR DIAGNÓSTICO
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                                    <div class="group/note p-8 rounded-[2rem] bg-gray-50/50 border border-gray-100/50 hover:bg-white hover:border-blue-100 hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-500 min-h-[160px]">
                                        <div class="flex items-center gap-2 mb-4">
                                            <div class="w-6 h-6 rounded-lg bg-gray-100 text-gray-400 flex items-center justify-center group-hover/note:bg-blue-600 group-hover/note:text-white transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Diagnóstico Clínico</span>
                                        </div>
                                        <p class="text-sm text-gray-700 leading-relaxed font-medium"><?php echo htmlspecialchars($h['diagnostico']); ?></p>
                                    </div>

                                    <div class="group/note p-8 rounded-[2rem] bg-blue-50/10 border border-blue-50 hover:bg-white hover:border-emerald-100 hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-500 min-h-[160px]">
                                        <div class="flex items-center gap-2 mb-4">
                                             <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-400 flex items-center justify-center group-hover/note:bg-emerald-600 group-hover/note:text-white transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                            </div>
                                            <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Plan de Cuidado</span>
                                        </div>
                                        <p class="text-sm text-blue-800 leading-relaxed font-bold"><?php echo htmlspecialchars($h['tratamiento']); ?></p>
                                    </div>
                                </div>

                                <?php if (!empty($h['observaciones'])): ?>
                                    <div class="mt-4 px-8 py-4 bg-gray-50/30 rounded-2xl border border-dashed border-gray-200">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-3 h-3 text-gray-300 mt-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                            <p class="text-[10px] text-gray-400 font-medium italic leading-normal">
                                                Nota del profesional: <?php echo htmlspecialchars($h['observaciones']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .animate-in { animation: animate-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    @keyframes animate-in {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>
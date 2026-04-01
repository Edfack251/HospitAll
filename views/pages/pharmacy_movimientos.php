<?php
use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;

AuthHelper::checkRole(['farmaceutico', 'administrador']);

$pageTitle = 'Auditoría de Movimientos - HospitAll';
$activePage = 'pharmacy_movimientos';
$headerTitle = 'Movimientos de Inventario';
$headerSubtitle = 'Control total de entradas y salidas de medicamentos.';

include __DIR__ . '/../layout/header.php';

// Cálculos de totales
$totalEntradas = 0;
$totalSalidas = 0;
foreach ($movimientos as $mov) {
    if ($mov['tipo_movimiento'] === 'Entrada') {
        $totalEntradas += $mov['cantidad'];
    } elseif ($mov['tipo_movimiento'] === 'Salida') {
        $totalSalidas += $mov['cantidad'];
    }
}
?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Filtros de Búsqueda -->
    <div class="glass-card p-8 rounded-3xl border border-white/20 shadow-xl shadow-blue-900/5 overflow-hidden relative">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
        
        <form method="GET" action="<?php echo UrlHelper::url('pharmacy_movimientos'); ?>" class="relative grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
            <div class="md:col-span-4">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest pl-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    Medicamento
                </label>
                <select name="medicamento_id" class="w-full bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none appearance-none cursor-pointer">
                    <option value="">Todos los medicamentos</option>
                    <?php foreach ($medicamentos as $med): ?>
                        <option value="<?php echo $med['id']; ?>" <?php echo ($filtros['medicamento_id'] == $med['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($med['nombre'] . ' (' . $med['presentacion'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest pl-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Tipo
                </label>
                <select name="tipo_movimiento" class="w-full bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none appearance-none cursor-pointer">
                    <option value="">Todos los tipos</option>
                    <option value="Entrada" <?php echo ($filtros['tipo_movimiento'] === 'Entrada') ? 'selected' : ''; ?>>Entrada</option>
                    <option value="Salida" <?php echo ($filtros['tipo_movimiento'] === 'Salida') ? 'selected' : ''; ?>>Salida</option>
                    <option value="Ajuste" <?php echo ($filtros['tipo_movimiento'] === 'Ajuste') ? 'selected' : ''; ?>>Ajuste</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest pl-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Desde
                </label>
                <input type="date" name="fecha_desde" value="<?php echo $filtros['fecha_desde']; ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest pl-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Hasta
                </label>
                <input type="date" name="fecha_hasta" value="<?php echo $filtros['fecha_hasta']; ?>" class="w-full bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-3.5 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-3.5 rounded-2xl hover:shadow-lg hover:shadow-blue-200 transition-all active:scale-95">
                    Filtrar
                </button>
                <a href="<?php echo UrlHelper::url('pharmacy_movimientos'); ?>" class="p-3.5 bg-gray-100 text-gray-500 rounded-2xl hover:bg-gray-200 transition-all" title="Limpiar filtros">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </a>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10">
        <div class="group relative overflow-hidden p-8 rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-2xl shadow-emerald-500/20 ring-1 ring-white/20 transition-all hover:-translate-y-1">
            <div class="absolute top-0 right-0 p-6 opacity-20 transition-transform group-hover:scale-110 group-hover:rotate-12">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="relative">
                <p class="text-emerald-100 text-sm font-bold uppercase tracking-widest mb-2">Ingresos al Inventario</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-5xl font-black tabular-nums tracking-tighter"><?php echo number_format($totalEntradas, 0); ?></h3>
                    <span class="text-emerald-100 font-medium">unidades</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs bg-white/10 w-fit px-3 py-1.5 rounded-full backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    Auditado hoy
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden p-8 rounded-3xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-2xl shadow-rose-500/20 ring-1 ring-white/20 transition-all hover:-translate-y-1">
            <div class="absolute top-0 right-0 p-6 opacity-20 transition-transform group-hover:scale-110 group-hover:-rotate-12">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 11-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="relative">
                <p class="text-rose-100 text-sm font-bold uppercase tracking-widest mb-2">Salidas / Despachos</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-5xl font-black tabular-nums tracking-tighter"><?php echo number_format($totalSalidas, 0); ?></h3>
                    <span class="text-rose-100 font-medium">unidades</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs bg-white/10 w-fit px-3 py-1.5 rounded-full backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    Sincronizado
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="mt-10 bg-white rounded-[2rem] shadow-2xl shadow-gray-200/50 border border-gray-100 overflow-hidden mb-12">
        <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
            <div class="flex items-center gap-3">
                <div class="w-2 h-8 bg-blue-600 rounded-full"></div>
                <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Registro Detallado</h3>
            </div>
            <span class="text-xs font-bold text-gray-400 bg-white border border-gray-100 px-4 py-2 rounded-full shadow-sm">
                <?php echo count($movimientos); ?> registros encontrados
            </span>
        </div>
        
        <div class="p-8">
            <table id="movimientosTable" class="w-full text-left border-separate border-spacing-y-3">
                <thead>
                    <tr class="text-gray-400 text-[11px] uppercase tracking-[0.2em]">
                        <th class="pb-4 pl-4 font-black">Fecha / Hora</th>
                        <th class="pb-4 font-black">Medicamento / Presentación</th>
                        <th class="pb-4 font-black">Operación</th>
                        <th class="pb-4 font-black text-center">Cant.</th>
                        <th class="pb-4 font-black">Motivo / Documento</th>
                        <th class="pb-4 pr-4 font-black">Auditado por</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($movimientos as $mov): ?>
                            <tr class="group hover:scale-[1.005] transition-all">
                                <td class="py-5 pl-4 bg-gray-50/50 rounded-l-2xl border-y border-l border-transparent group-hover:bg-white group-hover:border-gray-100 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800"><?php echo date('d/m/Y', strtotime($mov['fecha_movimiento'])); ?></span>
                                        <span class="text-[10px] text-gray-400 font-mono"><?php echo date('H:i', strtotime($mov['fecha_movimiento'])); ?></span>
                                    </div>
                                </td>
                                <td class="py-5 bg-gray-50/50 border-y border-transparent group-hover:bg-white group-hover:border-gray-100 transition-colors">
                                    <div class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($mov['medicamento_nombre']); ?></div>
                                    <div class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold"><?php echo htmlspecialchars($mov['presentacion'] . ' • ' . $mov['concentracion']); ?></div>
                                </td>
                                <td class="py-5 bg-gray-50/50 border-y border-transparent group-hover:bg-white group-hover:border-gray-100 transition-colors">
                                    <?php if ($mov['tipo_movimiento'] === 'Entrada'): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-black uppercase ring-1 ring-emerald-500/20">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path></svg>
                                            Entrada
                                        </span>
                                    <?php elseif ($mov['tipo_movimiento'] === 'Salida'): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-rose-100 text-rose-700 rounded-lg text-[10px] font-black uppercase ring-1 ring-rose-500/20">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"></path></svg>
                                            Salida
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black uppercase ring-1 ring-amber-500/20">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                                            Ajuste
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-5 bg-gray-50/50 border-y border-transparent group-hover:bg-white group-hover:border-gray-100 transition-colors text-center">
                                    <span class="text-lg font-black text-gray-800"><?php echo $mov['cantidad']; ?></span>
                                </td>
                                <td class="py-5 bg-gray-50/50 border-y border-transparent group-hover:bg-white group-hover:border-gray-100 transition-colors">
                                    <p class="text-xs text-gray-500 font-medium max-w-xs leading-relaxed"><?php echo htmlspecialchars($mov['motivo']); ?></p>
                                </td>
                                <td class="py-5 pr-4 bg-gray-50/50 rounded-r-2xl border-y border-r border-transparent group-hover:bg-white group-hover:border-gray-100 transition-colors">
                                    <div class="flex items-center">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700 flex items-center justify-center text-[10px] font-black mr-3 shadow-inner">
                                            <?php echo strtoupper(substr($mov['usuario_nombre'], 0, 1) . substr($mov['usuario_apellido'], 0, 1)); ?>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-700 text-xs"><?php echo htmlspecialchars($mov['usuario_nombre'] . ' ' . $mov['usuario_apellido']); ?></span>
                                            <span class="text-[9px] text-gray-400 uppercase font-black tracking-tighter">Personal Farmacia</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .animate-in { animation: animate-in 0.5s ease-out; }
    @keyframes animate-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#movimientosTable')) {
        $('#movimientosTable').DataTable().destroy();
    }
    
    $('#movimientosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        dom: '<"flex flex-col md:flex-row md:justify-between md:items-center px-8 py-4 bg-gray-50/50 border-b border-gray-100"f>rt<"flex flex-col md:flex-row md:justify-between md:items-center px-8 py-6 bg-gray-50/50 border-t border-gray-100"ip>',
        drawCallback: function() {
            $('.dataTables_filter input').addClass('bg-white border-gray-100 rounded-2xl px-6 py-2.5 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all w-80 outline-none shadow-sm ml-2');
            $('.dataTables_paginate .paginate_button').addClass('px-4 py-2 bg-white rounded-xl mx-1 text-xs font-bold text-gray-500 hover:bg-blue-600 hover:text-white transition-all cursor-pointer border border-gray-100 shadow-sm');
            $('.dataTables_paginate .paginate_button.current').addClass('bg-blue-600 text-white border-blue-600');
        }
    });
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>

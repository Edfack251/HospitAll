<?php
use App\Helpers\UrlHelper;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Monitor de Turnos'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .turno-glow { text-shadow: 0 0 20px rgba(255,255,255,0.3); }
        .area-card { transition: all 0.3s ease; }
        @keyframes pulse-custom {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-new-turno { animation: pulse-custom 2s infinite; }
    </style>
</head>
<body class="bg-[#0f172a] text-white h-screen flex flex-col overflow-hidden p-6">
    
    <!-- Header -->
    <header class="flex justify-between items-center mb-8 border-b border-gray-800 pb-6">
        <div class="flex items-center gap-4">
            <div class="bg-blue-600 p-2 rounded-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white">Hospit<span class="text-blue-500">All</span></h1>
                <p class="text-gray-400 text-sm font-medium tracking-widest uppercase">Sistema de Turnos en Tiempo Real</p>
            </div>
        </div>
        <div class="text-right">
            <div id="current-time" class="text-4xl font-black text-white tabular-nums">00:00:00</div>
            <div id="current-date" class="text-blue-400 text-sm font-bold uppercase tracking-widest">SÁBADO, 21 DE MARZO</div>
        </div>
    </header>

    <!-- Main Grid -->
    <main class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $areasConfig = [
            'consulta' => ['color' => 'blue', 'hex' => '#007BFF'],
            'laboratorio' => ['color' => 'green', 'hex' => '#28A745'],
            'farmacia' => ['color' => 'orange', 'hex' => '#FD7E14'],
            'imagenes' => ['color' => 'indigo', 'hex' => '#6366F1']
        ];

        foreach ($areasConfig as $key => $config):
        ?>
        <div id="card-<?php echo $key; ?>" class="area-card bg-gray-800/50 rounded-[2rem] border border-gray-700/50 flex flex-col overflow-hidden shadow-2xl backdrop-blur-sm">
            <!-- Area Label -->
            <div class="p-6 text-center border-b border-gray-700/50" style="background-color: <?php echo $config['hex']; ?>22">
                <h2 class="text-xl font-black uppercase tracking-widest" style="color: <?php echo $config['hex']; ?>">
                    <?php echo ($key === 'imagenes' ? 'Imágenes' : ucfirst($key)); ?>
                </h2>
            </div>

            <!-- Current Turn -->
            <div class="flex-1 flex flex-col items-center justify-center p-8 border-b border-gray-700/50">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Llamando Ahora</span>
                <div id="actual-num-<?php echo $key; ?>" class="text-[7rem] font-black leading-none mb-4 turno-glow" style="color: <?php echo $config['hex']; ?>">
                    --
                </div>
                <div id="actual-name-<?php echo $key; ?>" class="text-lg font-bold text-white text-center truncate w-full px-4">
                    Esperando...
                </div>
            </div>

            <!-- Previous Turns -->
            <div class="bg-gray-900/50 p-6">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-4 block">Últimos llamados</span>
                <div id="prev-container-<?php echo $key; ?>" class="space-y-3">
                    <div class="bg-gray-800/80 rounded-xl p-3 flex justify-between items-center border border-gray-700/50">
                        <span class="text-xl font-black text-gray-300">--</span>
                        <span class="text-xs font-bold text-gray-500 truncate w-32 text-right">--</span>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-between items-center pt-4 border-t border-gray-800">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">En Espera</span>
                    <span id="wait-count-<?php echo $key; ?>" class="bg-gray-700 text-white text-xs font-black px-3 py-1 rounded-full">0</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </main>

    <!-- Footer / News Ticker -->
    <footer class="mt-8 bg-blue-600/10 border border-blue-500/20 rounded-2xl p-4 flex items-center gap-4">
        <div class="bg-blue-600 text-white text-[10px] font-black px-3 py-1 rounded-lg uppercase whitespace-nowrap">Aviso</div>
        <div class="flex-1 overflow-hidden">
            <p class="text-blue-200 text-sm font-medium">Favor de estar atentos a su número en pantalla. Los pacientes con cita tienen prioridad preferencial.</p>
        </div>
    </footer>

    <script>
        const API_URL = '<?php echo UrlHelper::url('api/turnos/estado-salas'); ?>';
        const areas = ['consulta', 'laboratorio', 'farmacia', 'imagenes'];
        let lastState = {};

        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('es-ES', { hour12: false });
            const dateStr = now.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }).toUpperCase();
            
            document.getElementById('current-time').innerText = timeStr;
            document.getElementById('current-date').innerText = dateStr;
        }

        async function fetchData() {
            try {
                const response = await fetch(API_URL);
                const data = await response.json();
                
                areas.forEach(area => {
                    const info = data[area];
                    const numEl = document.getElementById(`actual-num-${area}`);
                    const nameEl = document.getElementById(`actual-name-${area}`);
                    const waitEl = document.getElementById(`wait-count-${area}`);
                    const prevContainer = document.getElementById(`prev-container-${area}`);

                    // Actualizar datos básicos
                    const currentNum = info.actual ? info.actual.numero : '--';
                    const currentName = info.actual ? `${info.actual.paciente_nombre} ${info.actual.paciente_apellido}` : 'Libre';
                    
                    // Efecto si cambia el turno
                    if (lastState[area] && lastState[area].num !== currentNum && currentNum !== '--') {
                        numEl.classList.add('animate-new-turno');
                        setTimeout(() => numEl.classList.remove('animate-new-turno'), 10000);
                        reproducirBeep();
                    }

                    numEl.innerText = currentNum;
                    nameEl.innerText = currentName;
                    waitEl.innerText = info.esperando_count;

                    // Actualizar historial
                    prevContainer.innerHTML = '';
                    if (info.ultimos_llamados.length === 0) {
                        prevContainer.innerHTML = '<div class="text-[10px] text-gray-600 p-2 text-center italic">Sin historial reciente</div>';
                    } else {
                        info.ultimos_llamados.forEach(prev => {
                            const row = document.createElement('div');
                            row.className = 'bg-gray-800/80 rounded-xl p-3 flex justify-between items-center border border-gray-700/50 opacity-60';
                            row.innerHTML = `
                                <span class="text-xl font-black text-gray-300">${prev.numero}</span>
                                <span class="text-xs font-bold text-gray-500 truncate w-32 text-right">${prev.paciente_nombre}</span>
                            `;
                            prevContainer.appendChild(row);
                        });
                    }

                    lastState[area] = { num: currentNum };
                });
            } catch (error) {
                console.error('Error fetching turnos:', error);
            }
        }

        function reproducirBeep() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); 
                gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                oscillator.start();
                oscillator.stop(audioCtx.currentTime + 0.3);
            } catch(e) {}
        }

        setInterval(updateTime, 1000);
        setInterval(fetchData, 5000);
        updateTime();
        fetchData();
        
        // Fullscreen toggle on double click
        document.body.addEventListener('dblclick', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    </script>
</body>
</html>

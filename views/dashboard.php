<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnergyDash | Live Monitoring</title>
    <link rel="icon" href="data:,"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #0F172A; scroll-behavior: smooth; }
        .chart-container { position: relative; height: 350px; width: 100%; }
        /* Scrollbar personalizada para máxima legibilidad */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1E293B; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #38BDF8; }
    </style>
</head>
<body class="text-slate-100 min-h-screen">
    <?php 
    // BLINDAJE DE VARIABLES (Evita errores de variable indefinida)
    $deviceId = $deviceId ?? 'esp32_casa_1';
    $currentFilter = $currentFilter ?? 'today';
    $latest = $latest ?? ['power' => 0, 'current' => 0, 'device_id' => $deviceId, 'created_at' => date('Y-m-d H:i:s')];
    $readings = $readings ?? [];
    $ai = $ai ?? ['is_anomaly' => false, 'reason' => '✓ CONSUMO ESTABLE', 'score' => 0, 'mean' => 0];
    ?>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#1E293B] h-screen sticky top-0 border-r border-slate-700/50 p-6 hidden md:block">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center shadow-lg shadow-sky-500/20">
                    <span class="text-white font-bold">E</span>
                </div>
                <h1 class="text-xl font-bold text-white tracking-tight">EnergyDash</h1>
            </div>
            <nav class="space-y-4">
                <p class="text-slate-300 text-[10px] uppercase font-bold tracking-widest px-3">Menú Principal</p>
                <a href="?device=<?= $deviceId ?>&view=today" class="flex items-center gap-3 p-3 bg-sky-500/10 text-sky-400 rounded-xl font-semibold border border-sky-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6 md:p-10">
            <!-- Header con Selector Multidispositivo -->
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h2 class="text-4xl font-black text-white mb-2">Monitor Live</h2>
                    <div class="flex items-center gap-4">
                        <div class="flex bg-slate-800/80 p-1 rounded-2xl border border-slate-700">
                            <a href="?device=esp32_casa_1&view=<?= $currentFilter ?>" 
                               class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($deviceId == 'esp32_casa_1') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'text-slate-100 hover:text-white' ?>">
                                CASA 1
                            </a>
                            <a href="?device=esp32_casa_2&view=<?= $currentFilter ?>" 
                               class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($deviceId == 'esp32_casa_2') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'text-slate-100 hover:text-white' ?>">
                                OFICINA
                            </a>
                        </div>
                        <span class="text-slate-400 text-sm font-medium">|</span>
                        <p class="text-white text-sm font-semibold">ID: <span class="text-sky-400 font-mono"><?= htmlspecialchars($deviceId) ?></span></p>
                    </div>
                </div>
                
                <div class="bg-emerald-500/10 p-3 px-6 rounded-2xl border border-emerald-500/20 flex items-center gap-3">
                    <div class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-xs font-black uppercase text-emerald-400 tracking-widest">Sistema Conectado</span>
                </div>
            </header>

            <!-- Widgets de Potencia y Corriente -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-[#1E293B] p-6 rounded-3xl border border-slate-700/50 shadow-2xl group">
                    <p class="text-white text-xs font-bold uppercase tracking-wider mb-4">Potencia Activa</p>
                    <div class="flex items-end gap-2">
                        <h3 id="val-power" class="text-6xl font-black text-white tracking-tighter"><?= number_format($latest['power'], 1) ?></h3>
                        <span class="text-sky-400 font-black text-xl mb-2">W</span>
                    </div>
                </div>

                <div class="bg-[#1E293B] p-6 rounded-3xl border border-slate-700/50 shadow-2xl group">
                    <p class="text-white text-xs font-bold uppercase tracking-wider mb-4">Corriente (RMS)</p>
                    <div class="flex items-end gap-2">
                        <h3 id="val-current" class="text-6xl font-black text-white tracking-tighter"><?= number_format($latest['current'], 2) ?></h3>
                        <span class="text-amber-400 font-black text-xl mb-2">A</span>
                    </div>
                </div>

                <!-- IA Card -->
                <div id="ai-card" class="md:col-span-2 p-6 rounded-3xl border transition-all duration-500 <?= ($ai['is_anomaly']) ? 'bg-rose-600 border-white shadow-[0_0_50px_rgba(225,29,72,0.4)]' : 'bg-[#1E293B] border-slate-700/50' ?>">
                    <div class="flex justify-between items-start">
                        <div class="space-y-1">
                            <p class="text-white text-xs font-bold uppercase tracking-wider mb-2">Diagnóstico IA</p>
                            <h3 id="ai-label" class="text-3xl font-black <?= ($ai['is_anomaly']) ? 'text-white' : 'text-emerald-400' ?>">
                                <?= $ai['reason'] ?>
                            </h3>
                            <p id="ai-stats" class="text-xs font-bold <?= ($ai['is_anomaly']) ? 'text-white' : 'text-slate-300' ?> uppercase mt-2">
                                Z-Score: <?= $ai['score'] ?> | Media: <?= $ai['mean'] ?> W
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfica con Ejes Rotados y Títulos -->
            <div class="bg-[#1E293B] p-8 rounded-3xl border border-slate-700/50 shadow-2xl mb-10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-white">Flujo Energético</h3>
                    <div class="flex gap-2 bg-slate-900 p-1.5 rounded-2xl border border-slate-700">
                        <a href="?device=<?= $deviceId ?>&view=today" class="px-6 py-2 <?= ($currentFilter == 'today') ? 'bg-sky-500 text-white shadow-lg' : 'text-slate-400' ?> text-xs font-black rounded-xl transition-all">HOY</a>
                        <a href="?device=<?= $deviceId ?>&view=week" class="px-6 py-2 <?= ($currentFilter == 'week') ? 'bg-sky-500 text-white' : 'text-slate-400' ?> text-xs font-black rounded-xl transition-all">7D</a>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="mainEnergyChart"></canvas>
                </div>
            </div>

            <!-- Tabla de Registros -->
            <div class="bg-[#1E293B] rounded-3xl border border-slate-700/50 shadow-2xl overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-900 text-white text-[10px] uppercase tracking-[0.2em] font-black">
                        <tr>
                            <th class="p-5">Marca de Tiempo</th>
                            <th class="p-5">Consumo (Watts)</th>
                            <th class="p-5 text-right">Amperaje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50 text-sm font-medium">
                        <?php if (!empty($readings)): foreach($readings as $r): ?>
                        <tr class="hover:bg-slate-800 transition-colors">
                            <td class="p-5 text-white">
                                <script>
                                    document.write(new Date("<?= $r['created_at'] ?>").toLocaleString('es-MX', {
                                        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit'
                                    }));
                                </script>
                            </td>
                            <td class="p-5 text-white font-bold"><?= number_format($r['power'], 1) ?> W</td>
                            <td class="p-5 text-right font-black text-amber-300"><?= number_format($r['current'], 3) ?> A</td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="3" class="p-20 text-center text-white italic">Esperando datos del dispositivo...</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

<script>
    // 1. Configuración de Gráfica (Ejes en Blanco, Títulos y Rotación)
    const ctx = document.getElementById('mainEnergyChart').getContext('2d');
    const MAX_POINTS = 30;
    let liveLabels = new Array(MAX_POINTS).fill("--:--:--");
    let liveData = new Array(MAX_POINTS).fill(0);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(56, 189, 248, 0.4)');
    gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

    let energyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: liveLabels,
            datasets: [{
                label: 'Potencia',
                data: liveData,
                borderColor: '#38BDF8',
                borderWidth: 4,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            animation: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    title: { display: true, text: 'WATTS', color: '#FFFFFF', font: { weight: 'bold', size: 12 } },
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#FFFFFF', font: { weight: 'bold' } },
                    suggestedMin: 0,
                    suggestedMax: 100 
                },
                x: { 
                    title: { display: true, text: 'TIEMPO', color: '#FFFFFF', font: { weight: 'bold', size: 12 } },
                    grid: { display: false }, 
                    ticks: { 
                        color: '#FFFFFF', 
                        font: { size: 10, weight: 'bold' },
                        maxRotation: 45, // Rotación Diagonal
                        minRotation: 45 
                    } 
                }
            }
        }
    });

    // 2. Lógica Live
    const alertSound = new Audio('https://www.soundjay.com/buttons/beep-01a.mp3');
    let isSwalActive = false; 
    let lastAlertTimestamp = null;

    async function updateDashboard() {
        try {
            const response = await fetch(`api.php?view=<?= $currentFilter ?>&device=<?= $deviceId ?>`);
            const data = await response.json();

            let powerNow = 0;
            let timeLabel = new Date().toLocaleTimeString('es-MX', {hour: '2-digit', minute:'2-digit', second:'2-digit'});

            if (data && data.latest) {
                const lastSeen = new Date(data.latest.created_at);
                const secondsSinceUpdate = (new Date() - lastSeen) / 1000;

                if (secondsSinceUpdate < 7) { 
                    powerNow = parseFloat(data.latest.power);
                    timeLabel = lastSeen.toLocaleTimeString('es-MX', {hour: '2-digit', minute:'2-digit', second:'2-digit'});
                    document.getElementById('val-power').innerText = powerNow.toFixed(1);
                    document.getElementById('val-current').innerText = parseFloat(data.latest.current).toFixed(2);
                } else {
                    powerNow = 0;
                    document.getElementById('val-power').innerText = "0.0";
                    document.getElementById('val-current').innerText = "0.00";
                }
            }

            // Scroll de Gráfica
            liveLabels.push(timeLabel);
            liveData.push(powerNow);
            liveLabels.shift();
            liveData.shift();
            energyChart.update('none');

            // Alertas IA
            const aiCard = document.getElementById('ai-card');
            const aiLabel = document.getElementById('ai-label');

            if (data.ai && data.ai.is_anomaly && powerNow > 0) {
                aiCard.className = "md:col-span-2 p-6 rounded-3xl border transition-all duration-500 bg-rose-600 border-white animate-pulse shadow-[0_0_50px_rgba(225,29,72,0.6)]";
                aiLabel.innerText = data.ai.reason; 
                aiLabel.className = "text-3xl font-black text-white";
                
                if (!isSwalActive && data.latest.created_at !== lastAlertTimestamp) {
                    isSwalActive = true;
                    alertSound.play().catch(e => {});
                    Swal.fire({
                        title: '¡EMERGENCIA!',
                        html: `<span style="font-size: 2rem; font-weight: 900; color: #f43f5e;">${powerNow} Watts</span><br><p style="color: #64748b">${data.ai.reason}</p>`,
                        icon: 'error',
                        background: '#1e293b',
                        color: '#fff',
                        confirmButtonText: 'ENTENDIDO',
                        confirmButtonColor: '#e11d48'
                    }).then(() => {
                        lastAlertTimestamp = data.latest.created_at;
                        isSwalActive = false;
                    });
                }
            } else {
                aiCard.className = "md:col-span-2 p-6 rounded-3xl border border-slate-700/50 bg-[#1E293B]";
                aiLabel.innerText = powerNow === 0 ? "DESCONECTADO" : "✓ CONSUMO ESTABLE";
                aiLabel.className = "text-2xl font-bold text-emerald-400";
            }
        } catch (e) { console.error("Error Live:", e); }
    }

    setInterval(updateDashboard, 1000);
    updateDashboard();
</script>
</body>
</html>
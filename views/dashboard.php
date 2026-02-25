<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnergyDash | Live</title>
    <link rel="icon" href="data:,"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #0F172A; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="text-slate-200 min-h-screen">
    <div class="flex">
        <aside class="w-64 bg-[#1E293B] h-screen sticky top-0 border-r border-slate-700/50 p-6 hidden md:block">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center shadow-lg"><span class="text-white font-bold">E</span></div>
                <h1 class="text-xl font-bold text-white">EnergyDash</h1>
            </div>
            <nav class="space-y-2">
                <a href="?view=today" class="block p-3 bg-sky-500/10 text-sky-400 rounded-xl font-medium">Dashboard</a>
            </nav>
        </aside>

        <main class="flex-1 p-6 md:p-10">
            <header class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-white">Consumo Energético</h2>
                    <p class="text-slate-400 text-sm">Dispositivo: <span class="text-sky-400 font-mono"><?= htmlspecialchars($latest['device_id'] ?? 'S/N') ?></span></p>
                </div>
                <div class="bg-slate-800/50 p-2 px-4 rounded-2xl border border-slate-700 flex items-center gap-3">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-xs font-bold uppercase text-emerald-500">Live Data</span>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-[#1E293B] p-6 rounded-3xl border border-slate-700/50 shadow-xl">
                    <p class="text-slate-400 text-sm font-medium mb-1">Potencia Activa</p>
                    <div class="flex items-end gap-2 text-white">
                        <h3 id="val-power" class="text-5xl font-bold"><?= number_format($latest['power'] ?? 0, 1) ?></h3>
                        <span class="text-sky-400 font-bold mb-1">W</span>
                    </div>
                </div>

                <div class="bg-[#1E293B] p-6 rounded-3xl border border-slate-700/50 shadow-xl">
                    <p class="text-slate-400 text-sm font-medium mb-1">Corriente (RMS)</p>
                    <div class="flex items-end gap-2 text-white">
                        <h3 id="val-current" class="text-5xl font-bold"><?= number_format($latest['current'] ?? 0, 2) ?></h3>
                        <span class="text-amber-400 font-bold mb-1">A</span>
                    </div>
                </div>

                <div id="ai-card" class="md:col-span-2 p-6 rounded-3xl border transition-all duration-500 <?= ($ai['is_anomaly'] ?? false) ? 'bg-rose-500/20 border-rose-500 animate-pulse' : 'bg-[#1E293B] border-slate-700/50' ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-400 text-sm font-medium mb-1">IA: Monitoreo</p>
                            <h3 id="ai-label" class="text-2xl font-bold <?= ($ai['is_anomaly'] ?? false) ? 'text-rose-500' : 'text-emerald-500' ?>">
                                <?= ($ai['is_anomaly'] ?? false) ? $ai['reason'] : '✓ CONSUMO ESTABLE' ?>
                            </h3>
                            <p id="ai-stats" class="text-xs mt-2 text-slate-500 uppercase tracking-tighter">
                                Z-Score: <?= $ai['score'] ?? 0 ?> | Media: <?= $ai['mean'] ?? 0 ?> W
                            </p>
                        </div>
                        <div id="ai-icon" class="<?= ($ai['is_anomaly'] ?? false) ? 'text-rose-500' : 'text-emerald-500' ?>">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[#1E293B] p-8 rounded-3xl border border-slate-700/50 shadow-xl mb-10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xl font-bold text-white">Historial de Consumo</h3>
                    <div class="flex gap-2 bg-slate-900/50 p-1 rounded-xl">
                        <a href="?view=today" class="px-4 py-1.5 <?= ($currentFilter ?? 'today') == 'today' ? 'bg-sky-500 text-white shadow-lg' : 'text-slate-400' ?> text-xs font-bold rounded-lg transition">HOY</a>
                        <a href="?view=week" class="px-4 py-1.5 <?= ($currentFilter ?? '') == 'week' ? 'bg-sky-500 text-white' : 'text-slate-400' ?> text-xs font-bold rounded-lg transition">7D</a>
                        <a href="?view=month" class="px-4 py-1.5 <?= ($currentFilter ?? '') == 'month' ? 'bg-sky-500 text-white' : 'text-slate-400' ?> text-xs font-bold rounded-lg transition">30D</a>
                    </div>
                </div>
                <div class="h-[300px]"><canvas id="mainEnergyChart"></canvas></div>
            </div>

            <div class="bg-[#1E293B] rounded-3xl border border-slate-700/50 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-900/50 text-slate-500 text-xs uppercase tracking-widest">
                        <tr><th class="p-4">Fecha/Hora</th><th class="p-4">Watts</th><th class="p-4">Amperes</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30 text-sm">
                        <?php if (!empty($readings)): foreach($readings as $r): ?>
                        <tr class="hover:bg-slate-800/20">
                            <td class="p-4 text-slate-400">
                                <script>
                                    document.write(new Date("<?= $r['created_at'] ?>").toLocaleString([], {
                                        day: '2-digit', 
                                        month: 'short', 
                                        hour: '2-digit', 
                                        minute: '2-digit', 
                                        second: '2-digit'
                                    }));
                                </script>
                            </td>
                            <td class="p-4 font-bold text-white"><?= $r['power'] ?? 0 ?> W</td>
                            <td class="p-4 text-amber-500"><?= $r['current'] ?? 0 ?> A</td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="3" class="p-10 text-center text-slate-500">No hay lecturas disponibles.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    |<script>
    // 1. Configuración de la Gráfica (Efecto Monitor Cardíaco)
    const ctx = document.getElementById('mainEnergyChart').getContext('2d');
    const MAX_POINTS = 30; // Cuántos segundos queremos ver en pantalla
    
    // Inicializamos arreglos locales para que la gráfica siempre tenga datos
    let liveLabels = new Array(MAX_POINTS).fill("--:--:--");
    let liveData = new Array(MAX_POINTS).fill(0);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(56, 189, 248, 0.3)');
    gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

    let energyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: liveLabels,
            datasets: [{
                label: 'Consumo (W)',
                data: liveData,
                borderColor: '#38BDF8',
                borderWidth: 3,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4,
                pointRadius: 0 // Sin puntos para que se vea como monitor médico
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            animation: false, // Animación false para que el scroll sea fluido
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    suggestedMin: 0,
                    suggestedMax: 200 // Se ajustará solo si hay picos
                },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Control de Alertas y Memoria
    const alertSound = new Audio('https://www.soundjay.com/buttons/beep-01a.mp3');
    let isSwalActive = false; 
    let lastAlertTimestamp = null;

    async function updateDashboard() {
        try {
            const response = await fetch('api.php?view=<?= $currentFilter ?? 'today' ?>');
            const data = await response.json();

            // Lógica de "Reloj de Arena":
            // Comparamos la hora actual vs la hora del último dato en la DB
            let powerNow = 0;
            let timeLabel = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});

            if (data.latest) {
                const lastSeen = new Date(data.latest.created_at);
                const secondsSinceUpdate = (new Date() - lastSeen) / 1000;

                // Si el dato tiene menos de 5 segundos, el dispositivo está "vivo"
                if (secondsSinceUpdate < 5) {
                    powerNow = parseFloat(data.latest.power);
                    timeLabel = lastSeen.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
                    
                    // Actualizar widgets numéricos solo si está vivo
                    document.getElementById('val-power').innerText = powerNow.toFixed(1);
                    document.getElementById('val-current').innerText = parseFloat(data.latest.current).toFixed(2);
                } else {
                    // Si el dato es viejo, mostramos 0 en los widgets
                    document.getElementById('val-power').innerText = "0.0";
                    document.getElementById('val-current').innerText = "0.00";
                }
            }

            // --- EL EFECTO SCROLL ---
            // Quitamos el dato más viejo y metemos el nuevo (ya sea power o 0)
            liveLabels.push(timeLabel);
            liveData.push(powerNow);
            liveLabels.shift();
            liveData.shift();

            // Actualizar gráfica
            energyChart.update('none');

            // --- LÓGICA DE ALERTAS (Se mantiene igual para seguridad) ---
            if (data.ai && data.ai.is_anomaly && powerNow > 0) {
                const aiCard = document.getElementById('ai-card');
                const aiLabel = document.getElementById('ai-label');
                
                aiCard.className = "md:col-span-2 p-6 rounded-3xl border transition-all duration-500 bg-rose-600 border-white animate-pulse shadow-[0_0_50px_rgba(244,63,94,0.6)]";
                aiLabel.innerText = data.ai.reason;
                document.getElementById('ai-stats').innerText = `Z-Score: ${data.ai.score} | Media: ${data.ai.mean}W`;

                if (!isSwalActive && data.latest.created_at !== lastAlertTimestamp) {
                    isSwalActive = true;
                    alertSound.play().catch(e => {});
                    Swal.fire({
                        title: '¡ALERTA!',
                        html: `Detectado: ${powerNow} W`,
                        icon: 'error',
                        confirmButtonText: 'ENTENDIDO'
                    }).then(() => {
                        lastAlertTimestamp = data.latest.created_at;
                        isSwalActive = false;
                    });
                }
            } else if (powerNow === 0) {
                document.getElementById('ai-label').innerText = "OFFLINE / ESPERA";
                document.getElementById('ai-card').className = "md:col-span-2 p-6 rounded-3xl border border-slate-700/50 bg-[#1E293B]";
            }

        } catch (e) { console.error("Error Live:", e); }
    }

    // Intervalo de 1 segundo exacto para el efecto monitor
    setInterval(updateDashboard, 1000);
    updateDashboard();
</script>
</body>
</html>
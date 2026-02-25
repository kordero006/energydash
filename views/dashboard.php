<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnergyDash | Live</title>
    <link rel="icon" href="data:,"> <script src="https://cdn.tailwindcss.com"></script>
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
                    <p class="text-slate-400 text-sm">Dispositivo: <span class="text-sky-400 font-mono"><?= htmlspecialchars($latest['device_id']) ?></span></p>
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
                        <h3 id="val-power" class="text-5xl font-bold"><?= number_format($latest['power'], 1) ?></h3>
                        <span class="text-sky-400 font-bold mb-1">W</span>
                    </div>
                </div>

                <div class="bg-[#1E293B] p-6 rounded-3xl border border-slate-700/50 shadow-xl">
                    <p class="text-slate-400 text-sm font-medium mb-1">Corriente (RMS)</p>
                    <div class="flex items-end gap-2 text-white">
                        <h3 id="val-current" class="text-5xl font-bold"><?= number_format($latest['current'], 2) ?></h3>
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
                        <a href="?view=today" class="px-4 py-1.5 <?= $currentFilter == 'today' ? 'bg-sky-500 text-white shadow-lg' : 'text-slate-400' ?> text-xs font-bold rounded-lg transition">HOY</a>
                        <a href="?view=week" class="px-4 py-1.5 <?= $currentFilter == 'week' ? 'bg-sky-500 text-white' : 'text-slate-400' ?> text-xs font-bold rounded-lg transition">7D</a>
                        <a href="?view=month" class="px-4 py-1.5 <?= $currentFilter == 'month' ? 'bg-sky-500 text-white' : 'text-slate-400' ?> text-xs font-bold rounded-lg transition">30D</a>
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
                        <?php foreach($readings as $r): ?>
                        <tr class="hover:bg-slate-800/20">
                            <td class="p-4 text-slate-400"><?= date('d M, H:i:s', strtotime($r['created_at'])) ?></td>
                            <td class="p-4 font-bold text-white"><?= $r['power'] ?> W</td>
                            <td class="p-4 text-amber-500"><?= $r['current'] ?> A</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-4 bg-slate-800/50 border-t border-slate-700/50 flex justify-between items-center">
                    <span class="text-xs text-slate-500 font-bold uppercase">Página <?= $currentPage ?></span>
                    <div class="flex gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="?view=<?= $currentFilter ?>&page=<?= $currentPage - 1 ?>" class="px-4 py-2 bg-slate-700 text-white text-xs font-bold rounded-lg">ANTERIOR</a>
                        <?php endif; ?>
                        <a href="?view=<?= $currentFilter ?>&page=<?= $currentPage + 1 ?>" class="px-4 py-2 bg-sky-500 text-white text-xs font-bold rounded-lg">SIGUIENTE</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // 1. Inicialización de la Gráfica
        const ctx = document.getElementById('mainEnergyChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.2)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        let energyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Consumo (W)',
                    data: [],
                    borderColor: '#38BDF8',
                    borderWidth: 3,
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
                    y: { grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Variables de control para Alertas "Escandalosas"
        const alertSound = new Audio('https://www.soundjay.com/buttons/beep-01a.mp3');
        let isSwalActive = false; // Bloqueador para que no salgan mil ventanas

        async function updateDashboard() {
            try {
                const response = await fetch('api.php?view=<?= $currentFilter ?>');
                const data = await response.json();

                if (!data.latest) return;

                // Actualizar Números Principales
                document.getElementById('val-power').innerText = parseFloat(data.latest.power).toFixed(1);
                document.getElementById('val-current').innerText = parseFloat(data.latest.current).toFixed(2);
                document.getElementById('ai-stats').innerText = `Z-Score: ${data.ai.score} | Media: ${data.ai.mean}W`;

                const aiCard = document.getElementById('ai-card');
                const aiLabel = document.getElementById('ai-label');

                // --- LÓGICA DE ALERTA DE ALTO IMPACTO ---
                if (data.ai.is_anomaly) {
                    // A. Efectos en el Dashboard (Colores y Animación)
                    aiCard.className = "md:col-span-2 p-6 rounded-3xl border transition-all duration-500 bg-rose-600 border-white animate-pulse shadow-[0_0_50px_rgba(244,63,94,0.6)]";
                    aiLabel.innerText = data.ai.reason; 
                    aiLabel.className = "text-2xl font-black text-white";
                    document.title = "⚠️ ¡EMERGENCIA DETECTADA!";

                    // B. Lanzar SweetAlert (Solo si no hay uno ya en pantalla)
                    if (!isSwalActive) {
                        isSwalActive = true;
                        alertSound.play().catch(e => console.log("Audio bloqueado por navegador"));

                        Swal.fire({
                            title: '¡ALERTA CRÍTICA!',
                            html: `<b style="font-size: 1.5rem; color: #f43f5e;">${data.latest.power} W</b><br>${data.ai.reason}`,
                            icon: 'error',
                            background: '#0f172a',
                            color: '#fff',
                            confirmButtonText: 'DETENER ALARMA',
                            confirmButtonColor: '#e11d48',
                            backdrop: `rgba(244, 63, 94, 0.4)`, // Fondo rojo traslúcido
                            allowOutsideClick: false,
                            showClass: {
                                popup: 'animate__animated animate__headShake' // Sacudida de pantalla
                            }
                        }).then(() => {
                            isSwalActive = false; // Se libera para que pueda volver a saltar
                        });
                    }
                } else {
                    // Volver a estado estable
                    aiCard.className = "md:col-span-2 p-6 rounded-3xl border border-slate-700/50 bg-[#1E293B]";
                    aiLabel.innerText = "✓ CONSUMO ESTABLE";
                    aiLabel.className = "text-2xl font-bold text-emerald-500";
                    document.title = "EnergyDash | Live Monitor";
                }

                // 3. Actualizar Gráfica
                energyChart.data.labels = data.readings.map(r => new Date(r.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}));
                energyChart.data.datasets[0].data = data.readings.map(r => r.power);
                energyChart.update('none');

            } catch (e) { 
                console.error("Error en el monitoreo:", e); 
            }
        }

        // Ejecutar cada 1 segundo para un monitoreo ultra rápido
        setInterval(updateDashboard, 1000);
        updateDashboard();
    </script>
</body>
</html>
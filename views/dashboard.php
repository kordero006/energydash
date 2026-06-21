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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        .swal2-popup.energy-alert { border: 1px solid rgba(244, 63, 94, 0.35); box-shadow: 0 24px 80px rgba(15, 23, 42, 0.7); }
        .emergency-map { height: 340px; border-radius: 16px; overflow: hidden; border: 1px solid rgba(148, 163, 184, 0.24); margin-top: 18px; }
        .tech-grid-alert { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-top: 14px; text-align: left; }
        .tech-card-alert { background: rgba(30, 41, 59, 0.9); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 14px; padding: 12px; }
        .tech-card-alert h4 { margin: 0 0 6px; color: #22d3ee; font-size: 0.92rem; font-weight: 900; }
        .tech-card-alert p { margin: 4px 0; color: #e2e8f0; font-size: 0.8rem; font-weight: 700; }
        .tech-actions-alert { display: flex; gap: 8px; margin-top: 10px; }
        .tech-actions-alert a { flex: 1; border-radius: 8px; color: #fff; font-size: 0.78rem; font-weight: 900; padding: 7px 8px; text-align: center; text-decoration: none; }
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
                                DISPOSITIVO 1
                            </a>
                            <a href="?device=esp32_casa_2&view=<?= $currentFilter ?>" 
                               class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($deviceId == 'esp32_casa_2') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'text-slate-100 hover:text-white' ?>">
                                DISPOSITIVO 2
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
    const TECH_SEARCH_RADIUS_M = 5000;
    const FALLBACK_LOCATION = { lat: 19.4326, lon: -99.1332, label: 'CDMX' };

    function getLaptopLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve({ ...FALLBACK_LOCATION, source: 'fallback' });
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => resolve({
                    lat: position.coords.latitude,
                    lon: position.coords.longitude,
                    label: 'Tu dispositivo',
                    source: 'browser'
                }),
                () => resolve({ ...FALLBACK_LOCATION, source: 'fallback' }),
                { enableHighAccuracy: true, timeout: 6000, maximumAge: 300000 }
            );
        });
    }

    function alertIcon(color) {
        return L.divIcon({
            html: `<div style="width:18px;height:18px;border-radius:999px;background:${color};border:3px solid #fff;box-shadow:0 0 14px ${color};"></div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            className: ''
        });
    }

    function distanceKm(lat1, lon1, lat2, lon2) {
        const toRad = (value) => value * Math.PI / 180;
        const earthKm = 6371;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return earthKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    async function fetchNearbyTechnicians(lat, lon) {
        const query = `
            [out:json][timeout:18];
            (
                node["craft"="electrician"](around:${TECH_SEARCH_RADIUS_M},${lat},${lon});
                node["office"="electrician"](around:${TECH_SEARCH_RADIUS_M},${lat},${lon});
                node["shop"="electronics"](around:${TECH_SEARCH_RADIUS_M},${lat},${lon});
                node["shop"="electrical"](around:${TECH_SEARCH_RADIUS_M},${lat},${lon});
            );
            out body;
        `;

        const response = await fetch('https://overpass-api.de/api/interpreter', {
            method: 'POST',
            body: query
        });

        if (!response.ok) throw new Error('No se pudo consultar OpenStreetMap.');
        const data = await response.json();
        return (data.elements || [])
            .filter((item) => Number.isFinite(item.lat) && Number.isFinite(item.lon))
            .map((item) => ({ ...item, km: distanceKm(lat, lon, item.lat, item.lon) }))
            .sort((a, b) => a.km - b.km)
            .slice(0, 6);
    }

    function renderTechnicianCards(technicians, originLat, originLon) {
        const list = document.getElementById('alert-tech-list');
        if (!list) return;

        if (!technicians.length) {
            const searchUrl = `https://www.google.com/maps/search/electricistas/@${originLat},${originLon},15z`;
            list.innerHTML = `
                <div class="tech-card-alert" style="grid-column:1/-1;">
                    <h4>Sin resultados automaticos</h4>
                    <p>Abre Google Maps para ver electricistas cerca de la ubicacion detectada.</p>
                    <div class="tech-actions-alert">
                        <a style="background:#3b82f6;" href="${searchUrl}" target="_blank" rel="noopener">Buscar en Maps</a>
                    </div>
                </div>
            `;
            return;
        }

        list.innerHTML = technicians.map((tech) => {
            const name = escapeHtml(tech.tags?.name || 'Tecnico electrico');
            const phone = escapeHtml(tech.tags?.phone || tech.tags?.['contact:phone'] || '');
            const gmaps = `https://www.google.com/maps?q=${tech.lat},${tech.lon}`;
            const osm = `https://www.openstreetmap.org/?mlat=${tech.lat}&mlon=${tech.lon}#map=17/${tech.lat}/${tech.lon}`;

            return `
                <div class="tech-card-alert">
                    <h4>${name}</h4>
                    <p>${tech.km.toFixed(2)} km</p>
                    ${phone ? `<p>${phone}</p>` : ''}
                    <div class="tech-actions-alert">
                        <a style="background:#3b82f6;" href="${gmaps}" target="_blank" rel="noopener">Google Maps</a>
                        <a style="background:#84cc16;" href="${osm}" target="_blank" rel="noopener">OpenStreetMap</a>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function renderEmergencyMap() {
        const status = document.getElementById('alert-location-status');
        const mapEl = document.getElementById('alert-map');
        if (!mapEl || typeof L === 'undefined') return;

        const location = await getLaptopLocation();
        if (status) {
            status.innerText = location.source === 'browser'
                ? 'Ubicacion tomada de esta laptop.'
                : 'No se concedio ubicacion; usando referencia CDMX.';
        }

        const map = L.map(mapEl, { zoomControl: true }).setView([location.lat, location.lon], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([location.lat, location.lon], { icon: alertIcon('#22d3ee') })
            .addTo(map)
            .bindPopup(`<strong>${location.label}</strong>`)
            .openPopup();

        setTimeout(() => map.invalidateSize(), 100);

        try {
            const technicians = await fetchNearbyTechnicians(location.lat, location.lon);
            renderTechnicianCards(technicians, location.lat, location.lon);

            technicians.forEach((tech) => {
                const name = escapeHtml(tech.tags?.name || 'Tecnico electrico');
                L.marker([tech.lat, tech.lon], { icon: alertIcon('#f59e0b') })
                    .addTo(map)
                    .bindPopup(`<strong>${name}</strong><br>${tech.km.toFixed(2)} km`);
            });
        } catch (error) {
            renderTechnicianCards([], location.lat, location.lon);
            if (status) status.innerText += ' Overpass no respondio; se dejo busqueda directa en Maps.';
        }
    }

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
                        html: `
                            <span style="font-size: 2rem; font-weight: 900; color: #f43f5e;">${powerNow} Watts</span>
                            <p style="color: #cbd5e1; margin: 8px 0 0;">${data.ai.reason}</p>
                            <p id="alert-location-status" style="color: #94a3b8; font-size: .82rem; font-weight: 800; margin: 10px 0 0;">Solicitando ubicacion de la laptop...</p>
                            <div id="alert-map" class="emergency-map"></div>
                            <h3 style="color: #22d3ee; text-align: left; margin: 18px 0 0; font-size: 1.05rem; font-weight: 900;">Tecnicos disponibles cerca</h3>
                            <div id="alert-tech-list" class="tech-grid-alert">
                                <div class="tech-card-alert" style="grid-column:1/-1;"><p>Buscando electricistas cercanos...</p></div>
                            </div>
                        `,
                        icon: 'error',
                        customClass: { popup: 'energy-alert' },
                        width: 980,
                        background: '#1e293b',
                        color: '#fff',
                        confirmButtonText: 'ENTENDIDO',
                        confirmButtonColor: '#e11d48',
                        didOpen: () => { renderEmergencyMap(); }
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

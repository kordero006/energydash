<?php
/**
 * EnergyDash - Script de Inyección de Anomalías (Test)
 * Propósito: Simular un pico de consumo para validar la respuesta de la IA.
 */

// 1. NUEVAS CREDENCIALES (Sincronizadas con el ESP32 y el Modelo)
$url = "https://voakbbekvscxyhafwwij.supabase.co/rest/v1/energy_readings";
$key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZvYWtiYmVrdnNjeHloYWZ3d2lqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzUxNjYwNzIsImV4cCI6MjA5MDc0MjA3Mn0.bWeImywvM9hrvEKYCVsFMbm7krJEWnlr85cHsNu8adY";

// 2. DATOS DE ANOMALÍA (Consumo Crítico)
$data = [
    "device_id" => "esp32_casa_1",
    "current"   => 35.50, 
    "power"     => 1200.0 // Supera el SAFETY_LIMIT de 1000W
];

// 3. INICIALIZACIÓN DE CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// 4. SOLUCIÓN PARA LOCALHOST Y SERVIDORES AWS SIN CERTIFICADOS ESTRICTOS
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// 5. ENCABEZADOS DE SUPABASE
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $key,
    'Authorization: Bearer ' . $key,
    'Content-Type: application/json',
    'Prefer: return=minimal'
]);

// 6. EJECUCIÓN
$response = curl_exec($ch);
$error = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Nota: En PHP 8.5 se omite curl_close() para evitar avisos de "Deprecated"

// 7. INTERFAZ DE RESULTADOS
echo "<!DOCTYPE html><html lang='es'><body style='background:#0f172a; color:#f8fafc; font-family:sans-serif; text-align:center; padding-top:50px;'>";

if ($status == 201 || $status == 204 || $status == 200) {
    echo "<div style='border: 2px solid #ef4444; display:inline-block; padding:30px; border-radius:20px; background:rgba(239, 68, 68, 0.1);'>";
    echo "<h1 style='color:#ef4444; margin-bottom:10px;'>🔥 ANOMALÍA INYECTADA CON ÉXITO</h1>";
    echo "<p style='font-size:1.2rem;'>Dato enviado: <b>{$data['power']} W</b> a la tabla 'energy_readings'.</p>";
    echo "<p style='color:#94a3b8; font-size:0.9rem;'>Status Code: $status</p>";
    echo "<br>";
    echo "<a href='index.php' style='background:#38bdf8; color:#fff; padding:12px 25px; text-decoration:none; border-radius:10px; font-weight:bold;'>VOLVER AL DASHBOARD</a>";
    echo "</div>";
} else {
    echo "<div style='border: 2px solid #f59e0b; display:inline-block; padding:30px; border-radius:20px; background:rgba(245, 158, 11, 0.1);'>";
    echo "<h1 style='color:#f59e0b;'>⚠️ ERROR EN LA INYECCIÓN</h1>";
    echo "<p><b>Status Code:</b> $status</p>";
    echo "<p><b>CURL Error:</b> $error</p>";
    echo "<pre style='text-align:left; background:#1e293b; padding:15px; border-radius:10px; color:#cbd5e1;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    echo "<br><a href='index.php' style='color:#38bdf8;'>Regresar al Dashboard</a>";
    echo "</div>";
}

echo "</body></html>";
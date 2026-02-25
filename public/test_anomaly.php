<?php
// public/test_anomaly.php
$url = "https://afvthfxrwmkkepzqvoua.supabase.co/rest/v1/readings";
$key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFmdnRoZnhyd21ra2VwenF2b3VhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzEyNzgzOTksImV4cCI6MjA4Njg1NDM5OX0.Aidqkv6AHDZ0-Oc0CQSrILfeL2JThEZMkh6mWYXJdHc";

// Datos de anomalía masiva
$data = [
    "device_id" => "esp32_casa_1",
    "current"   => 35.50, 
    "power"     => 1200.0 // Valor crítico
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// SOLUCIÓN QUIRÚRGICA PARA LOCALHOST:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $key,
    'Authorization: Bearer ' . $key,
    'Content-Type: application/json',
    'Prefer: return=minimal'
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status == 201 || $status == 200 || $status == 204) {
    echo "<h1 style='color:red; font-family:sans-serif;'>🔥 ANOMALÍA INYECTADA CON ÉXITO</h1>";
    echo "<p>Dato enviado: 4500W a la tabla 'readings'.</p>";
    echo "<a href='index.php?view=month'>Ir al Dashboard (Ver Mes)</a>";
} else {
    echo "<h1>Error en la inyección</h1>";
    echo "Status Code: " . $status . "<br>";
    echo "CURL Error: " . $error . "<br>";
    echo "Response: " . $response;
}
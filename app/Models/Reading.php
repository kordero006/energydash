<?php
namespace App\Models;

/**
 * Modelo Reading - Versión 2.0 (Soporte Multidispositivo)
 * Diseñado para filtrar lecturas por device_id y mantener la precisión de la IA.
 */
class Reading {
    // Configuración de Supabase
    private $url = "https://voakbbekvscxyhafwwij.supabase.co/rest/v1/energy_readings";
    private $key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZvYWtiYmVrdnNjeHloYWZ3d2lqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzUxNjYwNzIsImV4cCI6MjA5MDc0MjA3Mn0.bWeImywvM9hrvEKYCVsFMbm7krJEWnlr85cHsNu8adY";

    /**
     * Obtiene los datos filtrados por tiempo y por Identificador de Dispositivo.
     * 
     * @param string $filter 'today', 'week', 'month', 'all'
     * @param string $deviceId ID del dispositivo (ej. 'esp32_casa_1')
     * @param int $limit Cantidad de registros a traer
     * @param int $offset Para paginación
     */
    public function getData($filter = 'today', $deviceId = 'esp32_casa_1', $limit = 30, $offset = 0) {
        // Ajuste de zona horaria para compensar el servidor AWS (UTC vs CDMX)
        date_default_timezone_set('America/Mexico_City');

        $startDate = match($filter) {
            'week'  => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-d', strtotime('-30 days')),
            'all'   => '2020-01-01',
            // Mantenemos el -1 día por el desfase UTC de Supabase
            default => date('Y-m-d', strtotime('-1 day')), 
        };

        /**
         * CONSTRUCCIÓN DE QUERY MULTIDISPOSITIVO:
         * 1. Filtramos por device_id (eq = equal)
         * 2. Filtramos por fecha (gte = greater than or equal)
         * 3. Ordenamos por fecha descendente (más nuevo primero)
         */
        $queryUrl = $this->url . "?device_id=eq." . urlencode($deviceId) . 
                    "&created_at=gte." . $startDate . "T00:00:00" .
                    "&order=created_at.desc&limit=$limit&offset=$offset";

        $ch = curl_init($queryUrl);
        
        // Configuración segura para Amazon Linux y PHP 8.5
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);

        // Si falla la red, regresamos array vacío para que el Controller no truene
        if (curl_errno($ch)) {
            return [];
        }

        $data = json_decode($response, true);

        // Validación de Integridad (RLS y tipo de dato)
        if (is_array($data) && !isset($data['message'])) {
            return $data;
        }

        return [];
    }
}
<?php
namespace App\Models;

/**
 * Modelo Reading - Conexión con Supabase para EnergyDash
 * Versión: 1.2.0 (Sincronizada con tabla energy_readings)
 */
class Reading {
    // Configuración de conexión con las nuevas credenciales
    private $url = "https://voakbbekvscxyhafwwij.supabase.co/rest/v1/energy_readings";
    private $key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZvYWtiYmVrdnNjeHloYWZ3d2lqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzUxNjYwNzIsImV4cCI6MjA5MDc0MjA3Mn0.bWeImywvM9hrvEKYCVsFMbm7krJEWnlr85cHsNu8adY";

    /**
     * Obtiene los datos de consumo según el filtro seleccionado
     */
    public function getData($filter = 'today', $limit = 10, $offset = 0) {
        // Ajuste de zona horaria para cálculos de fecha en México
        date_default_timezone_set('America/Mexico_City');

        $startDate = match($filter) {
            'week'  => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-d', strtotime('-30 days')),
            'all'   => '2020-01-01',
            // FIX: Restamos un día para compensar el desfase UTC de Supabase
            default => date('Y-m-d', strtotime('-1 day')), 
        };

        // Construcción de la URL de consulta con orden descendente para el Dashboard Live
        $queryUrl = $this->url . "?created_at=gte." . $startDate . "T00:00:00&order=created_at.desc&limit=$limit&offset=$offset";

        $ch = curl_init($queryUrl);
        
        // Configuración de CURL optimizada para AWS y PHP 8.5
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita errores de certificados en local/EC2
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);

        // Manejo de errores de conexión de red
        if (curl_errno($ch)) {
            return [];
        }

        // Omitimos curl_close($ch) por compatibilidad con PHP 8.5

        $data = json_decode($response, true);

        /**
         * Validación de respuesta:
         * 1. Debe ser un array.
         * 2. No debe contener un mensaje de error de la API (como problemas de RLS).
         */
        if (is_array($data) && !isset($data['message'])) {
            return $data;
        }

        return [];
    }
}
<?php
namespace App\Models;

class Reading {
    private $url = "https://voakbbekvscxyhafwwij.supabase.co/rest/v1/energy_readings";
    private $key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZvYWtiYmVrdnNjeHloYWZ3d2lqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzUxNjYwNzIsImV4cCI6MjA5MDc0MjA3Mn0.bWeImywvM9hrvEKYCVsFMbm7krJEWnlr85cHsNu8adY";
    public function getData($filter = 'today', $limit = 10, $offset = 0) {
        $startDate = match($filter) {
            'week'  => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-d', strtotime('-30 days')),
            'all'   => '2020-01-01',
            default => date('Y-m-d'),
        };

        // Construcción de la URL de consulta
        $queryUrl = $this->url . "?created_at=gte." . $startDate . "T00:00:00&order=created_at.desc&limit=$limit&offset=$offset";

        $ch = curl_init($queryUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);

        // MANEJO DE ERRORES DE CONEXIÓN
        if (curl_errno($ch)) {
            // Si hay error de red (ej. timeout o DNS), devolvemos array vacío
            return [];
        }

        // En PHP 8.0+, los recursos de curl son objetos que se cierran solos.
        // En PHP 8.5, llamar a curl_close() lanza un aviso de "Deprecated".
        // Lo eliminamos para evitar el Error 500.

        $data = json_decode($response, true);

        // Si el JSON es inválido o Supabase devuelve error, aseguramos un array
        return is_array($data) ? $data : [];
    }
}
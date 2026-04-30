<?php
namespace App\Controllers;

use App\Models\Reading;
use App\Helpers\AnomalyDetector;

/**
 * DashboardController - Versión Multidispositivo
 * Gestiona la lógica de visualización y análisis de IA por dispositivo.
 */
class DashboardController {
    public function index() {
        // Aseguramos la zona horaria para consistencia en los registros manuales
        date_default_timezone_set('America/Mexico_City');

        // 1. CAPTURA DE PARÁMETROS DINÁMICOS
        // Obtenemos el ID del dispositivo (ej. esp32_casa_1) de la URL
        $deviceId = $_GET['device'] ?? 'esp32_casa_1';
        $filter = $_GET['view'] ?? 'today';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 30; // Aumentamos a 30 para mejor precisión de la IA
        $offset = ($page - 1) * $perPage;

        $model = new Reading();
        
        // 2. CARGA DE DATOS FILTRADOS POR DISPOSITIVO
        // Pasamos el $deviceId para que el Modelo segregue la información
        $rawReadings = $model->getData($filter, $deviceId, $perPage, $offset);
        if (!is_array($rawReadings)) { $rawReadings = []; }

        // 3. FALLBACK INTELIGENTE
        // Si no hay datos hoy para el dispositivo seleccionado, busca en el histórico general
        if (empty($rawReadings) && $filter == 'today') {
            $rawReadings = $model->getData('all', $deviceId, $perPage, $offset);
            if (!is_array($rawReadings)) { $rawReadings = []; }
        }

        // 4. ESTADO DE MONITOR (LATEST)
        // Obtenemos el registro más reciente de este dispositivo específico
        $latest = !empty($rawReadings) ? $rawReadings[0] : [
            'power' => 0, 
            'current' => 0, 
            'device_id' => $deviceId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 5. ANÁLISIS DE IA SEGREGADO
        // La IA ahora solo analiza el comportamiento contra el historial de ESTE dispositivo
        // Evita que el consumo de un motor afecte la media de una lámpara.
        if (!empty($rawReadings)) {
            $ai = AnomalyDetector::analyze($latest['power'], $rawReadings);
        } else {
            $ai = [
                'is_anomaly' => false, 
                'score' => 0, 
                'reason' => 'Esperando conexión...', 
                'mean' => 0
            ];
        }

        // 6. PREPARACIÓN DE LA VISTA
        $viewData = [
            'readings' => $rawReadings,
            'chartData' => array_reverse($rawReadings), // Para el efecto de scroll de derecha a izquierda
            'latest' => $latest,
            'ai' => $ai,
            'currentFilter' => $filter,
            'deviceId' => $deviceId, // Enviamos el ID para resaltar el botón activo en la UI
            'currentPage' => $page
        ];
        
        extract($viewData);

        // 7. RENDERIZADO SEGURO
        $viewPath = __DIR__ . '/../../views/dashboard.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            die("Error Crítico: La interfaz de usuario no se encuentra en $viewPath");
        }
    }
}
<?php
namespace App\Controllers;

use App\Models\Reading;
use App\Helpers\AnomalyDetector;

class DashboardController {
    public function index() {
        // 1. Captura de parámetros
        $filter = $_GET['view'] ?? 'today';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $model = new Reading();
        
        // 2. Intento de carga de datos con validación de tipo
        $rawReadings = $model->getData($filter, $perPage, $offset);
        if (!is_array($rawReadings)) { $rawReadings = []; }

        // 3. Fallback: Si 'today' está vacío, busca en 'all'
        if (empty($rawReadings) && $filter == 'today') {
            $rawReadings = $model->getData('all', $perPage, $offset);
            if (!is_array($rawReadings)) { $rawReadings = []; }
        }

        // 4. Estado inicial seguro (Evita el error de "Undefined index 0")
        $latest = !empty($rawReadings) ? $rawReadings[0] : [
            'power' => 0, 
            'current' => 0, 
            'device_id' => 'Offline',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 5. IA: Solo analizamos si hay datos para evitar división por cero en el Z-Score
        // Z = (x - μ) / σ
        if (!empty($rawReadings)) {
            $ai = AnomalyDetector::analyze($latest['power'], $rawReadings);
        } else {
            $ai = ['is_anomaly' => false, 'score' => 0, 'reason' => 'Sin datos', 'mean' => 0];
        }

        $viewData = [
            'readings' => $rawReadings,
            'chartData' => array_reverse($rawReadings),
            'latest' => $latest,
            'ai' => $ai,
            'currentFilter' => $filter,
            'currentPage' => $page
        ];
        
        extract($viewData);

        // 6. Verificación de ruta de vista
        $viewPath = __DIR__ . '/../../views/dashboard.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            die("Error Crítico: No se encontró la vista en $viewPath");
        }
    }
}
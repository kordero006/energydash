<?php
namespace App\Controllers;

use App\Models\Reading;
use App\Helpers\AnomalyDetector;

class DashboardController {
    public function index() {
        $filter = $_GET['view'] ?? 'today';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $model = new Reading();
        $rawReadings = $model->getData($filter, $perPage, $offset);

        // Si la vista es 'today' y no hay nada, intentamos 'all' para no ver el dashboard vacío
        if(empty($rawReadings) && $filter == 'today') {
            $rawReadings = $model->getData('all', $perPage, $offset);
        }

        $latest = !empty($rawReadings) ? $rawReadings[0] : ['power' => 0, 'current' => 0, 'device_id' => 'Offline'];
        $ai = AnomalyDetector::analyze($latest['power'], $rawReadings);

        $viewData = [
            'readings' => $rawReadings,
            'chartData' => array_reverse($rawReadings),
            'latest' => $latest,
            'ai' => $ai,
            'currentFilter' => $filter,
            'currentPage' => $page
        ];
        
        extract($viewData);
        include __DIR__ . '/../../views/dashboard.php';
    }
}
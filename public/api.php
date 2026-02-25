<?php
// public/api.php
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) require_once $path;
});

use App\Models\Reading;
use App\Helpers\AnomalyDetector;

$view = $_GET['view'] ?? 'today';
$model = new Reading();
$data = $model->getData($view, 20, 0);

if (empty($data)) $data = $model->getData('all', 20, 0);

$latest = $data[0] ?? null;
$ai = AnomalyDetector::analyze($latest['power'] ?? 0, $data);

header('Content-Type: application/json');
echo json_encode(['latest' => $latest, 'readings' => array_reverse($data), 'ai' => $ai]);
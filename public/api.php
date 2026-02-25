<?php
// 1. CARGA EL AUTOLOADER REAL DE COMPOSER
// Es mucho más seguro que el manual y ya sabe dónde está todo.
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Reading;
use App\Helpers\AnomalyDetector;

// 2. ACTIVAR ERRORES (Solo para debugear este Error 500)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. CAPTURA DE PARÁMETROS
$view = $_GET['view'] ?? 'today';

$model = new Reading();
$data = $model->getData($view, 20, 0);

// Aseguramos que $data sea un array para que no rompa el count() o el [0]
if (!is_array($data)) { $data = []; }

// 4. FALLBACK: Si no hay datos, buscar en 'all'
if (empty($data) && $view !== 'all') {
    $data = $model->getData('all', 20, 0);
    if (!is_array($data)) { $data = []; }
}

// 5. PREPARAR RESPUESTA
$latest = $data[0] ?? null;

// IA: Procesar anomalías (Z-Score)
// Usamos el blindaje que pusimos en el AnomalyDetector
$ai = AnomalyDetector::analyze($latest['power'] ?? 0, $data);

// 6. SALIDA JSON
header('Content-Type: application/json');
echo json_encode([
    'latest'   => $latest, 
    'readings' => array_reverse($data), 
    'ai'       => $ai
]);
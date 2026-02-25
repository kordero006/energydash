<?php
/**
 * EnergyDash - Punto de entrada principal
 * Configurado para PHP 8.5 en Amazon Linux
 */

// 1. CARGA EL AUTOLOADER DE COMPOSER (VITAL)
// Esto sustituye tu spl_autoload_register manual y garantiza que PSR-4 funcione.
require_once __DIR__ . '/../vendor/autoload.php';

// 2. ACTIVAR VISUALIZACIÓN DE ERRORES (Solo para esta fase final)
// Una vez que todo funcione, puedes cambiar 1 por 0.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Controllers\DashboardController;

try {
    // 3. INICIAR EL CONTROLADOR
    $controller = new DashboardController();
    $controller->index();
    
} catch (\Throwable $e) {
    // 4. CAPTURA DE ERRORES CRÍTICOS
    // Si algo falla, te lo dirá en pantalla en lugar de un Error 500 genérico.
    echo "<div style='background:#fee2e2; border:1px solid #ef4444; padding:20px; border-radius:10px; font-family:sans-serif;'>";
    echo "<h1 style='color:#991b1b; margin-top:0;'>Error del Sistema</h1>";
    echo "<p><b>Mensaje:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>Archivo:</b> " . $e->getFile() . " en línea " . $e->getLine() . "</p>";
    echo "</div>";
}
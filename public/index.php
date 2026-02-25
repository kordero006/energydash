<?php
// Autoloader manual (Simula a Composer mientras tanto)
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use App\Controllers\DashboardController;

// Iniciamos el controlador
$controller = new DashboardController();
$controller->index();
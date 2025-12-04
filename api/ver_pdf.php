<?php
/**
 * ver_pdf.php - Proxy para servir PDFs locales
 * Solo usado cuando los PDFs están en el servidor PHP local
 * Para PDFs en servidor LAN (HTTP), se usa redirección directa
 */

$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    http_response_code(500);
    die("Error: config.json no encontrado");
}

$config = json_decode(file_get_contents($configFile), true);

// Aceptar tanto 'file' como 'archivo' para compatibilidad
$archivo = basename($_GET['file'] ?? $_GET['archivo'] ?? '');
if (empty($archivo)) {
    http_response_code(400);
    die("Error: parámetro archivo requerido");
}

$rutaBase = $config['ruta_pdf'] ?? '../Pdf/';

// Si la ruta es HTTP, redirigir al servidor LAN
if (preg_match('/^https?:\/\//i', $rutaBase)) {
    $url = rtrim($rutaBase, '/') . '/' . $archivo;
    header('Location: ' . $url);
    exit;
}

// Ruta local
if (!file_exists($rutaBase) && file_exists(__DIR__ . '/' . $rutaBase)) {
    $rutaBase = __DIR__ . '/' . $rutaBase;
}
$rutaBase = rtrim($rutaBase, '/\\') . DIRECTORY_SEPARATOR;
$rutaCompleta = $rutaBase . $archivo;

if (!file_exists($rutaCompleta)) {
    http_response_code(404);
    die("PDF no encontrado: " . htmlspecialchars($archivo));
}

// Servir el archivo
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $archivo . '"');
header('Content-Length: ' . filesize($rutaCompleta));
header('Cache-Control: private, max-age=3600');

if (ob_get_length()) ob_clean();
flush();
readfile($rutaCompleta);
exit;
?>
<?php
/**
 * ver_pdf.php - Proxy seguro para visualización de planos
 * Sirve archivos locales con lista blanca estricta y protección contra path traversal.
 * Para rutas HTTP (LAN), redirige directamente.
 */
header('X-Content-Type-Options: nosniff');

$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    http_response_code(500);
    die(json_encode(['error' => 'config.json no encontrado']));
}

$config = json_decode((string)file_get_contents($configFile), true) ?: [];

// Aceptar tanto 'file' como 'archivo'
$archivoRaw = (string)($_GET['file'] ?? $_GET['archivo'] ?? '');
$archivo = basename($archivoRaw);

// Validación de caracteres en el nombre de archivo
if (empty($archivo) || !preg_match('/^[a-zA-Z0-9_\-\. ]+$/', $archivo)) {
    http_response_code(400);
    die("Error: Parámetro de archivo inválido");
}

$rutaBase = $config['ruta_pdf'] ?? '../Pdf/';

// Si la ruta es HTTP (servidor LAN intranet), redirigir
if (preg_match('/^https?:\/\//i', $rutaBase)) {
    $url = rtrim($rutaBase, '/') . '/' . rawurlencode($archivo);
    header('Location: ' . $url, true, 302);
    exit;
}

// Ruta local
if (!is_dir($rutaBase) && is_dir(__DIR__ . '/' . $rutaBase)) {
    $rutaBase = __DIR__ . '/' . $rutaBase;
}
$realBase = realpath($rutaBase);
if ($realBase === false) {
    http_response_code(500);
    die("Error: Carpeta base no configurada correctamente");
}

$rutaCompleta = realpath($realBase . DIRECTORY_SEPARATOR . $archivo);

// Verificación anti Path-Traversal
if ($rutaCompleta === false || strpos($rutaCompleta, $realBase) !== 0 || !is_file($rutaCompleta)) {
    http_response_code(404);
    die("Archivo no encontrado: " . htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8'));
}

// LISTA BLANCA ESTRICTA DE EXTENSIONES
$allowedExtensions = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png'
];

$ext = strtolower(pathinfo($rutaCompleta, PATHINFO_EXTENSION));
if (!isset($allowedExtensions[$ext])) {
    http_response_code(403);
    die("Tipo de archivo no permitido");
}

header('Content-Type: ' . $allowedExtensions[$ext]);
header('Content-Disposition: inline; filename="' . addslashes($archivo) . '"');
header('Content-Length: ' . filesize($rutaCompleta));
header('Cache-Control: private, max-age=3600');

if (ob_get_length()) ob_clean();
flush();
readfile($rutaCompleta);
exit;
?>
<?php
/**
 * sync_line.php - API para sincronización de Línea
 * Permite actualizar y leer el estado actual de una línea de producción.
 * Arquitectura: Atomic Write (Temp File + Rename) + Lock-Free Read
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

// Directorio para guardar estados
$dataDir = __DIR__ . '/lines';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

// Validación básica de inputs
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'get';
$line   = isset($_REQUEST['line']) ? intval($_REQUEST['line']) : 0;
$puesto = isset($_REQUEST['puesto']) ? intval($_REQUEST['puesto']) : 0;

if ($line <= 0 || $line > 50) {
    http_response_code(400);
    echo json_encode(['error' => true, 'msg' => 'ID de linea invalido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$filename = $dataDir . '/line_' . $line . '.json';

if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => true, 'msg' => 'Metodo no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ean = substr(trim($_POST['ean'] ?? ''), 0, 32);
    $desc = substr(trim(strip_tags($_POST['desc'] ?? '')), 0, 255);
    $pdf = substr(trim($_POST['pdf'] ?? ''), 0, 500);
    $timestamp = time();

    // Deduplicación: si es el mismo EAN notificado hace menos de 5 segundos, evitar sobreescritura innecesaria
    if (file_exists($filename) && !empty($ean)) {
        $existingRaw = @file_get_contents($filename);
        if ($existingRaw) {
            $existing = json_decode($existingRaw, true);
            if ($existing && isset($existing['ean']) && $existing['ean'] === $ean && ($timestamp - ($existing['timestamp'] ?? 0)) < 5) {
                echo json_encode(['success' => true, 'data' => $existing, 'deduplicated' => true], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    $data = [
        'line' => $line,
        'puesto_origen' => $puesto,
        'ean' => $ean,
        'desc' => $desc,
        'pdf' => $pdf,
        'timestamp' => $timestamp,
        'date' => date('Y-m-d H:i:s')
    ];

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => true, 'msg' => 'Error al serializar estado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ESCRITURA ATÓMICA: Escribir a un archivo temporal único y renombrar
    // rename() es atómico a nivel de filesystem POSIX (evita ventanas de archivo a 0 bytes).
    $tempFile = $dataDir . '/.tmp_' . $line . '_' . bin2hex(random_bytes(6)) . '.tmp';
    if (@file_put_contents($tempFile, $json, LOCK_EX) !== false) {
        if (@rename($tempFile, $filename)) {
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            @unlink($tempFile);
        }
    }

    http_response_code(500);
    echo json_encode(['error' => true, 'msg' => 'No se pudo persistir el estado'], JSON_UNESCAPED_UNICODE);
    exit;

} else {
    // OBTENER ESTADO ACTUAL (Lectura Lock-Free)
    if (file_exists($filename)) {
        $content = @file_get_contents($filename);
        if ($content !== false && $content !== '') {
            $data = json_decode($content, true);
            if (is_array($data)) {
                echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        echo json_encode(['success' => false, 'msg' => 'Estado temporalmente no disponible'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Sin datos'], JSON_UNESCAPED_UNICODE);
    }
}
?>

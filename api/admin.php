<?php
/**
 * admin.php - API de administración
 * Gestiona configuración, CSVs y autenticación
 */
session_start();
header('Content-Type: application/json');

$config_file = __DIR__ . '/config.json';

// Cargar config para obtener la contraseña
$config_data = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$PASSWORD = $config_data['admin_password'] ?? 'admin123'; // fallback por seguridad

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================================
// ACCIONES PÚBLICAS (sin autenticación)
// ============================================================================

if ($action === 'login') {
    $pass = $_POST['password'] ?? '';
    if ($pass === $PASSWORD) {
        $_SESSION['auth'] = true;
        echo json_encode(['status' => 'ok', 'success' => true]);
    } else {
        // Rate limiting básico
        sleep(1);
        echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'Contraseña incorrecta']);
    }
    exit;
}

if ($action === 'check_auth') {
    $logged = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
    echo json_encode(['auth' => $logged, 'logged_in' => $logged]);
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'ok', 'success' => true]);
    exit;
}

// ============================================================================
// ZONA PROTEGIDA
// ============================================================================

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// ============================================================================
// FUNCIONES HELPER
// ============================================================================

function loadConfig() {
    global $config_file;
    $defaults = [
        'ruta_pdf' => 'http://192.168.170.160/PDF-EXPGRIFERIA/',
        'ruta_csv' => '../csv/0codigos.csv',
        'timeout_segundos' => 10
    ];
    
    if (file_exists($config_file)) {
        $loaded = json_decode(file_get_contents($config_file), true);
        if ($loaded) return array_merge($defaults, $loaded);
    }
    return $defaults;
}

function saveConfig($data) {
    global $config_file;
    file_put_contents($config_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function readCSV($filename) {
    $path = $filename;
    if (strpos($path, '/') === false && strpos($path, '\\') === false) {
        $path = "../csv/" . $filename;
    }
    if (!file_exists($path) && file_exists(__DIR__ . '/' . $path)) {
        $path = __DIR__ . '/' . $path;
    }
    
    $rows = [];
    if (file_exists($path) && ($handle = fopen($path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    }
    return $rows;
}

function writeCSV($filename, $data) {
    $path = $filename;
    if (strpos($path, '/') === false && strpos($path, '\\') === false) {
        $path = __DIR__ . '/../csv/' . $filename;
    }

    if ($fp = fopen($path, 'w')) {
        foreach ($data as $fields) {
            fputcsv($fp, $fields, ";");
        }
        fclose($fp);
        return true;
    }
    return false;
}

function getCsvFiles() {
    $files = [];
    $csv_dir = __DIR__ . '/../csv/';
    if (is_dir($csv_dir)) {
        foreach (scandir($csv_dir) as $f) {
            if ($f !== '.' && $f !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'csv') {
                $files[] = $f;
            }
        }
    }
    sort($files);
    return $files;
}

// ============================================================================
// HANDLER DE ACCIONES
// ============================================================================

$config = loadConfig();
$target_csv = $_POST['target_csv'] ?? basename($config['ruta_csv']);

switch ($action) {
    case 'get_config':
        $csv_files = getCsvFiles();
        // Mapear para compatibilidad con admin.html
        echo json_encode([
            'config' => [
                'pdf_path' => $config['ruta_pdf'],
                'active_csv' => basename($config['ruta_csv']),
                'ruta_pdf' => $config['ruta_pdf'],
                'ruta_csv' => $config['ruta_csv'],
                'timeout_segundos' => $config['timeout_segundos']
            ],
            'csv_files' => $csv_files
        ]);
        break;

    case 'save_config':
        $new_config = [
            'ruta_pdf' => $_POST['pdf_path'] ?? $config['ruta_pdf'],
            'ruta_csv' => '../csv/' . ($_POST['active_csv'] ?? basename($config['ruta_csv'])),
            'timeout_segundos' => (int)($config['timeout_segundos'] ?? 10)
        ];
        saveConfig($new_config);
        echo json_encode(['status' => 'ok', 'success' => true, 'msg' => 'Configuración guardada']);
        break;

    case 'test_path':
        $path = $_POST['path'] ?? '';
        $type = $_POST['type'] ?? 'dir';
        
        // Si es HTTP, verificar conexión
        if (preg_match('/^https?:\/\//i', $path)) {
            $headers = @get_headers($path);
            $exists = $headers && strpos($headers[0], '200') !== false;
            echo json_encode([
                'success' => $exists,
                'msg' => $exists ? 'Servidor accesible' : 'No se puede acceder al servidor'
            ]);
        } else {
            // Ruta local
            $exists = ($type === 'file') ? file_exists($path) : is_dir($path);
            echo json_encode([
                'success' => $exists,
                'msg' => $exists ? 'Ruta válida' : 'Ruta no encontrada'
            ]);
        }
        break;

    case 'list_csvs':
        echo json_encode(['success' => true, 'files' => getCsvFiles()]);
        break;

    case 'get_csv_content':
        $filename = basename($_GET['filename'] ?? '');
        $path = __DIR__ . '/../csv/' . $filename;
        if (file_exists($path)) {
            echo json_encode(['success' => true, 'content' => file_get_contents($path)]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Archivo no encontrado']);
        }
        break;

    case 'save_csv_content':
        $filename = basename($_POST['filename'] ?? '');
        $content = $_POST['content'] ?? '';
        $path = __DIR__ . '/../csv/' . $filename;
        if (file_put_contents($path, $content) !== false) {
            echo json_encode(['success' => true, 'msg' => 'Guardado correctamente']);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Error al guardar']);
        }
        break;

    case 'get_data':
        echo json_encode(['data' => readCSV($target_csv), 'file' => $target_csv]);
        break;

    case 'update_row':
        $rowIndex = (int)$_POST['index'];
        $newData = $_POST['row_data']; 
        $allData = readCSV($target_csv);
        
        if (isset($allData[$rowIndex])) {
            $allData[$rowIndex] = $newData;
            writeCSV($target_csv, $allData);
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Fila no encontrada']);
        }
        break;

    case 'add_row':
        $allData = readCSV($target_csv);
        $allData[] = $_POST['row_data'];
        writeCSV($target_csv, $allData);
        echo json_encode(['status' => 'ok']);
        break;

    case 'add_row_top':
        $allData = readCSV($target_csv);
        if (count($allData) > 0) {
            array_splice($allData, 1, 0, [$_POST['row_data']]);
        } else {
            $allData[] = $_POST['row_data'];
        }
        writeCSV($target_csv, $allData);
        echo json_encode(['status' => 'ok']);
        break;

    case 'delete_row':
        $rowIndex = (int)$_POST['index'];
        $allData = readCSV($target_csv);
        if (isset($allData[$rowIndex])) {
            array_splice($allData, $rowIndex, 1);
            writeCSV($target_csv, $allData);
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        break;

    case 'upload_csv':
        if (isset($_FILES['file']) || isset($_FILES['archivo_csv'])) {
            $file = $_FILES['file'] ?? $_FILES['archivo_csv'];
            $target = __DIR__ . "/../csv/" . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target)) {
                echo json_encode(['status' => 'ok', 'success' => true, 'msg' => 'Archivo subido', 'filename' => basename($file['name'])]);
            } else {
                echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'Error al subir archivo']);
            }
        } else {
            echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'No se recibió archivo']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}
?>

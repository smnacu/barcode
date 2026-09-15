<?php
/**
 * admin.php - API de administración
 * Gestiona configuración, CSVs y autenticación
 */
session_start();
header('Content-Type: application/json');

$config_file = __DIR__ . '/config.json';
$faq_file = __DIR__ . '/../data/faq.json';

// Cargar config para obtener la contraseña
$config_data = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$PASSWORD = $config_data['admin_password'] ?? 'admin123'; // fallback por seguridad

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================================
// ACCIONES PÚBLICAS (sin autenticación)
// ============================================================================

if ($action === 'get_faq') {
    if (file_exists($faq_file)) {
        header('Content-Type: application/json');
        echo file_get_contents($faq_file);
    } else {
        echo json_encode([]);
    }
    exit;
}

if ($action === 'login') {
    $pass = $_POST['password'] ?? '';
    if ($pass === $PASSWORD) {
        session_regenerate_id(true);
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
        'timeout_segundos' => 10,
        'forzar_p1' => false,
        'forzar_sufijo' => '',
        'sufijo_puesto_1' => 'P1',
        'sufijo_puesto_2' => 'P2',
        'sufijo_puesto_3' => 'P3',
        'usar_sufijos_puesto' => false
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

// Helper para modificaciones atómicas con bloqueo
function modifyCSV($filename, callable $callback) {
    $path = $filename;
    if (strpos($path, '/') === false && strpos($path, '\\') === false) {
        $path = __DIR__ . '/../csv/' . $filename;
    }

    $fp = fopen($path, 'c+'); // Read/Write, no truncate yet
    if (!$fp) return false;

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    // Read
    $rows = [];
    while (($data = fgetcsv($fp, 0, ";")) !== FALSE) {
        $rows[] = $data;
    }

    // Callback changes data
    $newRows = $callback($rows);

    if ($newRows !== false) {
        // Rewind and write
        ftruncate($fp, 0);
        rewind($fp);
        foreach ($newRows as $fields) {
            fputcsv($fp, $fields, ";");
        }
    }

    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function writeCSV($filename, $data) {
    // Legacy support or specific overwrites (use modifyCSV for concurrent edits)
    $path = $filename;
    if (strpos($path, '/') === false && strpos($path, '\\') === false) {
        $path = __DIR__ . '/../csv/' . $filename;
    }
    if ($fp = fopen($path, 'c+')) {
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            foreach ($data as $fields) {
                fputcsv($fp, $fields, ";");
            }
            flock($fp, LOCK_UN);
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
        // Mapear para compatibilidad con admin.php
        echo json_encode([
            'config' => [
                'pdf_path' => $config['ruta_pdf'],
                'active_csv' => basename($config['ruta_csv']),
                'ruta_pdf' => $config['ruta_pdf'],
                'ruta_csv' => $config['ruta_csv'],
                'timeout_segundos' => $config['timeout_segundos'],
                'forzar_p1' => $config['forzar_p1'] ?? false,
                'forzar_sufijo' => $config['forzar_sufijo'] ?? '',
                'sufijo_puesto_1' => $config['sufijo_puesto_1'] ?? '',
                'sufijo_puesto_2' => $config['sufijo_puesto_2'] ?? 'P2',
                'sufijo_puesto_3' => $config['sufijo_puesto_3'] ?? 'P3',
                'usar_sufijos_puesto' => $config['usar_sufijos_puesto'] ?? false
            ],
            'csv_files' => $csv_files
        ]);
        break;

    case 'save_config':
        $new_config = [
            'ruta_pdf' => $_POST['pdf_path'] ?? $config['ruta_pdf'],
            'ruta_csv' => '../csv/' . ($_POST['active_csv'] ?? basename($config['ruta_csv'])),
            'timeout_segundos' => (int)($config['timeout_segundos'] ?? 10),
            'forzar_p1' => isset($_POST['forzar_p1']) && ($_POST['forzar_p1'] === '1' || $_POST['forzar_p1'] === 'true'),
            'forzar_sufijo' => $_POST['forzar_sufijo'] ?? '',
            'sufijo_puesto_1' => $_POST['sufijo_puesto_1'] ?? '',
            'sufijo_puesto_2' => $_POST['sufijo_puesto_2'] ?? 'P2',
            'sufijo_puesto_3' => $_POST['sufijo_puesto_3'] ?? 'P3',
            'usar_sufijos_puesto' => isset($_POST['usar_sufijos_puesto']) && ($_POST['usar_sufijos_puesto'] === '1' || $_POST['usar_sufijos_puesto'] === 'true')
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
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') {
            echo json_encode(['success' => false, 'msg' => 'Solo se permiten archivos CSV']);
            break;
        }
        $content = $_POST['content'] ?? '';
        $dir = __DIR__ . '/../csv';
        $path = $dir . '/' . $filename;
        $tmp = $dir . '/.tmp_' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) !== false && rename($tmp, $path)) {
            echo json_encode(['success' => true, 'msg' => 'Guardado correctamente']);
        } else {
            @unlink($tmp);
            echo json_encode(['success' => false, 'msg' => 'Error al guardar']);
        }
        break;

    case 'get_data':
        echo json_encode(['data' => readCSV($target_csv), 'file' => $target_csv]);
        break;

    case 'update_row':
        $rowIndex = (int)$_POST['index'];
        $newData = $_POST['row_data']; 
        
        $success = modifyCSV($target_csv, function($rows) use ($rowIndex, $newData) {
            if (isset($rows[$rowIndex])) {
                $rows[$rowIndex] = $newData;
                return $rows;
            }
            return false;
        });

        if ($success) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar (Fila no encontrada o archivo bloqueado)']);
        }
        break;

    case 'add_row':
        $success = modifyCSV($target_csv, function($rows) {
            $rows[] = $_POST['row_data'];
            return $rows;
        });
        echo json_encode(['status' => $success ? 'ok' : 'error']);
        break;

    case 'add_row_top':
        $success = modifyCSV($target_csv, function($rows) {
            if (count($rows) > 0) {
                array_splice($rows, 1, 0, [$_POST['row_data']]);
            } else {
                $rows[] = $_POST['row_data'];
            }
            return $rows;
        });
        echo json_encode(['status' => $success ? 'ok' : 'error']);
        break;

    case 'delete_row':
        $rowIndex = (int)$_POST['index'];
        $success = modifyCSV($target_csv, function($rows) use ($rowIndex) {
            if (isset($rows[$rowIndex])) {
                array_splice($rows, $rowIndex, 1);
                return $rows;
            }
            return false;
        });
        
        if ($success) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        break;

    case 'upload_csv':
        if (isset($_FILES['file']) || isset($_FILES['archivo_csv'])) {
            $file = $_FILES['file'] ?? $_FILES['archivo_csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'Solo se permiten archivos con extensión .csv']);
                break;
            }

            $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME)) . '.csv';
            $target = __DIR__ . "/../csv/" . $cleanName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                echo json_encode(['status' => 'ok', 'success' => true, 'msg' => 'Archivo subido', 'filename' => $cleanName]);
            } else {
                echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'Error al subir archivo']);
            }
        } else {
            echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'No se recibió archivo']);
        }
        break;

    case 'save_faq':
        $content = $_POST['content'] ?? '[]';
        $decoded = json_decode($content);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['status' => 'error', 'msg' => 'Formato JSON inválido']);
            break;
        }
        if (file_put_contents($faq_file, $content, LOCK_EX) !== false) {
            echo json_encode(['status' => 'ok', 'success' => true]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error writing file']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}
?>

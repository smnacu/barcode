<?php
/**
 * buscar.php - API de búsqueda de productos
 * Busca en CSV y retorna información del producto + ruta PDF
 */
header('Content-Type: application/json; charset=utf-8');

// Cargar configuración
$configFile = __DIR__ . '/config.json';
$config = [
    'ruta_csv' => '../csv/Libro.csv',
    'ruta_pdf' => '../Pdf/'
];

if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if ($loaded) $config = array_merge($config, $loaded);
}

/**
 * Limpia strings de caracteres especiales y BOM
 */
function limpiarString($texto) {
    if ($texto === null) return null;
    $texto = trim($texto);
    $texto = str_replace(["\xC2\xA0", "\xA0"], '', $texto);
    $texto = preg_replace('/^\x{FEFF}/u', '', $texto);
    return $texto;
}

/**
 * Codifica array a JSON de forma segura (compatibilidad PHP antiguo)
 */
function simpleJsonEncode($data) {
    if (is_array($data)) {
        $parts = [];
        $isList = array_keys($data) === range(0, count($data) - 1);
        foreach ($data as $key => $value) {
            $part = $isList ? '' : '"' . addslashes($key) . '":';
            if (is_array($value)) $part .= simpleJsonEncode($value);
            elseif (is_bool($value)) $part .= ($value ? 'true' : 'false');
            elseif (is_numeric($value) && !is_string($value)) $part .= $value;
            elseif ($value === null) $part .= 'null';
            else $part .= '"' . addslashes((string)$value) . '"';
            $parts[] = $part;
        }
        return $isList ? '[' . implode(',', $parts) . ']' : '{' . implode(',', $parts) . '}';
    }
    return '"' . addslashes((string)$data) . '"';
}

/**
 * Busca producto en archivo CSV
 */
function buscarEnLibro($codigo, $config, $modo = 'unico') {
    $csv_path = $config['ruta_csv'];
    
    // Intentar diferentes rutas para encontrar el CSV
    if (!file_exists($csv_path)) {
        if (file_exists(__DIR__ . '/' . $csv_path)) {
            $csv_path = __DIR__ . '/' . $csv_path;
        } elseif (file_exists(__DIR__ . '/../csv/' . basename($csv_path))) {
            $csv_path = __DIR__ . '/../csv/' . basename($csv_path);
        }
    }

    if (!file_exists($csv_path)) return ['encontrado' => false, 'resultados' => []];

    $handle = fopen($csv_path, 'r');
    if (!$handle) return ['encontrado' => false, 'resultados' => []];

    fgetcsv($handle); // Skip header
    
    $codigo = mb_strtolower($codigo, 'UTF-8');
    $resultados = [];

    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
        if (count($data) >= 3) {
            $codArt = limpiarString($data[0]);
            $descripcion = limpiarString($data[1]);
            $ean = limpiarString($data[2]);
            
            $codArtNorm = mb_strtolower($codArt, 'UTF-8');
            $eanNorm = mb_strtolower($ean, 'UTF-8');
            $descNorm = mb_strtolower($descripcion, 'UTF-8');

            // Búsqueda flexible en cualquier campo
            if (strpos($codArtNorm, $codigo) !== false || 
                strpos($eanNorm, $codigo) !== false || 
                strpos($descNorm, $codigo) !== false) {
                
                $item = ['codigo' => $codArt, 'descripcion' => $descripcion, 'ean' => $ean];
                
                if ($modo === 'unico') {
                    // Priorizar coincidencia exacta
                    if ($codArtNorm === $codigo || $eanNorm === $codigo) {
                        fclose($handle);
                        return [
                            'encontrado' => true,
                            'producto' => $item,
                            'fuente' => basename($csv_path)
                        ];
                    }
                    if (empty($resultados)) $resultados[] = $item;
                } else {
                    $resultados[] = $item;
                    if (count($resultados) >= 50) break;
                }
            }
        }
    }
    fclose($handle);

    if ($modo === 'unico') {
        if (!empty($resultados)) {
            return [
                'encontrado' => true,
                'producto' => $resultados[0],
                'fuente' => basename($csv_path)
            ];
        }
        return ['encontrado' => false];
    } else {
        return [
            'encontrado' => count($resultados) > 0,
            'resultados' => $resultados
        ];
    }
}

/**
 * Busca el archivo PDF correspondiente al producto
 */
function buscarPDF($producto, $config) {
    $ruta_base = $config['ruta_pdf'];
    $codigo = $producto['codigo'];
    
    // Si la ruta es HTTP, construir URL directa
    if (preg_match('/^https?:\/\//i', $ruta_base)) {
        $ruta_base = rtrim($ruta_base, '/') . '/';
        // Intentar diferentes extensiones y formatos de nombre
        $variantes = [
            $codigo . '.pdf',
            strtoupper($codigo) . '.pdf',
            strtolower($codigo) . '.pdf'
        ];
        // Para URLs HTTP, retornamos la primera variante
        // El servidor LAN manejará si existe o no
        return $ruta_base . $codigo . '.pdf';
    }
    
    // Si es ruta local
    if (!file_exists($ruta_base) && file_exists(__DIR__ . '/' . $ruta_base)) {
        $ruta_base = __DIR__ . '/' . $ruta_base;
    }
    $ruta_base = rtrim($ruta_base, '/\\') . DIRECTORY_SEPARATOR;
    
    // Buscar archivo con diferentes variantes
    $variantes = [
        $codigo . '.pdf',
        strtoupper($codigo) . '.pdf',
        strtolower($codigo) . '.pdf'
    ];
    
    foreach ($variantes as $nombre) {
        if (file_exists($ruta_base . $nombre)) {
            return $nombre;
        }
    }
    
    return null;
}

// ============================================================================
// PUNTO DE ENTRADA PRINCIPAL
// ============================================================================

$codigo = $_GET['codigo'] ?? $_POST['codigo'] ?? null;
$modo = $_POST['modo'] ?? 'unico';

if (empty($codigo)) {
    http_response_code(400);
    echo simpleJsonEncode(['error' => true, 'mensaje' => 'Codigo requerido']);
    exit;
}

$res = buscarEnLibro($codigo, $config, $modo);

if ($modo === 'lista') {
    echo simpleJsonEncode([
        'error' => false,
        'encontrado' => $res['encontrado'],
        'resultados' => $res['resultados'] ?? [],
        'count' => count($res['resultados'] ?? [])
    ]);
} else {
    if ($res['encontrado']) {
        $pdf = buscarPDF($res['producto'], $config);
        
        $pdf_url = null;
        $pdf_available = false;
        
        if ($pdf) {
            $pdf_available = true;
            // Si la ruta PDF es HTTP (servidor LAN), usar directamente
            if (preg_match('/^https?:\/\//i', $pdf)) {
                $pdf_url = $pdf;
            } else {
                // Ruta local, usar ver_pdf.php
                $pdf_url = 'api/ver_pdf.php?file=' . urlencode($pdf);
            }
        }

        echo simpleJsonEncode([
            'error' => false,
            'encontrado' => true,
            'producto' => $res['producto'],
            'pdf' => $pdf,
            'pdf_available' => $pdf_available,
            'pdf_url' => $pdf_url,
            'fuente' => $res['fuente']
        ]);
    } else {
        echo simpleJsonEncode([
            'error' => false,
            'encontrado' => false,
            'mensaje' => 'Producto no encontrado',
            'codigo_buscado' => $codigo
        ]);
    }
}
?>
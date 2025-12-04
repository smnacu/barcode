<?php
header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/config.json';
$config = [
    'ruta_csv' => '../csv/Libro.csv',
    'ruta_pdf' => '../Pdf/'
];

if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if ($loaded) $config = array_merge($config, $loaded);
}

function limpiarString($texto) {
    if ($texto === null) return null;
    $texto = trim($texto);
    $texto = str_replace(["\xC2\xA0", "\xA0"], '', $texto);
    $texto = preg_replace('/^\x{FEFF}/u', '', $texto);
    return $texto;
}

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
<?php
header('Content-Type: application/json; charset=utf-8');

$configFile = __DIR__ . '/config.json';
$config = [
    'ruta_csv' => '../csv/Libro.csv',
    'ruta_pdf' => '../Pdf/'
];

if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if ($loaded) $config = array_merge($config, $loaded);
}

function limpiarString($texto) {
    if ($texto === null) return null;
    $texto = trim($texto);
    $texto = str_replace(["\xC2\xA0", "\xA0"], '', $texto);
    $texto = preg_replace('/^\x{FEFF}/u', '', $texto);
    return $texto;
}

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

function buscarEnLibro($codigo, $config, $modo = 'unico') {
    $csv_path = $config['ruta_csv'];
    
    // 1. Try exact path
    if (!file_exists($csv_path)) {
        // 2. Try relative to __DIR__
        if (file_exists(__DIR__ . '/' . $csv_path)) {
            $csv_path = __DIR__ . '/' . $csv_path;
        }
        // 3. Try in ../csv/ folder if it's just a filename
        elseif (file_exists(__DIR__ . '/../csv/' . basename($csv_path))) {
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

            // Búsqueda flexible: Coincidencia parcial en cualquiera de los 3 campos
            if (strpos($codArtNorm, $codigo) !== false || 
                strpos($eanNorm, $codigo) !== false || 
                strpos($descNorm, $codigo) !== false) {
                
                $item = ['codigo' => $codArt, 'descripcion' => $descripcion, 'ean' => $ean];
                
                if ($modo === 'unico') {
                    // Comportamiento original: Retorna el primero exacto o el primero parcial si no hay exacto
                    // Priorizar coincidencia exacta de EAN o Código
                    if ($codArtNorm === $codigo || $eanNorm === $codigo) {
                        fclose($handle);
                        return [
                            'encontrado' => true,
                            'producto' => $item,
                            'fuente' => basename($csv_path)
                        ];
                    }
                    // Si es parcial, lo guardamos como candidato pero seguimos buscando un exacto
                    if (empty($resultados)) $resultados[] = $item;
                } else {
                    // Modo lista: Acumular todos
                    $resultados[] = $item;
                    if (count($resultados) >= 50) break; // Límite de seguridad
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
    http_response_code(400);
    echo simpleJsonEncode(['error' => true, 'mensaje' => 'Codigo requerido']);
    exit;
}

$res = buscarEnLibro($codigo, $config, $modo);

if ($modo === 'lista') {
    // En modo lista, retornamos array de resultados sin buscar PDF individualmente aún
    echo simpleJsonEncode([
        'error' => false,
        'encontrado' => $res['encontrado'],
        'resultados' => $res['resultados'] ?? [],
        'count' => count($res['resultados'] ?? [])
    ]);
} else {
    // Modo único (original)
    if ($res['encontrado']) {
        $pdf = buscarPDF($res['producto'], $config);
        
        $pdf_url = null;
        $pdf_available = false;
        
        if ($pdf) {
            $pdf_available = true;
            if (preg_match('/^https?:\/\//i', $pdf)) {
                $pdf_url = $pdf;
            } else {
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
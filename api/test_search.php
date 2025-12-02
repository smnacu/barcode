<?php
// Mock request
$_REQUEST['codigo'] = 'test';

// Mock config
$config = [
    'ruta_csv' => '../csv/Libro.csv',
    'ruta_pdf' => 'http://192.168.170.160/PDF-EXPGRIFERIA/'
];

// Mock functions from buscar.php (copy-paste for testing isolation or include if possible)
// Since buscar.php executes code at the bottom, we can't easily include it without side effects.
// I will copy the functions here to test them in isolation.

function limpiarString($texto) {
    if ($texto === null) return null;
    $texto = trim($texto);
    $texto = str_replace(["\xC2\xA0", "\xA0"], '', $texto);
    $texto = preg_replace('/^\x{FEFF}/u', '', $texto);
    return $texto;
}

function buscarEnLibro($codigo, $config) {
    // Mock CSV content creation
    $csvContent = "Codigo,Descripcion,EAN\nC001,Producto Test,123456\nC002,Otro Producto,789012";
    $csvFile = __DIR__ . '/test.csv';
    file_put_contents($csvFile, $csvContent);
    
    $config['ruta_csv'] = 'test.csv'; // Override for test

    $csv_path = $config['ruta_csv'];
    if (!file_exists($csv_path) && file_exists(__DIR__ . '/' . $csv_path)) {
        $csv_path = __DIR__ . '/' . $csv_path;
    }

    if (!file_exists($csv_path)) return ['encontrado' => false];

    $handle = fopen($csv_path, 'r');
    if (!$handle) return ['encontrado' => false];

    fgetcsv($handle);
    
    $codigo = mb_strtolower($codigo, 'UTF-8');

    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        if (count($data) >= 3) {
            $codArt = limpiarString($data[0]);
            $descripcion = limpiarString($data[1]);
            $ean = limpiarString($data[2]);
            
            $codArtNorm = mb_strtolower($codArt, 'UTF-8');
            $eanNorm = mb_strtolower($ean, 'UTF-8');
            $descNorm = mb_strtolower($descripcion, 'UTF-8');

            if ($codArtNorm === $codigo || $eanNorm === $codigo || strpos($descNorm, $codigo) !== false) {
                fclose($handle);
                unlink($csvFile); // Cleanup
                return [
                    'encontrado' => true,
                    'producto' => ['codigo' => $codArt, 'descripcion' => $descripcion, 'ean' => $ean],
                    'fuente' => basename($csv_path)
                ];
            }
        }
    }
    fclose($handle);
    unlink($csvFile); // Cleanup
    return ['encontrado' => false];
}

function buscarPDF($producto, $config) {
    $pdf_path_config = $config['ruta_pdf'];
    
    // Caso 1: URL HTTP/HTTPS
    if (preg_match('/^https?:\/\//i', $pdf_path_config)) {
        $pdf_path_config = rtrim($pdf_path_config, '/') . '/';
        
        $candidates = [];
        if (!empty($producto['ean'])) $candidates[] = $producto['ean'];
        if (!empty($producto['codigo'])) $candidates[] = $producto['codigo'];
        
        foreach ($candidates as $code) {
            $url = $pdf_path_config . $code . '.pdf';
            // Mock HEAD request for testing
            // In real scenario we use get_headers. Here we just return the URL to verify logic construction.
            return $url; 
        }
        return null;
    }
    return null;
}

// Test 1: Search by Description
echo "Test 1: Search 'Producto' (Description match)\n";
$res = buscarEnLibro('Producto', $config);
print_r($res);

// Test 2: Search by Code
echo "\nTest 2: Search 'C002' (Code match)\n";
$res = buscarEnLibro('C002', $config);
print_r($res);

// Test 3: PDF URL Construction
echo "\nTest 3: PDF URL for C001\n";
$pdf = buscarPDF(['codigo' => 'C001', 'ean' => '123456'], $config);
echo "PDF URL: " . $pdf . "\n";

?>

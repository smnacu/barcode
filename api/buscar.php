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
function limpiarString($texto)
{
    if ($texto === null) return null;
    $texto = trim($texto);
    $texto = str_replace(["\xC2\xA0", "\xA0"], '', $texto);
    $texto = preg_replace('/^\x{FEFF}/u', '', $texto);
    return $texto;
}

/**
 * Verifica si una URL HTTP existe (código 200)
 */
function url_exists($url)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    } else {
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Busca producto en archivo CSV
 */
function buscarEnLibro($codigo, $config, $modo = 'unico')
{
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

    fgetcsv($handle, 0, ';'); // Skip header con el mismo delimitador

    $codigo = mb_strtolower(trim($codigo), 'UTF-8');
    $resultados = [];

    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        if (count($data) >= 3) {
            $codArt = limpiarString($data[0]);
            $descripcion = limpiarString($data[1]);
            $ean = limpiarString($data[2]);

            $codArtNorm = mb_strtolower($codArt, 'UTF-8');
            $eanNorm = mb_strtolower($ean, 'UTF-8');
            $descNorm = mb_strtolower($descripcion, 'UTF-8');

            // Búsqueda flexible en cualquier campo
            if (
                strpos($codArtNorm, $codigo) !== false ||
                strpos($eanNorm, $codigo) !== false ||
                strpos($descNorm, $codigo) !== false
            ) {

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
                    // OPTIMIZATION: If we found a partial match in 'unico' mode, we could stop if strictness wasn't required,
                    // but the logic above allows finding exact match later. However, we can break if we found exact match (handled above).
                    // If we just want any match, we continue.
                    // But if we found *some* match and we are at the end, fine.
                    // Wait, the original logic didn't break.
                    // We will break if we have enough results in list mode, or if found exact in unico.
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

function buscarPDF($producto, $config)
{
    $ruta_base = $config['ruta_pdf'];
    $codigo = $producto['codigo'];
    $puesto = $config['puesto'] ?? 1;

    // Resolver sufijo para este puesto según la configuración
    $sufijo_puesto = null;
    $usar_sufijos = $config['usar_sufijos_puesto'] ?? false;

    if ($usar_sufijos) {
        if (isset($config['sufijo_puesto_' . $puesto])) {
            $sufijo_puesto = $config['sufijo_puesto_' . $puesto];
        } else {
            // Fallback retrocompatible
            $forzar_sufijo = $config['forzar_sufijo'] ?? '';
            if (empty($forzar_sufijo) && !empty($config['forzar_p1'])) {
                $forzar_sufijo = 'P1';
            }
            
            if (!empty($forzar_sufijo)) {
                $sufijo_puesto = $forzar_sufijo;
            } else {
                // Comportamiento normal por defecto
                $sufijo_puesto = ($puesto > 1) ? 'P' . $puesto : '';
            }
        }
    } else {
        // Por defecto, sin sufijos en ningún puesto
        $sufijo_puesto = '';
    }

    // Si la ruta es HTTP, retornar URLs de imagen y PDF directamente sin verificar existencia vía cURL,
    // ya que el servidor PHP local podría no tener acceso al servidor de imágenes que sí ve la tablet cliente.
    if (preg_match('/^https?:\/\//i', $ruta_base)) {
        $ruta_base = rtrim($ruta_base, '/') . '/';
        $sufijo = !empty($sufijo_puesto) ? '_' . $sufijo_puesto : '';
        $nombre_base = strtoupper($codigo) . $sufijo;

        return [
            'http' => true,
            'is_img' => true, // Intentar primero como imagen en el frontend
            'url' => $ruta_base . $nombre_base . '.jpg',
            'pdf_url' => $ruta_base . $nombre_base . '.pdf',
            'nombre' => $nombre_base . '.jpg'
        ];
    }

    // Si es ruta local
    if (!file_exists($ruta_base) && file_exists(__DIR__ . '/' . $ruta_base)) {
        $ruta_base = __DIR__ . '/' . $ruta_base;
    }
    $ruta_base = rtrim($ruta_base, '/\\') . DIRECTORY_SEPARATOR;

    // Buscar archivo con diferentes variantes
    $variantes = [];

    // 1. Específico del Puesto (priorizamos imagenes, luego pdf)
    if (!empty($sufijo_puesto)) {
        $variantes[] = $codigo . '_' . $sufijo_puesto . '.jpg';
        $variantes[] = strtoupper($codigo) . '_' . $sufijo_puesto . '.jpg';
        $variantes[] = $codigo . '_' . $sufijo_puesto . '.pdf';
        $variantes[] = strtoupper($codigo) . '_' . $sufijo_puesto . '.pdf';
    }

    // 2. Genérico
    $variantes[] = $codigo . '.jpg';
    $variantes[] = strtoupper($codigo) . '.jpg';
    $variantes[] = strtolower($codigo) . '.jpg';
    $variantes[] = $codigo . '.pdf';
    $variantes[] = strtoupper($codigo) . '.pdf';
    $variantes[] = strtolower($codigo) . '.pdf';

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
$puesto = $_GET['puesto'] ?? $_POST['puesto'] ?? 1;
$config['puesto'] = $puesto;

if (empty($codigo)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'mensaje' => 'Codigo requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$res = buscarEnLibro($codigo, $config, $modo);

if ($modo === 'lista') {
    echo json_encode([
        'error' => false,
        'encontrado' => $res['encontrado'],
        'resultados' => $res['resultados'] ?? [],
        'count' => count($res['resultados'] ?? [])
    ], JSON_UNESCAPED_UNICODE);
} else {
    if ($res['encontrado']) {
        $pdf = buscarPDF($res['producto'], $config);

        $pdf_url = null;
        $img_url = null;
        $pdf_available = false;

        if ($pdf) {
            $pdf_available = true;
            if (is_array($pdf) && isset($pdf['http'])) {
                if ($pdf['is_img']) {
                    $img_url = $pdf['url'];
                    if (isset($pdf['pdf_url'])) {
                        $pdf_url = $pdf['pdf_url'];
                    }
                } else {
                    $pdf_url = $pdf['url'];
                }
            } else {
                // Ruta local
                if (strtolower(pathinfo($pdf, PATHINFO_EXTENSION)) === 'jpg' || strtolower(pathinfo($pdf, PATHINFO_EXTENSION)) === 'png') {
                    $img_url = 'api/ver_pdf.php?file=' . urlencode($pdf);
                } else {
                    $pdf_url = 'api/ver_pdf.php?file=' . urlencode($pdf);
                }
            }
        }

        echo json_encode([
            'error' => false,
            'encontrado' => true,
            'producto' => $res['producto'],
            'pdf' => is_array($pdf) ? $pdf['nombre'] : $pdf,
            'pdf_available' => $pdf_available,
            'pdf_url' => $pdf_url,
            'img_url' => $img_url,
            'fuente' => $res['fuente']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'error' => false,
            'encontrado' => false,
            'mensaje' => 'Producto no encontrado',
            'codigo_buscado' => $codigo
        ], JSON_UNESCAPED_UNICODE);
    }
}

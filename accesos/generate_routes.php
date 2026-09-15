<?php
// Generador de rutas de acceso directo
// Ejecutar una vez para crear todos los accesos

$lines = 4;
$puestos = 3;
$dir = __DIR__;

if (!is_dir($dir)) mkdir($dir);

for ($l = 1; $l <= $lines; $l++) {
    for ($p = 1; $p <= $puestos; $p++) {
        $filename = "linea{$l}_puesto{$p}.php";
        $role = ($p == 1) ? "Scanner" : "Visor PDF";
        
        $content = "<?php\n";
        $content .= "// Acceso directo para Linea $l - Puesto $p ($role)\n";
        $content .= "header(\"Location: ../index.php?line=$l&puesto=$p\");\n";
        $content .= "exit;\n";
        
        file_put_contents($dir . "/" . $filename, $content);
        echo "Creado: $filename\n";
    }
}
echo "Generacion completa de rutas.\n";
?>

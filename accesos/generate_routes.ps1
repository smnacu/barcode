$lines = 4
$puestos = 3
$dir = $PSScriptRoot

Write-Host "Generando rutas en: $dir"

for ($l = 1; $l -le $lines; $l++) {
    for ($p = 1; $p -le $puestos; $p++) {
        $filename = "linea${l}_puesto${p}.php"
        if ($p -eq 1) { $role = "Scanner" } else { $role = "Visor PDF" }
        
        $content = "<?php`r`n"
        $content += "// Acceso directo para Linea $l - Puesto $p ($role)`r`n"
        $content += "header(`"Location: ../index.php?line=$l&puesto=$p`");`r`n"
        $content += "exit;`r`n"
        $content += "?>"
        
        $path = Join-Path $dir $filename
        Set-Content -Path $path -Value $content -Encoding UTF8
        Write-Host "Creado: $filename"
    }
}
Write-Host "Generacion completada."

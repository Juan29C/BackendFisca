#!/usr/bin/env php
<?php

/**
 * Script de prueba para verificar el sistema de almacenamiento globalizado
 * 
 * Uso:
 *   php test-storage.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "\n" . str_repeat('=', 60) . "\n";
echo "  PRUEBA DE SISTEMA DE ALMACENAMIENTO GLOBALIZADO\n";
echo str_repeat('=', 60) . "\n\n";

// 1. Verificar configuración actual
$disk = config('filesystems.default');
echo "✓ Disco configurado en .env: \033[1;32m{$disk}\033[0m\n";

// 2. Obtener ruta física del disco
$path = Storage::disk($disk)->path('');
echo "✓ Ruta física del disco: \033[1;36m{$path}\033[0m\n\n";

// 3. Crear archivo de prueba
echo "→ Creando archivo de prueba...\n";
$testFile = 'test_storage_' . time() . '.txt';
$testContent = "Prueba de almacenamiento - " . now()->toDateTimeString();

try {
    Storage::disk($disk)->put($testFile, $testContent);
    echo "✓ Archivo creado: \033[1;32m{$testFile}\033[0m\n";
    
    // 4. Verificar que existe
    if (Storage::disk($disk)->exists($testFile)) {
        echo "✓ Archivo verificado correctamente\n";
        
        // 5. Leer contenido
        $content = Storage::disk($disk)->get($testFile);
        echo "✓ Contenido leído: \033[1;33m{$content}\033[0m\n";
        
        // 6. Obtener ruta completa
        $fullPath = Storage::disk($disk)->path($testFile);
        echo "✓ Ubicación física: \033[1;36m{$fullPath}\033[0m\n";
        
        // 7. Generar URL pública
        $url = asset('storage/' . $testFile);
        echo "✓ URL pública: \033[1;34m{$url}\033[0m\n";
        
        // 8. Eliminar archivo de prueba
        Storage::disk($disk)->delete($testFile);
        echo "✓ Archivo de prueba eliminado\n";
        
        echo "\n\033[1;32m✓ TODAS LAS PRUEBAS PASARON CORRECTAMENTE\033[0m\n\n";
        
        // 9. Instrucciones para cambiar de disco
        echo str_repeat('-', 60) . "\n";
        echo "📝 Para cambiar el disco de almacenamiento:\n\n";
        echo "1. Edita \033[1;33m.env\033[0m y cambia:\n";
        echo "   \033[1;36mFILESYSTEM_DISK=public\033[0m   (desarrollo con symlink)\n";
        echo "   \033[1;36mFILESYSTEM_DISK=uploads\033[0m  (producción sin symlink)\n";
        echo "   \033[1;36mFILESYSTEM_DISK=s3\033[0m       (cloud storage)\n\n";
        echo "2. Limpia cache:\n";
        echo "   \033[1;32mphp artisan config:clear\033[0m\n\n";
        echo "3. Mueve archivos existentes (si es necesario):\n";
        if ($disk === 'public') {
            echo "   \033[1;32mMove-Item -Path .\\storage\\app\\public\\* -Destination .\\public\\storage\\ -Force\033[0m\n";
        } else {
            echo "   \033[1;32mMove-Item -Path .\\public\\storage\\* -Destination .\\storage\\app\\public\\ -Force\033[0m\n";
        }
        echo "\n" . str_repeat('-', 60) . "\n\n";
        
        exit(0);
    } else {
        throw new Exception("El archivo no se encontró después de crearlo");
    }
    
} catch (Exception $e) {
    echo "\n\033[1;31m✗ ERROR: " . $e->getMessage() . "\033[0m\n\n";
    exit(1);
}

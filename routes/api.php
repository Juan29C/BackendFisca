<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoactivoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\DocumentoCoactivoController;
use App\Http\Controllers\EntidadBancariaController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\ResolucionController;
use App\Http\Controllers\TiposDocumentoController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;
use App\Http\Middleware\CoactivoMiddleware;
use App\Http\Middleware\FiscalizacionMiddleware;
use App\Models\Coactivo;
use Doctrine\Inflector\Rules\Word;

// ===== Auth públicas =====
Route::prefix('user')->group(function () {
    Route::post('register', [UserController::class, 'store']);
    Route::get('usuarios', [UserController::class, 'index']);
    Route::post('login',    [AuthController::class, 'login']);
    Route::get('plantillas', [WordController::class, 'listarPlantillas']);
});


// ===== Rutas v1/auth - Ambos roles (Fiscalización y Coactivo) =====
Route::prefix('v1/auth')->middleware(['auth.jwt'])->group(function () {
    // Rutas comunes para ambos roles
    Route::get('me',        [AuthController::class, 'me']);
    Route::post('refresh',  [AuthController::class, 'refresh']);
    Route::post('logout',   [AuthController::class, 'logout']);
    
    // Expedientes - Lectura: Ambos roles
    Route::get('expedientes', [ExpedienteController::class, 'index'])->middleware(['multi.role:fiscalizacion,coactivo']);
    Route::get('expedientes/elevados-coactivo', [ExpedienteController::class, 'elevadosCoactivo'])->middleware(['multi.role:fiscalizacion,coactivo']);
    Route::get('expedientes/{id}', [ExpedienteController::class, 'show'])->middleware(['multi.role:fiscalizacion,coactivo']);
    
    // Expedientes - Escritura: Solo Fiscalización
    Route::post('expedientes', [ExpedienteController::class, 'store'])->middleware(['fiscalizacion']);
    Route::put('expedientes/{id}', [ExpedienteController::class, 'update'])->middleware(['fiscalizacion']);
    Route::delete('expedientes/{id}', [ExpedienteController::class, 'destroy'])->middleware(['fiscalizacion']);
    Route::post('expedientes/{id}/apelacion', [ExpedienteController::class, 'resolverApelacion'])->middleware(['fiscalizacion']);
    Route::post('expedientes/{id}/reconsideracion', [ExpedienteController::class, 'iniciarReconsideracion'])->middleware(['fiscalizacion']);

    // Documentos - Lectura: Ambos roles
    Route::get('expedientes/{expediente}/documentos', [DocumentoController::class, 'index'])->middleware(['multi.role:fiscalizacion,coactivo']);
    
    // Documentos - Escritura: Solo Fiscalización
    Route::post('expedientes/{expediente}/documentos', [DocumentoController::class, 'store'])->middleware(['fiscalizacion']);
    Route::put('expedientes/{expediente}/documentos/{id}', [DocumentoController::class, 'patch'])->middleware(['fiscalizacion']);
    Route::delete('expedientes/{expediente}/documentos/{id}', [DocumentoController::class, 'destroy'])->middleware(['fiscalizacion']);

    // Resoluciones - Lectura: Ambos roles
    Route::get('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'indexForExpediente'])->middleware(['multi.role:fiscalizacion,coactivo']);
    
    // Resoluciones - Escritura: Solo Fiscalización
    Route::post('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'storeForExpediente'])->middleware(['fiscalizacion']);

    // Tipos de Documentos - Ambos roles
    Route::get('/tipos-documentos', [TiposDocumentoController::class, 'index'])->middleware(['multi.role:fiscalizacion,coactivo']);
    
    // Entidades Bancarias - Solo Fiscalización
    Route::get('/entidades-bancarias', [EntidadBancariaController::class, 'index'])->middleware(['fiscalizacion']);
    Route::get('/entidades-bancarias/{id}', [EntidadBancariaController::class, 'show'])->middleware(['fiscalizacion']);
    Route::post('/entidades-bancarias', [EntidadBancariaController::class, 'store'])->middleware(['fiscalizacion']);
    Route::put('/entidades-bancarias/{id}', [EntidadBancariaController::class, 'update'])->middleware(['fiscalizacion']);
    Route::delete('/entidades-bancarias/{id}', [EntidadBancariaController::class, 'destroy'])->middleware(['fiscalizacion']);
    
    // Coactivos - Vincular expediente (Solo Coactivo puede crear)
    Route::post('/coactivos/vincular-expediente', [CoactivoController::class, 'vincularExpediente'])->middleware(['coactivo']);
    
    // Coactivos - Verificar vinculación de expediente
    Route::get('/coactivos/verificar-vinculacion/{idExpediente}', [CoactivoController::class, 'verificarVinculacion'])->middleware(['multi.role:fiscalizacion,coactivo']);
    
    // Coactivos - Lectura: Ambos roles
    Route::get('/coactivos', [CoactivoController::class, 'index'])->middleware(['multi.role:fiscalizacion,coactivo']);
    Route::get('/coactivos/{id}', [CoactivoController::class, 'show'])->middleware(['multi.role:fiscalizacion,coactivo']);
    
    // Documentos Coactivos - Solo Coactivo
    Route::get('/coactivos/{coactivoId}/documentos', [DocumentoCoactivoController::class, 'index'])->middleware(['coactivo']);
    Route::get('/coactivos/{coactivoId}/documentos/{id}', [DocumentoCoactivoController::class, 'show'])->middleware(['coactivo']);
    Route::post('/coactivos/{coactivoId}/documentos', [DocumentoCoactivoController::class, 'store'])->middleware(['coactivo']);
    Route::put('/coactivos/{coactivoId}/documentos/{id}', [DocumentoCoactivoController::class, 'update'])->middleware(['coactivo']);
    Route::delete('/coactivos/{coactivoId}/documentos/{id}', [DocumentoCoactivoController::class, 'destroy'])->middleware(['coactivo']);
    
    // Generar documento Word desde plantilla - Solo Coactivo
    Route::post('/coactivos/{coactivoId}/documentos/generar-resolucion-1', [DocumentoCoactivoController::class, 'generarResolucion1'])->middleware(['coactivo']);
    Route::post('/coactivos/{coactivoId}/documentos/generar-resolucion-2', [DocumentoCoactivoController::class, 'generarResolucion2'])->middleware(['coactivo']);
    Route::post('/coactivos/{coactivoId}/documentos/generar-orden-pago-total', [DocumentoCoactivoController::class, 'generarOrdenPagoTotal'])->middleware(['coactivo']);
    Route::post('/coactivos/{coactivoId}/documentos/generar-orden-pago-parcial', [DocumentoCoactivoController::class, 'generarOrdenPagoParcial'])->middleware(['coactivo']);
    
    // Obtener datos para prefill del formulario de orden de pago - Solo Coactivo
    Route::get('/coactivos/{id}/datos-para-orden-pago', [CoactivoController::class, 'getDatosParaOrdenPago'])->middleware(['coactivo']);
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\ResolucionController;
use App\Http\Controllers\TiposDocumentoController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;
use App\Http\Middleware\FiscalizacionMiddleware;
use Doctrine\Inflector\Rules\Word;

// ===== Auth públicas =====
Route::prefix('user')->group(function () {
    Route::post('register', [UserController::class, 'store']);
    Route::get('usuarios', [UserController::class, 'index']);
    Route::post('login',    [AuthController::class, 'login']);
    Route::get('plantillas', [WordController::class, 'listarPlantillas']);
});


// ===== Rutas v1 (Fiscalización) =====
Route::prefix('v1/auth')->middleware([FiscalizacionMiddleware::class])->group(function () {
    Route::get('me',        [AuthController::class, 'me']);
    Route::post('refresh',  [AuthController::class, 'refresh']);
    Route::post('logout',   [AuthController::class, 'logout']);
    
    // Expedientes
    Route::post('expedientes', [ExpedienteController::class, 'store']);
    Route::get('expedientes', [ExpedienteController::class, 'index']);
    Route::get('expedientes/{id}', [ExpedienteController::class, 'show']);
    Route::put('expedientes/{id}', [ExpedienteController::class, 'update']);
    Route::delete('expedientes/{id}', [ExpedienteController::class, 'destroy']);
    Route::post('expedientes/{id}/apelacion', [ExpedienteController::class, 'resolverApelacion']);
    Route::post('expedientes/{id}/reconsideracion', [ExpedienteController::class, 'iniciarReconsideracion']);

    // Documentos anidados en expediente
    Route::get('expedientes/{expediente}/documentos', [DocumentoController::class, 'index']);
    Route::post('expedientes/{expediente}/documentos', [DocumentoController::class, 'store']);
    Route::put('expedientes/{expediente}/documentos/{id}', [DocumentoController::class, 'patch']);
    Route::delete('expedientes/{expediente}/documentos/{id}', [DocumentoController::class, 'destroy']);

    // Resoluciones por expediente
    Route::post('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'storeForExpediente']);
    Route::get('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'indexForExpediente']);

    // Plantillas

    // Tipos de Documentos
    Route::get('/tipos-documentos', [TiposDocumentoController::class, 'index']);
    
});

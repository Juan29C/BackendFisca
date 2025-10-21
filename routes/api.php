<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\ResolucionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;


// ===== Auth públicas =====
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); // usa RegisterRequest
    Route::post('login',    [AuthController::class, 'login']);    // usa LoginRequest
    // Para refresh con token en header Authorization Bearer {token}
    Route::post('refresh',  [AuthController::class, 'refresh'])->middleware('jwt.auth');
    Route::post('logout',   [AuthController::class, 'logout'])->middleware('jwt.auth');
    Route::get('me',        [AuthController::class, 'me'])->middleware('jwt.auth');
});


// ===== Rutas v1 (Fiscalización) =====
Route::prefix('v1')->middleware(['jwt.auth', 'fiscalizacion'])->group(function () {
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

    // Resoluciones por expediente
    Route::post('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'storeForExpediente']);
    Route::get('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'indexForExpediente']);

    // Plantillas
    Route::get('plantillas', [WordController::class, 'listarPlantillas']);
});

<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\ResolucionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1')->group(function () {
    // Expedientes
    Route::post('expedientes', [ExpedienteController::class, 'store']);
    Route::get('expedientes', [ExpedienteController::class, 'index']);
    Route::get('expedientes/{id}', [ExpedienteController::class, 'show']);
    Route::put('expedientes/{id}', [ExpedienteController::class, 'update']);
    Route::delete('expedientes/{id}', [ExpedienteController::class, 'destroy']);
    Route::post('expedientes/{id}/apelacion', [ExpedienteController::class, 'resolverApelacion']);

    // Documentos anidados en expediente
    Route::get('expedientes/{expediente}/documentos', [DocumentoController::class, 'index']);
    Route::post('expedientes/{expediente}/documentos', [DocumentoController::class, 'store']);
        

    // Resoluciones (si también son por expediente)
    Route::post('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'storeForExpediente']);
    Route::get('expedientes/{expediente}/resoluciones', [ResolucionController::class, 'indexForExpediente']);

    // Catálogo de tipos de documento
    //Route::get('tipos-documentos', [TiposDocumentoController::class, 'index']);

    // Listar plantillas de documentos
    Route::get('plantillas', [WordController::class, 'listarPlantillas']);
});

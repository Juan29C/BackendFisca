<?php

use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\ResolucionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/*
|--------------------------------------------------------------------------
| API Routes                                                 
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/ping', fn () => ['ok' => true, 'time' => now()]);

Route::post('/word/plantilla', [WordController::class, 'generarDesdePlantilla']);
Route::get('/plantillas', [WordController::class, 'listarPlantillas']);
Route::apiResource('/expedientes', ExpedienteController::class)->only(['store', 'show', 'index']);
Route::post('/expedientes/{id}/documentos', [ExpedienteController::class, 'uploadDocumentos']);
Route::post('/expedientes/{id}/resoluciones', [ResolucionController::class, 'storeForExpediente']);


Route::get('/test-ex', function () { throw new \DomainException('Prueba Domain'); });

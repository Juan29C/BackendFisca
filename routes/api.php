<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuditoriaAsistenciaController;


Route::get('/asistencias', [AsistenciaController::class, 'index']);
Route::get('/asistencias/{id}', [AsistenciaController::class, 'show']);

Route::get('/auditoria-asistencias', [AuditoriaAsistenciaController::class, 'index']);
Route::get('/auditoria-asistencias/{id}', [AuditoriaAsistenciaController::class, 'show']);


<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BiometriaController;
use App\Http\Controllers\Api\ClienteApiController;
use Illuminate\Support\Facades\Route;

// ── Autenticación ──────────────────────────────────────────────────────────
Route::post('/login',  [AuthApiController::class, 'login']);
Route::post('/logout', [AuthApiController::class, 'logout'])->middleware('auth:sanctum');

// ── Agente biométrico (protegido con token de agente SHA-256) ─────────────
Route::middleware('agent.token')->prefix('agente')->group(function () {
    Route::get('/clientes',    [BiometriaController::class, 'listarClientes']);
    Route::post('/enrolar',    [BiometriaController::class, 'enrolar']);
    Route::get('/sincronizar', [BiometriaController::class, 'sincronizar']);
    Route::post('/asistencia', [BiometriaController::class, 'registrarAsistencia']);
});

// ── Portal del Cliente (protegido con token Sanctum) ──────────────────────
Route::middleware('auth:sanctum')->prefix('cliente')->group(function () {
    Route::get('/perfil',      [ClienteApiController::class, 'perfil']);
    Route::get('/membresia',   [ClienteApiController::class, 'membresia']);
    Route::get('/pagos',       [ClienteApiController::class, 'pagos']);
    Route::get('/asistencias', [ClienteApiController::class, 'asistencias']);
});

<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\MembresiaClienteController;
use App\Http\Controllers\EntrenadorController;
use App\Http\Controllers\RecepcionistaController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ClientePortalController;

// -----------------------------------------------------------------------
// Página de inicio pública
// -----------------------------------------------------------------------
Route::get('/', fn () => view('welcome'));
Route::get('/public', fn () => view('layouts.public'))->name('public');

// -----------------------------------------------------------------------
// Autenticación (Breeze)
// -----------------------------------------------------------------------
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// -----------------------------------------------------------------------
// Dashboard — dispatcher y vistas por rol
// -----------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (Auth::user()->hasRole('Cliente')) {
            return redirect()->route('dashboard.cliente');
        }
        return redirect()->route('dashboard.index');
    })->name('dashboard');

    Route::get('/dashboard/index',      [DashboardController::class, 'index'])     ->name('dashboard.index');
    Route::get('/dashboard/cliente',    [DashboardController::class, 'cliente'])   ->name('dashboard.cliente');
    Route::get('/dashboard/entrenador', [DashboardController::class, 'entrenador'])->name('dashboard.entrenador');
});

// -----------------------------------------------------------------------
// Perfil de usuario (cualquier rol autenticado)
// -----------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// -----------------------------------------------------------------------
// Archivos privados (QRs de pagos, fotos de perfil)
// -----------------------------------------------------------------------
Route::middleware('auth')->get(
    '/archivos/privados/{path}',
    [PrivateFileController::class, 'serve']
)->where('path', '.*')->name('archivos.privado');

// -----------------------------------------------------------------------
// Solo Administrador
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::resource('roles',           RolController::class);
    Route::resource('users',           UserController::class);
    Route::resource('personas',        PersonaController::class);
    Route::resource('administradores', AdministradorController::class);
    Route::resource('promociones',     PromocionController::class);
});

// -----------------------------------------------------------------------
// Administrador + Recepcionista
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Administrador,Recepcionista'])->group(function () {
    Route::resource('clientes',          ClienteController::class);
    Route::resource('membresias',        MembresiaController::class);
    Route::resource('membresia_cliente', MembresiaClienteController::class);
    Route::resource('recepcionistas',    RecepcionistaController::class);
    Route::resource('notificaciones',    NotificacionController::class);

    Route::get('/notificaciones/enviar',  [NotificacionController::class, 'showForm'])    ->name('notificaciones.form');
    Route::post('/notificaciones/enviar', [NotificacionController::class, 'sendWhatsapp'])->name('notificaciones.sendWhatsapp');

    Route::resource('asistencias', AsistenciaController::class);
    Route::resource('pagos',       PagoController::class);
});

// -----------------------------------------------------------------------
// Administrador + Entrenador
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Administrador,Entrenador'])->group(function () {
    Route::resource('entrenadores', EntrenadorController::class);
    Route::resource('rutinas',      RutinaController::class);
    Route::get('/rutinas/ver/{id}', [RutinaController::class, 'verRutina'])->name('rutinas.ver');
});

// -----------------------------------------------------------------------
// Rutas exclusivas del Cliente (su propio historial + Telegram)
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Cliente'])->group(function () {
    Route::get('mis-asistencias', [AsistenciaController::class, 'misAsistencias'])->name('asistencias.mis');
    Route::get('mis-pagos',       [PagoController::class,       'misPagos'])      ->name('pagos.mis-pagos');

    Route::post('/portal/telegram/vincular',      [ClientePortalController::class, 'vincularTelegram'])   ->name('portal.telegram.vincular');
    Route::delete('/portal/telegram/desvincular', [ClientePortalController::class, 'desvincularTelegram'])->name('portal.telegram.desvincular');
});

// -----------------------------------------------------------------------
// Reportes — solo Administrador
// -----------------------------------------------------------------------
Route::prefix('reportes')->name('reportes.')->middleware(['auth', 'role:Administrador'])->group(function () {
    Route::get('clientes-estado',               [ReporteController::class, 'clientesEstado'])              ->name('clientes.estado');
    Route::get('clientes-estado/pdf',           [ReporteController::class, 'exportClientesEstadoPDF'])     ->name('clientes.estado.pdf');
    Route::get('clientes-nuevos',               [ReporteController::class, 'clientesNuevos'])              ->name('clientes.nuevos');
    Route::get('clientes-nuevos/pdf',           [ReporteController::class, 'exportClientesNuevosPDF'])     ->name('clientes.nuevos.pdf');
    Route::get('clientes-membresia-vencida',    [ReporteController::class, 'clientesMembresiaVencida'])    ->name('clientes.membresia_vencida');
    Route::get('clientes-membresia-vencida/pdf',[ReporteController::class, 'exportClientesMembresiaVencidaPDF'])->name('clientes.membresia_vencida.pdf');
    Route::get('pagos-por-mes',                 [ReporteController::class, 'pagosPorMes'])                 ->name('pagos.mes');
    Route::get('pagos-por-mes/pdf',             [ReporteController::class, 'exportPagosPorMesPDF'])        ->name('pagos.mes.pdf');
});

// -----------------------------------------------------------------------
// Push notifications: se rehará correctamente en una fase posterior.
// -----------------------------------------------------------------------

require __DIR__.'/auth.php';

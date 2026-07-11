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
use App\Http\Controllers\AsistenciaQrController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ClientePortalController;
use App\Http\Controllers\DescuentoController;
use App\Http\Controllers\QrPagoController;

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

    // GESTIÓN del catálogo (crear/editar/borrar tipos de membresía y
    // descuentos): SOLO Admin. El listado (index) es de solo lectura
    // para todo el personal (ver grupo de los tres roles más abajo).
    Route::resource('membresias', MembresiaController::class)->except(['index']);
    Route::resource('descuentos', DescuentoController::class)->except(['index', 'show']);

    // Borrar personal: solo Admin (Recepcionista solo ve/crea/edita)
    Route::delete('entrenadores/{id}',   [EntrenadorController::class,    'destroy'])->name('entrenadores.destroy');
    Route::delete('recepcionistas/{id}', [RecepcionistaController::class, 'destroy'])->name('recepcionistas.destroy');

    // QR de pago único del gimnasio: solo el Administrador lo configura/reemplaza
    Route::get('configuracion/qr-pago',  [QrPagoController::class, 'edit'])  ->name('qr_pago.edit');
    Route::post('configuracion/qr-pago', [QrPagoController::class, 'update'])->name('qr_pago.update');
});

// -----------------------------------------------------------------------
// Administrador + Recepcionista
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Administrador,Recepcionista'])->group(function () {
    // Gestión de personal (ver/crear/editar); el borrado es solo del Admin
    Route::resource('entrenadores',   EntrenadorController::class)->except(['destroy']);
    Route::resource('recepcionistas', RecepcionistaController::class)->except(['destroy']);

    Route::resource('notificaciones', NotificacionController::class);
    Route::get('/notificaciones/enviar',  [NotificacionController::class, 'showForm'])    ->name('notificaciones.form');
    Route::post('/notificaciones/enviar', [NotificacionController::class, 'sendWhatsapp'])->name('notificaciones.sendWhatsapp');

    // El Entrenador no borra clientes ni edita/borra pagos (solo registra)
    Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    Route::resource('pagos', PagoController::class)->only(['edit', 'update', 'destroy']);
});

// -----------------------------------------------------------------------
// Administrador + Recepcionista + Entrenador
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Administrador,Recepcionista,Entrenador'])->group(function () {
    Route::get('asistencias-qr',  [AsistenciaQrController::class, 'index'])    ->name('asistencias.qr');
    Route::post('asistencias-qr', [AsistenciaQrController::class, 'registrar'])->name('asistencias.qr.registrar');

    // Imagen del QR de pago (disco privado): la ven quienes cobran (Registrar Pago)
    Route::get('qr-pago/imagen', [QrPagoController::class, 'imagen'])->name('qr_pago.imagen');

    // Membresías de Clientes: SOLO listado/edición/eliminación. Asignar una
    // membresía nueva ya no existe como flujo suelto — la ÚNICA forma de
    // dársela a un cliente es "Registrar Pago" (crea la asignación y su pago
    // vinculado en un solo paso, dejándola activa de inmediato).
    // 'show' también se excluye: nunca tuvo vista propia (nada la enlaza) y,
    // sin 'create' registrada como ruta estática, /membresia_cliente/create
    // caería en el comodín {membresia_cliente} de 'show' y rompería.
    Route::resource('membresia_cliente', MembresiaClienteController::class)->except(['create', 'store', 'show']);

    // Clientes: los tres roles ven/crean/editan (borrar: Admin + Recepcionista)
    Route::resource('clientes', ClienteController::class)->except(['destroy']);

    Route::resource('asistencias', AsistenciaController::class);

    // Pagos: los tres roles registran (editar/borrar: Admin + Recepcionista)
    Route::resource('pagos', PagoController::class)->only(['index', 'create', 'store', 'show']);
    // Comprobante de pago (imagen privada): solo personal, nunca URL pública
    Route::get('pagos/{pago}/comprobante', [PagoController::class, 'comprobante'])->name('pagos.comprobante');

    Route::resource('rutinas', RutinaController::class);
    Route::get('/rutinas/ver/{id}', [RutinaController::class, 'verRutina'])->name('rutinas.ver');

    // Catálogo en SOLO LECTURA para todo el personal (gestión: solo Admin)
    Route::get('membresias', [MembresiaController::class, 'index'])->name('membresias.index');
    Route::get('descuentos', [DescuentoController::class, 'index'])->name('descuentos.index');
});

// -----------------------------------------------------------------------
// Rutas exclusivas del Cliente (su propio historial + Telegram)
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Cliente'])->group(function () {
    Route::get('mis-asistencias', [AsistenciaController::class, 'misAsistencias'])->name('asistencias.mis');
    Route::get('mis-pagos',       [PagoController::class,       'misPagos'])      ->name('pagos.mis-pagos');
    Route::get('mis-pagos/{pago}/comprobante', [PagoController::class, 'miComprobante'])->name('pagos.mis.comprobante');
    Route::get('mi-membresia',    [ClientePortalController::class, 'miMembresia'])->name('portal.membresia');
    Route::get('mis-rutinas',     [ClientePortalController::class, 'misRutinas']) ->name('portal.rutinas');

    Route::get('/portal/carnet/descargar', [ClientePortalController::class, 'descargarCarnet'])->name('portal.carnet.descargar');

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

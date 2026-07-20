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
use App\Http\Controllers\CalendarioMusculoController;
use App\Http\Controllers\DescuentoController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\GrupoMuscularController;
use App\Http\Controllers\QrPagoController;

// -----------------------------------------------------------------------
// Página de inicio pública
// -----------------------------------------------------------------------
Route::get('/', fn () => view('welcome'));
Route::get('/public', fn () => view('layouts.public'))->name('public');

// -----------------------------------------------------------------------
// PREVIEW de la nueva home pública (plantilla GymOn, Homepage 2). Ruta de
// prueba en paralelo a "/" — no reemplaza la home real todavía.
// -----------------------------------------------------------------------
Route::get('/preview-gymon', function (\Illuminate\Http\Request $request) {
    $temaOscuro = $request->cookie('gymon_tema', 'dark') !== 'light';
    $waLink = 'https://wa.me/59176372337?text=Hola,%20quiero%20unirme%20a%20la%20Calistenia%20MadBarzz.';
    $membresias = \App\Models\Membresia::orderBy('precio')->get();

    return view('welcome-gymon', compact('temaOscuro', 'waLink', 'membresias'));
})->name('preview.gymon');

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

    // Catálogo de grupos musculares (base del futuro calendario de rutinas):
    // SOLO Admin gestiona. El listado (index) es de solo lectura para todo
    // el personal (ver grupo de los tres roles más abajo).
    Route::resource('grupos_musculares', GrupoMuscularController::class)
        ->except(['index', 'show'])
        ->parameters(['grupos_musculares' => 'grupo_muscular']);

    // Calendario mensual de grupos musculares (Etapa 2 del módulo de
    // rutinas): SOLO Admin por ahora. Entrenador y Cliente lo consumirán
    // en etapas siguientes (aún no tienen ninguna ruta de lectura aquí).
    Route::get('calendario-musculos',        [CalendarioMusculoController::class, 'index'])       ->name('calendario_musculos.index');
    Route::put('calendario-musculos/dia/{fecha}', [CalendarioMusculoController::class, 'asignarDia'])
        ->where('fecha', '\d{4}-\d{2}-\d{2}')
        ->name('calendario_musculos.dia');
    Route::post('calendario-musculos/patron', [CalendarioMusculoController::class, 'aplicarPatron'])->name('calendario_musculos.patron');

    // Catálogo de ejercicios (importado del dataset externo): listado de
    // solo lectura para verificar la importación. Armar rutinas con esto es
    // una etapa posterior; por ahora, solo el Administrador lo ve.
    Route::get('ejercicios', [EjercicioController::class, 'index'])->name('ejercicios.index');

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

    // Catálogo en SOLO LECTURA para todo el personal (gestión: solo Admin)
    Route::get('membresias', [MembresiaController::class, 'index'])->name('membresias.index');
    Route::get('descuentos', [DescuentoController::class, 'index'])->name('descuentos.index');
    Route::get('grupos_musculares', [GrupoMuscularController::class, 'index'])->name('grupos_musculares.index');
});

// -----------------------------------------------------------------------
// Rutinas (Etapa 3 del módulo de rutinas): el Entrenador arma SU rutina
// para un día del calendario; el Admin arma la suya y puede borrar la de
// cualquiera. La Recepcionista, por ahora, no arma rutinas.
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Administrador,Entrenador'])->group(function () {
    Route::get('rutinas', [RutinaController::class, 'index'])->name('rutinas.index');
    Route::get('rutinas/dia/{fecha}', [RutinaController::class, 'edit'])
        ->where('fecha', '\d{4}-\d{2}-\d{2}')
        ->name('rutinas.edit');
    Route::put('rutinas/dia/{fecha}', [RutinaController::class, 'update'])
        ->where('fecha', '\d{4}-\d{2}-\d{2}')
        ->name('rutinas.update');
    Route::get('rutinas/{rutina}', [RutinaController::class, 'show'])->whereNumber('rutina')->name('rutinas.show');
    Route::delete('rutinas/{rutina}', [RutinaController::class, 'destroy'])->whereNumber('rutina')->name('rutinas.destroy');
});

// -----------------------------------------------------------------------
// Rutas exclusivas del Cliente (su propio historial + Telegram)
// -----------------------------------------------------------------------
Route::middleware(['auth', 'role:Cliente'])->group(function () {
    Route::get('mis-asistencias', [AsistenciaController::class, 'misAsistencias'])->name('asistencias.mis');
    Route::get('mis-pagos',       [PagoController::class,       'misPagos'])      ->name('pagos.mis-pagos');
    Route::get('mis-pagos/{pago}/comprobante', [PagoController::class, 'miComprobante'])->name('pagos.mis.comprobante');
    Route::get('mi-membresia',    [ClientePortalController::class, 'miMembresia'])->name('portal.membresia');
    Route::get('mi-rutina',       [ClientePortalController::class, 'miRutina'])   ->name('portal.rutina');

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

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
   use App\Services\WhatsappService;
use App\Models\Administrador;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(WhatsappService::class, function ($app) {
            return new WhatsappService();
        });
    }


    /**
     * Bootstrap any application services.
     */

    public function boot()
    {
        // Forzar HTTPS en producción (usa config(), no env())
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Política mínima de contraseña (hallazgo 6.5 de la auditoría de
        // seguridad): un solo lugar central para Rules\Password::defaults(),
        // que ya usan el registro público, el reset y el cambio de
        // contraseña del perfil. Antes dependía del default implícito de
        // Laravel (min 8) — ahora queda explícito acá para no depender de
        // qué haga el framework por detrás, y como único punto para subir
        // el mínimo el día de mañana si hace falta.
        Password::defaults(fn () => Password::min(8));

        // Un único view composer que comparte el superadmin a todas las vistas
        view()->composer('*', function ($view) {
            $superadmin = Administrador::with(['persona', 'user'])
                ->where('nivel_acceso', 'superadmin')
                ->first();
            $view->with('superadmin', $superadmin);
        });

        // Campanita de Avisos (Etapa 2): solo corre para layouts/private.blade.php
        // (no '*'), y adentro se corta de una si el rol no es Cliente — cero
        // consultas extra para el resto de los roles.
        view()->composer('layouts.private', \App\View\Composers\AvisosClienteComposer::class);

        // Web Push (Etapa 1): en Windows, cualquier operación con claves EC
        // (VAPID) falla si el proceso no sabe dónde está openssl.cnf — se
        // fija una vez acá, al bootear la app, para que TODO código que
        // termine usando minishlink/web-push (WebPushChannel, tests, un
        // comando de generar claves, etc.) quede cubierto sin repetir este
        // chequeo en cada lugar. No hace nada fuera de Windows.
        \App\Notifications\Channels\WebPushChannel::asegurarOpensslConfEnWindows();
    }





}

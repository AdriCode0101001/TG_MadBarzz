<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
   use App\Services\WhatsappService;
use App\Models\Administrador;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

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

        // Etapa 3 del auto-registro: al registrarse (evento Registered),
        // envía el correo de verificación — en modo log por ahora
        // (MAIL_MAILER=log), el link queda en storage/logs/laravel.log. Este
        // proyecto no tiene EventServiceProvider (skeleton nuevo de Laravel),
        // así que este listener por defecto de Laravel no se registra solo:
        // hay que engancharlo acá. El listener mismo ya revisa
        // "instanceof MustVerifyEmail && !hasVerifiedEmail()", así que no
        // hace nada para el personal ni para clientes dados de alta por el
        // personal (quedan verificados desde que se crean).
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

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

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
   use App\Services\WhatsappService;
use App\Models\Administrador;
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

        // Un único view composer que comparte el superadmin a todas las vistas
        view()->composer('*', function ($view) {
            $superadmin = Administrador::with(['persona', 'user'])
                ->where('nivel_acceso', 'superadmin')
                ->first();
            $view->with('superadmin', $superadmin);
        });
    }





}

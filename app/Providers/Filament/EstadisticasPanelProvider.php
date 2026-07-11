<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAdminAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel Filament de ESTADÍSTICAS, solo lectura y solo para Administrador.
 * No gestiona entidades (sin recursos CRUD): la administración sigue
 * en las pantallas Blade existentes. El login es el de la app (Breeze).
 */
class EstadisticasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('estadisticas')
            ->path('estadisticas')
            ->brandName('MadBarzz · Estadísticas')
            ->favicon(asset('assets_private/images/logo_mad.png'))
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationItems([
                NavigationItem::make('volver')
                    ->label('← Volver al panel de gestión')
                    ->url('/dashboard')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->sort(99),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentAdminAuthenticate::class,
            ]);
    }
}

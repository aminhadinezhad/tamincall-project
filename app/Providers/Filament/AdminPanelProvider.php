<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            // the whole app is this panel, so it lives at the root of its subdomain
            ->path('')
            ->login()
            ->brandName('تامین کال')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('ico/favicon-32x32.png'))
            ->font('Kalameh', url: asset('css/fonts.css'), provider: LocalFontProvider::class)
            // the Tamin Falat look (resources/css/filament/admin/theme.css) is designed for light only
            ->viteTheme('resources/css/filament/admin/theme.css')
            // the Kalameh weights the theme uses start downloading with the page, not after the CSS,
            // so text appears in Kalameh from the first paint instead of switching fonts a moment later
            ->renderHook(PanelsRenderHook::HEAD_START, fn (): string => collect(['Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold'])
                ->map(fn (string $weight): string => '<link rel="preload" href="'.e(asset("fonts/kalameh/KalamehWeb(FaNum)-{$weight}.woff2")).'" as="font" type="font/woff2" crossorigin>')
                ->implode(''))
            // web app: added to a phone's home screen it opens full screen with its own icon
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => implode('', [
                '<link rel="manifest" href="'.e(asset('manifest.webmanifest')).'">',
                // the status bar matches the light grey top bar
                '<meta name="theme-color" content="#f7f7f7">',
                '<meta name="mobile-web-app-capable" content="yes">',
                '<meta name="apple-mobile-web-app-capable" content="yes">',
                '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
                '<meta name="apple-mobile-web-app-title" content="تامین کال">',
                '<link rel="apple-touch-icon" href="'.e(asset('ico/apple-touch-icon.png')).'">',
            ]))
            // «ثبت تماس» sits beside the search on every page, so a new call is one click from anywhere
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => view('filament.quick-call'))
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#164194'),
                'warning' => Color::hex('#f18815'),
                // the brand book's neutral (#c6c6c6, K30) is a pure grey, so Filament's greys are too
                'gray' => Color::Neutral,
            ])
            ->navigationGroups([
                'تماس ها',
                'مدیریت',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs(false)
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

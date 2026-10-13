<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokenManager;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\DataQualityProfiling;
use App\Filament\Pages\ReportsExportCenter;
use App\Filament\Pages\VisualQueryBuilder;
use App\Filament\Widgets\CategoryDistributionChart;
use App\Filament\Widgets\DataQualityRadarChart;
use App\Filament\Widgets\IngestionTrendChart;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentDatasetsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->path('admin')
            ->brandName('ASETS')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->spa()
            ->login(Login::class)
            ->profile(isSimple: false)
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profile Settings')
                    ->icon('heroicon-o-user')
                    ->url(fn (): string => route('filament.admin.auth.profile')),
                MenuItem::make()
                    ->label('Security & Password')
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => route('filament.admin.auth.profile')),
                MenuItem::make()
                    ->label('My API Credentials')
                    ->icon('heroicon-o-code-bracket')
                    ->url(fn (): string => route('filament.admin.pages.api-tokens')),
            ])
            ->darkMode(true)
            ->themeSwitcher(false)
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.hooks.theme-toggle')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.global-overlay-loader')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.components.sidebar-footer-widget')
            )
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('CORE PLATFORM')
                    ->collapsible(false),
                NavigationGroup::make('ANALYTICS & INSIGHTS')
                    ->collapsible(false),
                NavigationGroup::make('INTEGRATIONS & AUDIT')
                    ->collapsible(false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                DataQualityProfiling::class,
                VisualQueryBuilder::class,
                ReportsExportCenter::class,
                ApiTokenManager::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                CategoryDistributionChart::class,
                IngestionTrendChart::class,
                DataQualityRadarChart::class,
                QuickActionsWidget::class,
                RecentDatasetsWidget::class,
            ])
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

<?php

namespace App\Providers\Filament;

use Filament\GlobalSearch\GlobalSearchResults;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->font(family: 'IBM Plex Sans Arabic', url: 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap')
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\ModulesManager::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                //AccountWidget::class,
                //FilamentInfoWidget::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    // ->items([
                    //     NavigationItem::make('لوحة التحكم')
                    //         ->icon('heroicon-o-home')
                    //         ->url(fn (): string => Dashboard::getUrl())
                    //         ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard')),
                    // ])
                    ->groups([
                        NavigationGroup::make('الماليات')
                            ->items([
                                ...array_map(fn($item) => $item->icon('heroicon-o-arrow-down-tray'), 
                                    \App\Filament\Resources\CollectionPaymentResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-arrow-up-tray'), 
                                    \App\Filament\Resources\SupplyPaymentResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-calculator'), 
                                    \App\Filament\Resources\OperationResource::getNavigationItems()),
                            ]),
                        NavigationGroup::make('التعاقدات')
                            ->items([
                                ...array_map(fn($item) => $item->icon('heroicon-o-clipboard-document-check'), 
                                    \App\Filament\Resources\UnitContractResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-document-check'), 
                                    \App\Filament\Resources\PropertyContractResource::getNavigationItems()),
                            ]),
                        NavigationGroup::make('العقارات')
                            ->items([
                                ...array_map(fn($item) => $item->icon('heroicon-o-home'), 
                                    \App\Filament\Resources\Units\UnitResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-building-office-2'), 
                                    \App\Filament\Resources\PropertyResource::getNavigationItems()),
                            ]),
                        NavigationGroup::make('المستخدمين')
                            ->items([
                                ...array_map(fn($item) => $item->icon('heroicon-o-users'), 
                                    \App\Filament\Resources\TenantResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-key'), 
                                    \App\Filament\Resources\OwnerResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-briefcase'), 
                                    \App\Filament\Resources\EmployeeResource::getNavigationItems()),
                            ]),
                        NavigationGroup::make('التأسيس')
                            ->items([
                                ...array_map(fn($item) => $item->icon('heroicon-o-map-pin'), 
                                    \App\Filament\Resources\LocationResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-sparkles'), 
                                    \App\Filament\Resources\UnitFeatureResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-bookmark'), 
                                    \App\Filament\Resources\UnitCategories\UnitCategoryResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-tag'), 
                                    \App\Filament\Resources\UnitTypes\UnitTypeResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-star'), 
                                    \App\Filament\Resources\PropertyFeatureResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-signal'), 
                                    \App\Filament\Resources\PropertyStatusResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->icon('heroicon-o-squares-2x2'), 
                                    \App\Filament\Resources\PropertyTypeResource::getNavigationItems()),

                            ])
                            ->collapsed(),
                        // NavigationGroup::make('النظام')
                        //     ->items([
                        //         NavigationItem::make('Modules Manager')
                        //             ->icon('heroicon-o-squares-plus')
                        //             ->url(fn (): string => \App\Filament\Pages\ModulesManager::getUrl())
                        //             ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.modules-manager')),
                        //     ])
                        //     ->collapsed(),
                    ]);
            })
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
                Authenticate::class,
            ])
            ->authGuard('web')
            ->globalSearch();
    }
}

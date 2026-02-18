<?php

namespace App\Providers\Filament;

use App\Filament\Pages\SystemManagement;
use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\CustomFields\CustomFieldResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Owners\OwnerResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use App\Filament\Resources\PropertyFeatures\PropertyFeatureResource;
use App\Filament\Resources\PropertyStatuses\PropertyStatusResource;
use App\Filament\Resources\PropertyTypes\PropertyTypeResource;
use App\Filament\Resources\SupplyPayments\SupplyPaymentResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Resources\UnitCategories\UnitCategoryResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Filament\Resources\UnitFeatures\UnitFeatureResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\UnitTypes\UnitTypeResource;
use App\Filament\Widgets\PostponedPaymentsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TenantsPaymentDueWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->profile()
            ->sidebarWidth('15rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->pages([])
            ->widgets([
                StatsOverviewWidget::class,
                TenantsPaymentDueWidget::class,
                PostponedPaymentsWidget::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->groups([
                        NavigationGroup::make('الماليات')
                            ->items([
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-arrow-down-tray'),
                                    CollectionPaymentResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-arrow-up-tray'),
                                    SupplyPaymentResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-banknotes'),
                                    ExpenseResource::getNavigationItems()
                                ),
                            ]),
                        NavigationGroup::make('التعاقدات')
                            ->items([
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-clipboard-document-check'),
                                    UnitContractResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-document-check'),
                                    PropertyContractResource::getNavigationItems()
                                ),
                            ]),
                        NavigationGroup::make('العقارات')
                            ->items([
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-home'),
                                    UnitResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-building-office-2'),
                                    PropertyResource::getNavigationItems()
                                ),
                            ]),
                        NavigationGroup::make('المستخدمين')
                            ->items(array_filter([
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-users'),
                                    TenantResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-key'),
                                    OwnerResource::getNavigationItems()
                                ),
                                // عرض الموظفين للمدير والمدير العام فقط
                                ...(in_array(auth()->user()?->type, ['super_admin', 'admin'])
                                    ? array_map(
                                        fn ($item) => $item->icon('heroicon-o-briefcase'),
                                        EmployeeResource::getNavigationItems()
                                    )
                                    : []),
                            ])),
                        NavigationGroup::make('التأسيس')
                            ->items([
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-adjustments-horizontal'),
                                    CustomFieldResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-map-pin'),
                                    LocationResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-sparkles'),
                                    UnitFeatureResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-bookmark'),
                                    UnitCategoryResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-tag'),
                                    UnitTypeResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-star'),
                                    PropertyFeatureResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-signal'),
                                    PropertyStatusResource::getNavigationItems()
                                ),
                                ...array_map(
                                    fn ($item) => $item->icon('heroicon-o-squares-2x2'),
                                    PropertyTypeResource::getNavigationItems()
                                ),

                            ])
                            ->collapsed(),
                        NavigationGroup::make('النظام')
                            ->items([
                                NavigationItem::make('إدارة النظام')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->url(fn (): string => SystemManagement::getUrl())
                                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.system-management'))
                                    ->visible(fn (): bool => in_array(auth()->user()?->type, ['super_admin', 'admin'])),
                            ])
                            ->collapsed(),
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

<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Filament\Resources\Owners\OwnerResource;
use App\Models\CollectionPayment;
use App\Models\Property;
use App\Models\PropertyContract;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewOwner extends ViewRecord
{
    protected static string $resource = OwnerResource::class;

    protected static ?string $title = 'تقرير المالك';

    public function infolist(Schema $schema): Schema
    {
        $owner = $this->record;
        $reportData = $this->getReportData();

        return $schema
            ->columns(2)
            ->components([
                // معلومات المالك الأساسية
                Section::make('معلومات المالك')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم المالك')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('phone')
                                    ->label('التليفون')
                                    ->icon('heroicon-o-phone')
                                    ->color('primary'),
                                TextEntry::make('secondary_phone')
                                    ->label('التليفون 2')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->icon('heroicon-o-envelope')
                                    ->placeholder('غير محدد'),
                            ]),
                    ])
                    ->columnSpan(1),

                // ملخص الإحصائيات
                Section::make('ملخص الإحصائيات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_properties')
                                    ->label('عدد العقارات')
                                    ->state($reportData['summary']['total_properties'])
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg'),
                                TextEntry::make('total_units')
                                    ->label('إجمالي الوحدات')
                                    ->state($reportData['summary']['total_units'])
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('occupied_units')
                                    ->label('وحدات مشغولة')
                                    ->state($reportData['summary']['occupied_units'])
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('vacant_units')
                                    ->label('وحدات شاغرة')
                                    ->state($reportData['summary']['vacant_units'])
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('occupancy_rate')
                                    ->label('نسبة الإشغال')
                                    ->state($reportData['summary']['occupancy_rate'].'%')
                                    ->badge()
                                    ->color(fn () => match (true) {
                                        $reportData['summary']['occupancy_rate'] >= 80 => 'success',
                                        $reportData['summary']['occupancy_rate'] >= 50 => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                    ])
                    ->columnSpan(1),

                // الإحصائيات المالية
                Section::make('الإحصائيات المالية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('monthly_revenue')
                                    ->label('الإيراد الشهري')
                                    ->state(number_format($reportData['summary']['monthly_revenue'], 2).' ر.س')
                                    ->color('success')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('annual_revenue')
                                    ->label('الإيراد السنوي')
                                    ->state(number_format($reportData['summary']['annual_revenue'], 2).' ر.س')
                                    ->color('success')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('total_paid')
                                    ->label('إجمالي المحصل')
                                    ->state(number_format($reportData['summary']['total_paid'], 2).' ر.س')
                                    ->color('success')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('total_overdue')
                                    ->label('إجمالي المتأخر')
                                    ->state(number_format($reportData['summary']['total_overdue'], 2).' ر.س')
                                    ->color('danger')
                                    ->weight(FontWeight::Bold),
                            ]),
                    ])
                    ->columnSpanFull(),

                // تقرير العقارات
                Section::make('تقرير العقارات')
                    ->schema([
                        RepeatableEntry::make('properties_report')
                            ->hiddenLabel()
                            ->state($reportData['propertiesReport'])
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('property_name')
                                            ->label('اسم العقار')
                                            ->weight(FontWeight::Bold)
                                            ->size('lg'),
                                        TextEntry::make('location')
                                            ->label('الموقع')
                                            ->icon('heroicon-o-map-pin'),
                                        TextEntry::make('property_category')
                                            ->label('صنف العقار')
                                            ->badge()
                                            ->color('info'),
                                    ]),
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('collection_payments')
                                            ->label('دفعات التحصيل')
                                            ->badge()
                                            ->color('warning'),
                                        TextEntry::make('total_income')
                                            ->label('المحصل')
                                            ->formatStateUsing(fn ($state) => number_format($state, 2).' ر.س')
                                            ->color('success')
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('admin_percentage')
                                            ->label('نسبة الإدارة')
                                            ->formatStateUsing(fn ($state) => $state.'%')
                                            ->badge()
                                            ->color('gray'),
                                        TextEntry::make('admin_fee')
                                            ->label('رسوم الإدارة')
                                            ->formatStateUsing(fn ($state) => number_format($state, 2).' ر.س')
                                            ->color('danger')
                                            ->weight(FontWeight::Bold),
                                    ]),
                            ])
                            ->columns(1),
                    ])
                    ->columnSpanFull()
                    ->visible(count($reportData['propertiesReport']) > 0),

                // إجمالي العقارات
                Section::make('إجمالي تقرير العقارات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_income')
                                    ->label('إجمالي المحصل')
                                    ->state(number_format($reportData['propertiesTotal']['total_income'], 2).' ر.س')
                                    ->color('success')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('total_admin_fee')
                                    ->label('إجمالي رسوم الإدارة')
                                    ->state(number_format($reportData['propertiesTotal']['total_admin_fee'], 2).' ر.س')
                                    ->color('danger')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->visible(count($reportData['propertiesReport']) > 0),

                // تقرير المستأجرين والعقود النشطة
                Section::make('العقود النشطة والمستأجرين')
                    ->schema([
                        RepeatableEntry::make('tenants_report')
                            ->hiddenLabel()
                            ->state($reportData['tenantsReport'])
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        TextEntry::make('property_name')
                                            ->label('العقار')
                                            ->badge()
                                            ->color('primary'),
                                        TextEntry::make('unit_name')
                                            ->label('الوحدة')
                                            ->icon('heroicon-o-home'),
                                        TextEntry::make('tenant_name')
                                            ->label('المستأجر')
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('tenant_phone')
                                            ->label('الهاتف')
                                            ->icon('heroicon-o-phone'),
                                        TextEntry::make('monthly_rent')
                                            ->label('الإيجار الشهري')
                                            ->formatStateUsing(fn ($state) => number_format($state, 2).' ر.س')
                                            ->color('warning'),
                                        TextEntry::make('remaining_days')
                                            ->label('الأيام المتبقية')
                                            ->formatStateUsing(fn ($state) => $state > 0 ? $state.' يوم' : 'منتهي')
                                            ->badge()
                                            ->color(fn ($state) => match (true) {
                                                $state <= 0 => 'danger',
                                                $state <= 30 => 'warning',
                                                default => 'success',
                                            }),
                                    ]),
                            ])
                            ->columns(1),
                    ])
                    ->columnSpanFull()
                    ->visible(count($reportData['tenantsReport']) > 0)
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('الملاك')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(OwnerResource::getUrl('index')),
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('طباعة تقرير المالك')
                ->modalContent(function () {
                    $data = $this->getReportData();
                    $data['owner'] = $this->record;

                    return view('filament.resources.owner-resource.pages.print-owner', $data);
                })
                ->modalWidth('7xl')
                ->modalFooterActions([
                    Action::make('printReport')
                        ->label('طباعة التقرير')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->extraAttributes([
                            'onclick' => "
                                var printContent = document.querySelector('.print-content').innerHTML;
                                var originalContent = document.body.innerHTML;
                                document.body.innerHTML = printContent;
                                window.print();
                                document.body.innerHTML = originalContent;
                                window.location.reload();
                                return false;
                            ",
                        ]),
                    Action::make('close')
                        ->label('إلغاء')
                        ->color('gray')
                        ->close(),
                ]),
            EditAction::make()->label('تعديل'),
        ];
    }

    protected function getReportData(): array
    {
        $owner = $this->record;

        // جلب عقارات المالك مع العلاقات
        $properties = Property::where('owner_id', $owner->id)
            ->with([
                'location',
                'propertyType',
                'units' => function ($query) {
                    $query->with([
                        'unitType',
                        'activeContract' => function ($q) {
                            $q->with(['tenant', 'payments']);
                        },
                    ]);
                },
            ])
            ->get();

        // حساب الإحصائيات العامة
        $totalProperties = $properties->count();
        $totalUnits = $properties->sum(fn ($property) => $property->units->count());
        $occupiedUnits = $properties->sum(fn ($property) => $property->units->filter(fn ($unit) => $unit->activeContract !== null)->count());
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        // بيانات تقرير العقارات
        $propertiesReport = [];
        $ownerDetailedReport = [];
        $totalIncome = 0;
        $totalAdminFee = 0;
        $totalPaid = 0;
        $totalOverdue = 0;
        $totalMaintenance = 0;

        foreach ($properties as $property) {
            $propertyIncome = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->sum('total_amount');

            $propertyPaid = $propertyIncome;

            $propertyOverdue = CollectionPayment::where('property_id', $property->id)
                ->overduePayments()
                ->sum('total_amount');

            $propertyContract = PropertyContract::where('property_id', $property->id)
                ->where('contract_status', 'active')
                ->first();

            $adminPercentage = $propertyContract?->commission_rate ?? 0;
            $adminFee = $propertyIncome * ($adminPercentage / 100);

            // تقرير العقارات (للعرض والطباعة)
            $propertiesReport[] = [
                'property_name' => $property->name,
                'location' => $property->location?->name ?? 'غير محدد',
                'is_residential' => $property->propertyType?->slug === 'residential' ? 'نعم' : 'لا',
                'is_commercial' => $property->propertyType?->slug === 'commercial' ? 'نعم' : 'لا',
                'property_category' => $property->propertyType?->name ?? '-',
                'collection_payments' => CollectionPayment::where('property_id', $property->id)->count(),
                'total_income' => $propertyIncome,
                'admin_percentage' => $adminPercentage,
                'admin_fee' => $adminFee,
            ];

            // تقرير تفصيلي للطباعة
            $unitsCount = $property->units->count();
            $unitTypes = $property->units->pluck('unitType.name')->unique()->filter()->implode(', ') ?: '-';

            $firstPayment = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->orderBy('due_date_start', 'asc')
                ->first();

            $lastPayment = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->orderBy('due_date_end', 'desc')
                ->first();

            $ownerDetailedReport[] = [
                'property_name' => $property->name,
                'units_count' => $unitsCount,
                'unit_type' => $unitTypes,
                'payment_date_from' => $firstPayment?->due_date_start,
                'payment_date_to' => $lastPayment?->due_date_end,
                'payment_amount' => $propertyIncome,
                'admin_fee' => $adminFee,
                'maintenance_special' => 0,
                'government_obligations' => 0,
                'net_income' => $propertyIncome - $adminFee,
            ];

            $totalIncome += $propertyIncome;
            $totalAdminFee += $adminFee;
            $totalPaid += $propertyPaid;
            $totalOverdue += $propertyOverdue;
        }

        // بيانات المستأجرين والعقود النشطة
        $tenantsReport = [];
        foreach ($properties as $property) {
            foreach ($property->units as $unit) {
                if ($unit->activeContract) {
                    $contract = $unit->activeContract;
                    $remainingDays = $contract->getRemainingDays();

                    $tenantsReport[] = [
                        'property_name' => $property->name,
                        'unit_name' => $unit->name,
                        'tenant_name' => $contract->tenant->name,
                        'tenant_phone' => $contract->tenant->phone,
                        'monthly_rent' => $contract->monthly_rent,
                        'remaining_days' => $remainingDays,
                    ];
                }
            }
        }

        // حساب الإيرادات
        $monthlyRevenue = $properties->sum(fn ($property) => $property->units->sum(fn ($unit) => $unit->activeContract?->monthly_rent ?? 0));
        $annualRevenue = $monthlyRevenue * 12;

        // إجماليات التقرير التفصيلي
        $detailedTotals = [
            'payment_amount' => $totalIncome,
            'admin_fee' => $totalAdminFee,
            'maintenance_special' => $totalMaintenance,
            'government_obligations' => 0,
            'general_maintenance' => 0,
            'general_obligations' => 0,
            'net_income' => $totalIncome - $totalAdminFee,
            'grand_total' => $totalIncome - $totalAdminFee - $totalMaintenance,
        ];

        return [
            'summary' => [
                'total_properties' => $totalProperties,
                'total_units' => $totalUnits,
                'occupied_units' => $occupiedUnits,
                'vacant_units' => $vacantUnits,
                'occupancy_rate' => $occupancyRate,
                'monthly_revenue' => $monthlyRevenue,
                'annual_revenue' => $annualRevenue,
                'total_paid' => $totalPaid,
                'total_overdue' => $totalOverdue,
            ],
            'propertiesReport' => $propertiesReport,
            'propertiesTotal' => [
                'total_income' => $totalIncome,
                'total_admin_fee' => $totalAdminFee,
            ],
            'ownerDetailedReport' => $ownerDetailedReport,
            'detailedTotals' => $detailedTotals,
            'tenantsReport' => $tenantsReport,
        ];
    }
}

<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected static ?string $title = 'تقرير العقار';

    public function infolist(Schema $schema): Schema
    {
        $property = $this->record;
        $reportData = $this->getReportData();

        $totalUnits = $property->units()->count();
        $occupiedUnits = $property->units()->whereHas('activeContract')->count();
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        return $schema
            ->columns(2)
            ->components([
                // معلومات العقار الأساسية
                Section::make('معلومات العقار')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم العقار')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('owner.name')
                                    ->label('المالك')
                                    ->icon('heroicon-o-user')
                                    ->color('primary'),
                                TextEntry::make('propertyType.name')
                                    ->label('نوع العقار')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('propertyStatus.name')
                                    ->label('حالة العقار')
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ])
                    ->columnSpan(1),

                // إحصائيات الإشغال
                Section::make('إحصائيات الإشغال')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_units')
                                    ->label('إجمالي الوحدات')
                                    ->state($totalUnits)
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('occupied_units')
                                    ->label('وحدات مشغولة')
                                    ->state($occupiedUnits)
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('vacant_units')
                                    ->label('وحدات شاغرة')
                                    ->state($vacantUnits)
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('occupancy_rate')
                                    ->label('نسبة الإشغال')
                                    ->state($occupancyRate.'%')
                                    ->badge()
                                    ->color(fn () => match (true) {
                                        $occupancyRate >= 80 => 'success',
                                        $occupancyRate >= 50 => 'warning',
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
                                TextEntry::make('collection_total')
                                    ->label('إجمالي التحصيل')
                                    ->state(number_format($reportData['collectionTotal'], 2).' ر.س')
                                    ->color('success')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('supply_total')
                                    ->label('إجمالي التوريد')
                                    ->state(number_format($reportData['supplyTotal'], 2).' ر.س')
                                    ->color('info')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('next_collection')
                                    ->label('الدفعة القادمة')
                                    ->state(number_format($reportData['generalReport']['next_collection'], 2).' ر.س')
                                    ->color('warning'),
                                TextEntry::make('commission_rate')
                                    ->label('نسبة الإدارة')
                                    ->state($reportData['commissionRate'].'%')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->columnSpanFull(),

                // معلومات الموقع
                Section::make('معلومات الموقع')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('location.name')
                                    ->label('الموقع')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('address')
                                    ->label('العنوان')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('postal_code')
                                    ->label('الرمز البريدي')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('build_year')
                                    ->label('سنة البناء')
                                    ->placeholder('غير محدد'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),

                // تفاصيل إضافية
                Section::make('تفاصيل إضافية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('floors_count')
                                    ->label('عدد الطوابق')
                                    ->placeholder('-'),
                                TextEntry::make('parking_spots')
                                    ->label('عدد المواقف')
                                    ->placeholder('-'),
                                TextEntry::make('elevators')
                                    ->label('عدد المصاعد')
                                    ->placeholder('-'),
                                TextEntry::make('notes')
                                    ->label('ملاحظات')
                                    ->placeholder('لا توجد ملاحظات'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('العقارات')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(PropertyResource::getUrl('index')),
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('طباعة تقرير العقار')
                ->modalContent(function () {
                    $reportData = $this->getReportData();

                    return view('filament.resources.property-resource.pages.print-property', $reportData);
                })
                ->modalWidth('5xl')
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
        $property = $this->record;

        // إجمالي التحصيل - من دفعات التحصيل المحصلة
        $collectionTotal = CollectionPayment::where('property_id', $property->id)
            ->collectedPayments()
            ->sum('total_amount');

        // إجمالي التوريد - من دفعات التوريد المدفوعة
        $supplyTotal = SupplyPayment::whereHas('propertyContract', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })->collected()->sum('net_amount');

        // التقرير العام
        $nextPayment = CollectionPayment::where('property_id', $property->id)
            ->whereNull('collection_date')
            ->orderBy('due_date_start')
            ->first();

        $generalReport = [
            'property_name' => $property->name,
            'owner_name' => $property->owner?->name ?? 'غير محدد',
            'units_count' => $property->units()->count(),
            'property_status' => $property->propertyStatus?->name ?? 'غير محدد',
            'collected_rent' => $collectionTotal,
            'next_collection' => $nextPayment?->total_amount ?? 0,
            'next_collection_date' => $nextPayment?->due_date_start,
        ];

        // تقرير العمليات
        $operationsReport = [];

        // جمع عمليات التحصيل
        $collections = CollectionPayment::where('property_id', $property->id)
            ->collectedPayments()
            ->get();

        foreach ($collections as $payment) {
            $operationsReport[] = [
                'name' => 'تحصيل إيجار - '.($payment->unit?->name ?? 'وحدة'),
                'type' => 'تحصيل',
                'amount' => $payment->total_amount,
            ];
        }

        // جمع عمليات التوريد
        $supplies = SupplyPayment::whereHas('propertyContract', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })->collected()->get();

        foreach ($supplies as $payment) {
            $operationsReport[] = [
                'name' => 'توريد للمالك',
                'type' => 'توريد',
                'amount' => $payment->net_amount,
            ];
        }

        $operationsTotal = $collectionTotal - $supplyTotal;

        // التقرير التفصيلي - حسب الوحدات
        $detailedData = [];
        $totals = [
            'amount' => 0,
            'admin_fee' => 0,
            'maintenance' => 0,
            'net' => 0,
        ];

        // الحصول على نسبة العمولة من العقد النشط
        $activeContract = $property->contracts()->where('contract_status', 'active')->first();
        $commissionRate = $activeContract?->commission_rate ?? 5;

        // جمع بيانات الدفعات حسب الوحدات
        $payments = CollectionPayment::where('property_id', $property->id)
            ->collectedPayments()
            ->with(['unit', 'tenant'])
            ->get();

        foreach ($payments as $payment) {
            $amount = $payment->total_amount;
            $adminFee = $amount * ($commissionRate / 100);
            $maintenance = 0; // يمكن إضافة الصيانة لاحقاً
            $net = $amount - $adminFee - $maintenance;

            $detailedData[] = [
                'unit_number' => $payment->unit?->name ?? '-',
                'tenant_name' => $payment->tenant?->name ?? '-',
                'total_payments' => 1,
                'payment_date' => $payment->collection_date,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'maintenance' => $maintenance,
                'net' => $net,
            ];

            $totals['amount'] += $amount;
            $totals['admin_fee'] += $adminFee;
            $totals['maintenance'] += $maintenance;
            $totals['net'] += $net;
        }

        $detailedReport = [
            'data' => $detailedData,
            'totals' => $totals,
        ];

        return [
            'record' => $property,
            'collectionTotal' => $collectionTotal,
            'supplyTotal' => $supplyTotal,
            'generalReport' => $generalReport,
            'operationsReport' => $operationsReport,
            'operationsTotal' => $operationsTotal,
            'detailedReport' => $detailedReport,
            'commissionRate' => $commissionRate,
        ];
    }
}

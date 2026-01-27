<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected static ?string $title = 'عرض المستأجر';

    public function infolist(Schema $schema): Schema
    {
        $tenant = $this->record;

        $activeContract = \App\Models\UnitContract::where('tenant_id', $tenant->id)
            ->where('contract_status', 'active')
            ->with(['unit.property'])
            ->first();

        return $schema
            ->schema([
                Section::make('معلومات المستأجر')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم المستأجر')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('phone')
                                    ->label('رقم الهاتف')
                                    ->icon('heroicon-o-phone')
                                    ->color('primary'),
                                TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->icon('heroicon-o-envelope')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('national_id')
                                    ->label('رقم الهوية')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('occupation')
                                    ->label('المهنة')
                                    ->placeholder('غير محددة'),
                                TextEntry::make('employer')
                                    ->label('جهة العمل')
                                    ->placeholder('غير محددة'),
                            ]),
                    ]),

                Section::make('معلومات العقد الحالي')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('current_property')
                                    ->label('العقار الحالي')
                                    ->state($activeContract ? $activeContract->unit->property->name : 'لا يوجد عقد نشط')
                                    ->badge()
                                    ->color($activeContract ? 'success' : 'gray'),
                                TextEntry::make('current_unit')
                                    ->label('الوحدة')
                                    ->state($activeContract ? $activeContract->unit->name : '-')
                                    ->icon('heroicon-o-home'),
                                TextEntry::make('monthly_rent')
                                    ->label('الإيجار الشهري')
                                    ->state($activeContract ? number_format($activeContract->monthly_rent, 2).' ريال' : '-')
                                    ->color('warning')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('contract_start')
                                    ->label('بداية العقد')
                                    ->state($activeContract ? $activeContract->start_date : '-')
                                    ->date('Y-m-d'),
                                TextEntry::make('contract_end')
                                    ->label('نهاية العقد')
                                    ->state($activeContract ? $activeContract->end_date : '-')
                                    ->date('Y-m-d'),
                                TextEntry::make('remaining_days')
                                    ->label('الأيام المتبقية')
                                    ->state(function () use ($activeContract) {
                                        if (! $activeContract) {
                                            return '-';
                                        }
                                        $days = now()->diffInDays($activeContract->end_date, false);

                                        return $days > 0 ? $days.' يوم' : 'منتهي';
                                    })
                                    ->badge()
                                    ->color(function () use ($activeContract) {
                                        if (! $activeContract) {
                                            return 'gray';
                                        }
                                        $days = now()->diffInDays($activeContract->end_date, false);
                                        if ($days <= 0) {
                                            return 'danger';
                                        }
                                        if ($days <= 30) {
                                            return 'warning';
                                        }

                                        return 'success';
                                    }),
                            ]),
                    ])
                    ->visible($activeContract !== null),

                Section::make('الإحصائيات المالية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_paid')
                                    ->label('إجمالي المدفوع')
                                    ->state(function () use ($tenant) {
                                        $total = \App\Models\CollectionPayment::where('tenant_id', $tenant->id)
                                            ->collectedPayments()
                                            ->sum('total_amount');

                                        return number_format($total, 2).' ريال';
                                    })
                                    ->color('success')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('pending_payments')
                                    ->label('مدفوعات مستحقة')
                                    ->state(function () use ($tenant) {
                                        $total = \App\Models\CollectionPayment::where('tenant_id', $tenant->id)
                                            ->dueForCollection()
                                            ->sum('total_amount');

                                        return number_format($total, 2).' ريال';
                                    })
                                    ->color('warning'),
                                TextEntry::make('overdue_payments')
                                    ->label('مدفوعات متأخرة')
                                    ->state(function () use ($tenant) {
                                        $total = \App\Models\CollectionPayment::where('tenant_id', $tenant->id)
                                            ->overduePayments()
                                            ->sum('total_amount');

                                        return number_format($total, 2).' ريال';
                                    })
                                    ->color('danger')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('payment_count')
                                    ->label('عدد الدفعات')
                                    ->state(function () use ($tenant) {
                                        return \App\Models\CollectionPayment::where('tenant_id', $tenant->id)->count();
                                    })
                                    ->badge()
                                    ->color('primary'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\TenantResource\RelationManagers\ContractsRelationManager::class,
            \App\Filament\Resources\TenantResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }
}

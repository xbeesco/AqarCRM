<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewUnit extends ViewRecord
{
    protected static string $resource = UnitResource::class;

    protected static ?string $title = 'تقرير الوحدة';

    public function infolist(Schema $schema): Schema
    {
        $unit = $this->record;

        return $schema
            ->columns(2)
            ->components([
                // معلومات الوحدة الأساسية
                Section::make('معلومات الوحدة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم الوحدة')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('property.name')
                                    ->label('العقار')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('unitType.name')
                                    ->label('نوع الوحدة')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('unitCategory.name')
                                    ->label('تصنيف الوحدة')
                                    ->placeholder('غير محدد'),
                            ]),
                    ])
                    ->columnSpan(1),

                // حالة الإشغال
                Section::make('حالة الإشغال')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('occupancy_status')
                                    ->label('حالة الوحدة')
                                    ->state(fn () => $unit->occupancy_status->label())
                                    ->badge()
                                    ->color(fn () => $unit->occupancy_status->color()),
                                TextEntry::make('current_tenant')
                                    ->label('المستأجر الحالي')
                                    ->state(fn () => $unit->activeContract?->tenant?->name ?? 'لا يوجد')
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('monthly_rent')
                                    ->label('الإيجار الشهري الحالي')
                                    ->state(fn () => $unit->activeContract ? number_format($unit->activeContract->monthly_rent, 2).' ر.س' : '-')
                                    ->color('success')
                                    ->weight(FontWeight::Bold),
                            ]),
                    ])
                    ->columnSpan(1),

                // مواصفات الوحدة
                Section::make('مواصفات الوحدة')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('rent_price')
                                    ->label('سعر الإيجار')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2).' ر.س' : '-')
                                    ->color('warning')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('area_sqm')
                                    ->label('المساحة')
                                    ->formatStateUsing(fn ($state) => $state ? $state.' م²' : '-'),
                                TextEntry::make('floor_number')
                                    ->label('رقم الطابق')
                                    ->placeholder('-'),
                                TextEntry::make('rooms_count')
                                    ->label('عدد الغرف')
                                    ->placeholder('-'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('bathrooms_count')
                                    ->label('عدد دورات المياه')
                                    ->placeholder('-'),
                                TextEntry::make('balconies_count')
                                    ->label('عدد الشرفات')
                                    ->placeholder('-'),
                                TextEntry::make('has_laundry_room')
                                    ->label('غرفة غسيل')
                                    ->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('has_maid_room')
                                    ->label('غرفة خادمة')
                                    ->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),
                    ])
                    ->columnSpan(1),

                // معلومات الخدمات
                Section::make('معلومات الخدمات')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('electricity_account_number')
                                    ->label('رقم حساب الكهرباء')
                                    ->icon('heroicon-o-bolt')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('water_meter_number')
                                    ->label('رقم عداد المياه')
                                    ->icon('heroicon-o-beaker')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('water_expenses')
                                    ->label('مصروف المياه')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2).' ر.س' : '-')
                                    ->color('info'),
                            ]),
                    ])
                    ->columnSpan(1),

                // معلومات العقار
                Section::make('معلومات العقار')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('property.owner.name')
                                    ->label('المالك')
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('property.location.name')
                                    ->label('الموقع')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('property.propertyType.name')
                                    ->label('نوع العقار')
                                    ->placeholder('غير محدد'),
                                TextEntry::make('property.address')
                                    ->label('العنوان')
                                    ->placeholder('غير محدد'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),

                // ملاحظات
                Section::make('ملاحظات')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn () => ! empty($unit->notes))
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('الوحدات')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(UnitResource::getUrl('index')),
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('طباعة تقرير الوحدة')
                ->modalContent(function () {
                    $unit = $this->record;
                    $specifications = $this->getSpecifications();

                    return view('filament.resources.unit-resource.pages.print-unit', [
                        'unit' => $unit,
                        'specifications' => $specifications,
                    ]);
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

    protected function getSpecifications(): array
    {
        $unit = $this->record;
        $specifications = [];

        if ($unit->rent_price) {
            $specifications[] = ['label' => 'سعر الإيجار', 'value' => number_format($unit->rent_price).' ر.س'];
        }

        if ($unit->area_sqm) {
            $specifications[] = ['label' => 'المساحة', 'value' => $unit->area_sqm.' م²'];
        }

        if ($unit->floor_number !== null) {
            $specifications[] = ['label' => 'رقم الطابق', 'value' => $unit->floor_number];
        }

        if ($unit->rooms_count) {
            $specifications[] = ['label' => 'عدد الغرف', 'value' => $unit->rooms_count];
        }

        if ($unit->bathrooms_count) {
            $specifications[] = ['label' => 'عدد دورات المياه', 'value' => $unit->bathrooms_count];
        }

        if ($unit->balconies_count) {
            $specifications[] = ['label' => 'عدد الشرفات', 'value' => $unit->balconies_count];
        }

        if ($unit->has_laundry_room) {
            $specifications[] = ['label' => 'غرفة غسيل', 'value' => 'نعم'];
        }

        if ($unit->electricity_account_number) {
            $specifications[] = ['label' => 'رقم حساب الكهرباء', 'value' => $unit->electricity_account_number];
        }

        if ($unit->water_expenses) {
            $specifications[] = ['label' => 'مصروف المياه', 'value' => number_format($unit->water_expenses).' ر.س'];
        }

        return $specifications;
    }
}

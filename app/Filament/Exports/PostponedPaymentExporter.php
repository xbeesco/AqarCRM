<?php

namespace App\Filament\Exports;

use App\Models\CollectionPayment;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PostponedPaymentExporter extends Exporter
{
    protected static ?string $model = CollectionPayment::class;

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return '';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return '';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('payment_number')
                ->label('رقم الدفعة'),

            ExportColumn::make('tenant.name')
                ->label('المستأجر')
                ->default('غير محدد'),

            ExportColumn::make('tenant.phone')
                ->label('رقم الهاتف')
                ->default('-'),

            ExportColumn::make('property.name')
                ->label('العقار')
                ->default('غير محدد'),

            ExportColumn::make('unit.name')
                ->label('رقم الوحدة')
                ->default('-'),

            ExportColumn::make('total_amount')
                ->label('المبلغ')
                ->formatStateUsing(fn ($state) => number_format($state, 2).' ريال'),

            ExportColumn::make('due_date_start')
                ->label('تاريخ البداية')
                ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m-d')),

            ExportColumn::make('due_date_end')
                ->label('تاريخ النهاية')
                ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m-d')),

            ExportColumn::make('delay_duration')
                ->label('مدة التأجيل (أيام)')
                ->state(function ($record) {
                    return $record->delay_duration ?:
                        Carbon::parse($record->due_date_end)->diffInDays(Carbon::now());
                }),

            ExportColumn::make('delay_reason')
                ->label('سبب التأجيل')
                ->default('غير محدد'),

            ExportColumn::make('late_payment_notes')
                ->label('ملاحظات')
                ->default('-'),
        ];
    }
}

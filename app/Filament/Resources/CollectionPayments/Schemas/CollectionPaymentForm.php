<?php

namespace App\Filament\Resources\CollectionPayments\Schemas;

use App\Models\UnitContract;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CollectionPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('إضافة دفعة مستأجر')
                    ->columnSpan('full')
                    ->schema([
                        Select::make('unit_contract_id')
                            ->label('العقد')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return UnitContract::with(['tenant', 'unit', 'property'])
                                    ->get()
                                    ->mapWithKeys(function ($contract) {
                                        $label = sprintf(
                                            '%s - %s - %s',
                                            $contract->tenant?->name ?? 'غير محدد',
                                            $contract->unit?->name ?? 'غير محدد',
                                            $contract->property?->name ?? 'غير محدد'
                                        );

                                        return [$contract->id => $label];
                                    });
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $contract = UnitContract::find($state);
                                    if ($contract) {
                                        $set('unit_id', $contract->unit_id);
                                        $set('property_id', $contract->property_id);
                                        $set('tenant_id', $contract->tenant_id);
                                        $set('amount', $contract->monthly_rent ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(6),

                        TextInput::make('amount')
                            ->label('القيمة المالية')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->postfix('ريال')
                            ->columnSpan(6),

                        DatePicker::make('due_date_start')
                            ->label('تاريخ بداية الاستحقاق')
                            ->required()
                            ->columnSpan(6)
                            ->default(now()->startOfMonth()),

                        DatePicker::make('due_date_end')
                            ->label('تاريخ نهاية الاستحقاق')
                            ->required()
                            ->columnSpan(6)
                            ->default(now()->endOfMonth()),

                        DatePicker::make('collection_date')
                            ->label('تاريخ التحصيل')
                            ->columnSpan(6)
                            ->helperText('اتركه فارغاً إذا لم يتم التحصيل بعد'),

                        TextInput::make('delay_duration')
                            ->label('مدة التأجيل بالأيام')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('يوم')
                            ->columnSpan(3)
                            ->helperText('0 أو فارغ = لا يوجد تأجيل'),

                        TextInput::make('delay_reason')
                            ->label('سبب التأجيل')
                            ->columnSpan(3)
                            ->visible(fn ($get) => $get('delay_duration') > 0),

                        Textarea::make('late_payment_notes')
                            ->label('ملاحظات')
                            ->columnSpan(6)
                            ->rows(2),

                        Hidden::make('unit_id'),
                        Hidden::make('property_id'),
                        Hidden::make('tenant_id'),
                    ])->columns(12),
            ]);
    }
}

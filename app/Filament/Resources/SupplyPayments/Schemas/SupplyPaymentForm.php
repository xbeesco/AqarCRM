<?php

namespace App\Filament\Resources\SupplyPayments\Schemas;

use App\Models\PropertyContract;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplyPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('إضافة دفعة مالك')
                ->columnSpan('full')
                ->schema([
                    Select::make('property_contract_id')
                        ->label('العقد')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return PropertyContract::with(['property', 'owner'])
                                ->get()
                                ->mapWithKeys(function ($contract) {
                                    $label = sprintf(
                                        'عقد: %s | عقار: %s | المالك: %s',
                                        $contract->contract_number ?: 'بدون رقم',
                                        $contract->property?->name ?? 'غير محدد',
                                        $contract->owner?->name ?? 'غير محدد'
                                    );

                                    return [$contract->id => $label];
                                });
                        })
                        ->columnSpan(['lg' => 2, 'xl' => 3]),

                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->required()
                        ->default(now()->addDays(7))
                        ->columnSpan(['lg' => 1, 'xl' => 1]),

                    DatePicker::make('paid_date')
                        ->label('تاريخ التوريد')
                        ->helperText('اتركه فارغاً إذا لم يتم التوريد بعد')
                        ->columnSpan(['lg' => 1, 'xl' => 1]),

                    Placeholder::make('approval_section')
                        ->label('إقرار ما بعد التوريد')
                        ->content('')
                        ->visible(fn ($get) => $get('paid_date') !== null)
                        ->columnSpan(['lg' => 3, 'xl' => 4]),

                    Radio::make('approval_status')
                        ->label('أقر')
                        ->options([
                            'approved' => 'موافق',
                            'rejected' => 'غير موافق',
                        ])
                        ->inline()
                        ->visible(fn ($get) => $get('paid_date') !== null)
                        ->required(fn ($get) => $get('paid_date') !== null)
                        ->columnSpan(['lg' => 3, 'xl' => 4]),

                    Hidden::make('owner_id'),
                    Hidden::make('payment_number'),
                ])->columns([
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 3,
                    'xl' => 4,
                ]),
        ]);
    }
}

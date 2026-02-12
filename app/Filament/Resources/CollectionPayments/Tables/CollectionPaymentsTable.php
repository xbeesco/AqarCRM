<?php

namespace App\Filament\Resources\CollectionPayments\Tables;

use App\Enums\PaymentStatus;
use App\Models\CollectionPayment;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Services\CollectionPaymentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CollectionPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_date_start', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['tenant', 'unit', 'property', 'unitContract']);
            })
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('due_date_start')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('القيمة')
                    ->money('SAR')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_status_label')
                    ->label('الحالة')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->payment_status_label)
                    ->color(fn ($record): string => $record->payment_status_color),

                TextColumn::make('delay_duration')
                    ->label('ملاحظات')
                    ->formatStateUsing(function ($record) {
                        if ($record->delay_duration && $record->delay_duration > 0) {
                            $text = $record->delay_duration.' يوم';
                            if ($record->delay_reason) {
                                $text .= ' - السبب: '.$record->delay_reason;
                            }

                            return $text;
                        }

                        return '';
                    })
                    ->placeholder('')
                    ->wrap(),
            ])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->options(function () {
                        return User::where('type', 'owner')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->whereHas('property', function ($q) use ($data) {
                                $q->where('owner_id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $owner = User::find($data['value']);

                            return $owner ? 'المالك: '.$owner->name : null;
                        }

                        return null;
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('property_and_unit')
                    ->label('العقار والوحدة')
                    ->schema([
                        Select::make('property_id')
                            ->label('العقار')
                            ->options(Property::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('unit_id', null)),

                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->native(true)
                            ->placeholder('جميع الوحدات')
                            ->options(function ($get) {
                                $propertyId = $get('property_id');
                                if (! $propertyId) {
                                    return [];
                                }

                                return Unit::where('property_id', $propertyId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->visible(fn ($get) => (bool) $get('property_id')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['property_id'])) {
                            $query->where('property_id', $data['property_id']);
                        }
                        if (! empty($data['unit_id'])) {
                            $query->where('unit_id', $data['unit_id']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['property_id'])) {
                            $property = Property::find($data['property_id']);
                            if ($property) {
                                if (! empty($data['unit_id'])) {
                                    $unit = Unit::find($data['unit_id']);
                                    if ($unit) {
                                        $indicators['filter'] = 'العقار: '.$property->name.' - الوحدة: '.$unit->name;
                                    }
                                } else {
                                    $indicators['filter'] = 'العقار: '.$property->name;
                                }
                            }
                        }

                        return $indicators;
                    }),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->relationship('tenant', 'name', fn ($query) => $query->where('type', 'tenant'))
                    ->searchable()
                    ->preload()
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $tenant = User::find($data['value']);

                            return $tenant ? 'المستأجر: '.$tenant->name : null;
                        }

                        return null;
                    }),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفعة')
                    ->options(PaymentStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            $status = PaymentStatus::from($data['value']);

                            return $query->byStatus($status);
                        }

                        return $query;
                    }),

                SelectFilter::make('unit_contract_id')
                    ->label('العقد')
                    ->relationship('unitContract', 'contract_number')
                    ->searchable()
                    ->preload()
                    ->hidden(),

                TernaryFilter::make('is_collected')
                    ->label('تم التحصيل')
                    ->placeholder('الكل')
                    ->trueLabel('تم التحصيل')
                    ->falseLabel('لم يتم التحصيل')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('collection_date'),
                        false: fn (Builder $query) => $query->whereNull('collection_date'),
                    ),

                TernaryFilter::make('has_notes')
                    ->label('يوجد ملاحظات')
                    ->placeholder('الكل')
                    ->trueLabel('يوجد')
                    ->falseLabel('لا يوجد')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('late_payment_notes')->where('late_payment_notes', '!=', ''),
                        false: fn (Builder $query) => $query->where(fn ($q) => $q->whereNull('late_payment_notes')->orWhere('late_payment_notes', '')),
                    ),

                Filter::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->schema([
                        DatePicker::make('due_from')->label('من تاريخ الاستحقاق'),
                        DatePicker::make('due_until')->label('إلى تاريخ الاستحقاق'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date_start', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date_start', '<=', $date),
                            );
                    }),

                Filter::make('collection_date')
                    ->label('تاريخ التحصيل')
                    ->schema([
                        DatePicker::make('collected_from')->label('من تاريخ التحصيل'),
                        DatePicker::make('collected_until')->label('إلى تاريخ التحصيل'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['collected_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('collection_date', '>=', $date),
                            )
                            ->when(
                                $data['collected_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('collection_date', '<=', $date),
                            );
                    }),

                Filter::make('amount_range')
                    ->label('نطاق المبلغ')
                    ->schema([
                        TextInput::make('min_amount')
                            ->label('الحد الأدنى للمبلغ')
                            ->numeric(),
                        TextInput::make('max_amount')
                            ->label('الحد الأقصى للمبلغ')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),

                SelectFilter::make('collected_by')
                    ->label('المحصل')
                    ->relationship('collectedBy', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::Modal)
            ->recordActions([
                ActionGroup::make([
                    Action::make('postpone_payment')
                        ->label('تأجيل')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->schema([
                            TextInput::make('delay_duration')
                                ->label('مدة التأجيل (بالأيام)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(90)
                                ->default(7)
                                ->suffix('يوم'),

                            Textarea::make('delay_reason')
                                ->label('سبب التأجيل')
                                ->required()
                                ->rows(3)
                                ->placeholder('اذكر سبب التأجيل...'),
                        ])
                        ->modalHeading('تأجيل الدفعة')
                        ->modalSubmitActionLabel('تأجيل')
                        ->modalIcon('heroicon-o-clock')
                        ->modalIconColor('warning')
                        ->visible(
                            fn (CollectionPayment $record) => ! $record->collection_date &&
                                (! $record->delay_duration || $record->delay_duration == 0)
                        )
                        ->action(function (CollectionPayment $record, array $data) {
                            app(CollectionPaymentService::class)->postponePayment(
                                $record,
                                $data['delay_duration'],
                                $data['delay_reason']
                            );

                            Notification::make()
                                ->title('تم تأجيل الدفعة')
                                ->body("تم تأجيل الدفعة لمدة {$data['delay_duration']} يوم")
                                ->warning()
                                ->send();
                        }),

                    Action::make('confirm_payment')
                        ->label('تأكيد الاستلام')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد استلام الدفعة')
                        ->modalDescription(function (CollectionPayment $record) {
                            $userName = auth()->user()->name;
                            $paymentNumber = $record->payment_number;
                            $tenantName = $record->tenant?->name ?? 'المستأجر';
                            $amount = number_format($record->total_amount, 2);
                            $propertyName = $record->property?->name ?? 'غير محدد';
                            $unitName = $record->unit?->name ?? 'غير محدد';

                            return new HtmlString(
                                "<div style='text-align: right; direction: rtl;'>
                                <p>أقر أنا <strong>{$userName}</strong> باستلام:</p>
                                <p>الدفعة رقم: <strong>{$paymentNumber}</strong></p>
                                <p>المبلغ: <strong style='color: green;'>{$amount} ريال</strong></p>
                                <p>من المستأجر: <strong>{$tenantName}</strong></p>
                                <p>العقار: <strong>{$propertyName}</strong></p>
                                <p>الوحدة: <strong>{$unitName}</strong></p>
                            </div>"
                            );
                        })
                        ->modalSubmitActionLabel('تأكيد الاستلام')
                        ->modalIcon('heroicon-o-check-circle')
                        ->modalIconColor('success')
                        ->visible(
                            fn (CollectionPayment $record) => ! $record->collection_date
                        )
                        ->action(function (CollectionPayment $record) {
                            app(CollectionPaymentService::class)->markAsCollected($record, auth()->id());

                            Notification::make()
                                ->title('تم تأكيد الاستلام')
                                ->body('تم تسجيل استلام الدفعة بنجاح')
                                ->success()
                                ->send();
                        }),
                ])->label('More actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(Size::Small)
                    ->color('primary')
                    ->button(),

            ])
            ->toolbarActions([])
            ->searchable([
                'payment_number',
                'amount',
                'delay_reason',
                'late_payment_notes',
                'tenant.name',
                'tenant.phone',
                'unit.name',
                'property.name',
                'property.address',
            ]);
    }
}

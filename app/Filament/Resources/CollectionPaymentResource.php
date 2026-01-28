<?php

namespace App\Filament\Resources;

use App\Models\UnitContract;
use Filament\Forms\Components\Hidden;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\HtmlString;
use App\Filament\Resources\CollectionPaymentResource\Pages\ListCollectionPayments;
use App\Filament\Resources\CollectionPaymentResource\Pages\CreateCollectionPayment;
use App\Filament\Resources\CollectionPaymentResource\Pages\ViewCollectionPayment;
use App\Filament\Resources\CollectionPaymentResource\Pages\EditCollectionPayment;
use Illuminate\Support\Collection;
use ValueError;
use Filament\GlobalSearch\GlobalSearchResult;
use App\Enums\PaymentStatus;
use App\Filament\Resources\CollectionPaymentResource\Pages;
use App\Models\CollectionPayment;
use App\Services\CollectionPaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CollectionPaymentResource extends Resource
{
    protected static ?string $model = CollectionPayment::class;

    protected static ?string $navigationLabel = 'دفعات المستأجرين';

    protected static ?string $modelLabel = 'دفعة مستأجر';

    protected static ?string $pluralModelLabel = 'دفعات المستأجرين';

    protected static ?string $recordTitleAttribute = 'payment_number';
    // Navigation properties removed - managed centrally in AdminPanelProvider

    public static function form(Schema $schema): Schema
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

    public static function table(Table $table): Table
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
            ->filters([
                Filter::make('property_and_unit')
                    ->label('العقار والوحدة')
                    ->schema([
                        Grid::make(2)->schema([
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
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['property_id'])) {
                            $query->where('property_id', $data['property_id']);
                        }
                        if (! empty($data['unit_id'])) {
                            $query->where('unit_id', $data['unit_id']);
                        }

                        return $query;
                    }),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->relationship('tenant', 'name', fn ($query) => $query->where('type', 'tenant'))
                    ->searchable()
                    ->preload(),

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
            ], layout: FiltersLayout::BeforeContent)
            ->recordActions([
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

            ])
            ->toolbarActions([
                // Bulk actions here
            ])
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

    public static function getPages(): array
    {
        return [
            'index' => ListCollectionPayments::route('/'),
            'create' => CreateCollectionPayment::route('/create'),
            'view' => ViewCollectionPayment::route('/{record}'),
            'edit' => EditCollectionPayment::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'payment_number',
            'amount',
            'collection_date',
            'due_date_start',
            'due_date_end',
            'paid_date',
            'delay_duration',
            'delay_reason',
            'late_payment_notes',
            'payment_reference',
            'receipt_number',
            'month_year',
            'tenant.name',
            'tenant.phone',
            'tenant.email',
            'unit.name',
            'property.name',
            'property.address',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['tenant', 'unit', 'property']);
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        $search = trim($search);

        // Normalize Arabic search - remove hamzat and taa marbuta variations
        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
        $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);

        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);

        return static::getGlobalSearchEloquentQuery()
            ->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                // Search in payment number and references
                $query->where('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('payment_reference', 'LIKE', "%{$search}%")
                    ->orWhere('receipt_number', 'LIKE', "%{$search}%")
                    ->orWhere('month_year', 'LIKE', "%{$search}%");

                // Search by payment status
                $statusSearch = PaymentStatus::options();
                foreach ($statusSearch as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        try {
                            $status = PaymentStatus::from($key);
                            $query->orWhere(function ($statusQuery) use ($status) {
                                (new CollectionPayment)->scopeByStatus($statusQuery, $status);
                            });
                        } catch (ValueError $e) {
                            // Skip invalid values
                        }
                    }
                }

                // Search by amount
                if (is_numeric($search)) {
                    $query->orWhere('amount', 'LIKE', "%{$search}%")
                        ->orWhere('amount', $search);
                }

                // Search in notes
                $query->orWhere('delay_reason', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('late_payment_notes', 'LIKE', "%{$normalizedSearch}%");

                // Search by delay duration
                if (is_numeric($search)) {
                    $query->orWhere('delay_duration', $search)
                        ->orWhere('delay_duration', 'LIKE', "%{$search}%");
                }

                // Search in dates
                $query->orWhere('collection_date', 'LIKE', "%{$search}%")
                    ->orWhere('due_date_start', 'LIKE', "%{$search}%")
                    ->orWhere('due_date_end', 'LIKE', "%{$search}%")
                    ->orWhere('paid_date', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%");

                // Search by year only
                if (preg_match('/^\d{4}$/', $search)) {
                    $query->orWhereYear('collection_date', $search)
                        ->orWhereYear('due_date_start', $search)
                        ->orWhereYear('due_date_end', $search)
                        ->orWhereYear('paid_date', $search)
                        ->orWhereYear('created_at', $search);
                }

                // Search by month and year
                if (preg_match('/^\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $month = $parts[0];
                    $year = $parts[1];
                    $query->orWhereMonth('collection_date', $month)->whereYear('collection_date', $year)
                        ->orWhereMonth('due_date_start', $month)->whereYear('due_date_start', $year)
                        ->orWhereMonth('due_date_end', $month)->whereYear('due_date_end', $year);
                }

                // Search in tenant
                $query->orWhereHas('tenant', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where(function ($subQuery) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                        $subQuery->where('name', 'LIKE', "%{$normalizedSearch}%")
                            ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$searchWithoutSpaces}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                });

                // Search in unit
                $query->orWhereHas('unit', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");

                    if (is_numeric($search)) {
                        $q->orWhere('floor_number', $search)
                            ->orWhere('rooms_count', $search);
                    }
                });

                // Search in property
                $query->orWhereHas('property', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('address', 'LIKE', "%{$normalizedSearch}%");
                });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $tenant = $record->tenant?->name ?? 'غير محدد';
                $unit = $record->unit?->name ?? 'غير محدد';
                $property = $record->property?->name ?? 'غير محدد';

                return new GlobalSearchResult(
                    title: $record->payment_number,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'المستأجر' => $tenant,
                        'الوحدة' => $unit,
                        'العقار' => $property,
                        'المبلغ' => number_format($record->amount, 2).' SAR',
                        'الحالة' => $record->payment_status_label,
                    ]
                );
            });
    }
}

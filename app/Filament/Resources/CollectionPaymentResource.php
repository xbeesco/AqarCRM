<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionPaymentResource\Pages;
use App\Models\CollectionPayment;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Helpers\DateHelper;
use App\Enums\PaymentStatus;

class CollectionPaymentResource extends Resource
{
    protected static ?string $model = CollectionPayment::class;

    protected static ?string $navigationLabel = 'دفعات تحصيل';

    protected static ?string $modelLabel = 'دفعة تحصيل';

    protected static ?string $pluralModelLabel = 'دفعات تحصيل';

    protected static ?string $recordTitleAttribute = 'payment_number';
    // Navigation properties removed - managed centrally in AdminPanelProvider

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('إضافة دفعة تحصيل')
                    ->columnSpan('full')
                    ->schema([
                        // العقد
                        Select::make('unit_contract_id')
                            ->label('العقد')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return \App\Models\UnitContract::with(['tenant', 'unit', 'property'])
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
                                    $contract = \App\Models\UnitContract::find($state);
                                    if ($contract) {
                                        $set('unit_id', $contract->unit_id);
                                        $set('property_id', $contract->property_id);
                                        $set('tenant_id', $contract->tenant_id);
                                        $set('amount', $contract->monthly_rent ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(6),
                        // القيمة المالية
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

                        // Hidden fields للحفظ
                        \Filament\Forms\Components\Hidden::make('unit_id'),
                        \Filament\Forms\Components\Hidden::make('property_id'),
                        \Filament\Forms\Components\Hidden::make('tenant_id'),
                    ])->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date_start', 'asc')
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
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
                    ->date('d/m/Y')
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
                            $text = $record->delay_duration . ' يوم';
                            if ($record->delay_reason) {
                                $text .= ' - السبب: ' . $record->delay_reason;
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
                    ->form([
                        Grid::make(2)->schema([
                            Select::make('property_id')
                                ->label('العقار')
                                ->options(\App\Models\Property::pluck('name', 'id'))
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
                                    if (!$propertyId) {
                                        return [];
                                    }
                                    return \App\Models\Unit::where('property_id', $propertyId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->visible(fn ($get) => (bool)$get('property_id')),
                        ]),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        if (!empty($data['property_id'])) {
                            $query->where('property_id', $data['property_id']);
                        }
                        if (!empty($data['unit_id'])) {
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
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        if (!empty($data['value'])) {
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
                    ->hidden(), // مخفي افتراضياً، يظهر فقط عند الحاجة
            ])
            ->recordActions([
                Action::make('postpone_payment')
                    ->label('تأجيل')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->form([
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
                            ->placeholder('اذكر سبب التأجيل...')
                    ])
                    ->modalHeading('تأجيل الدفعة')
                    ->modalSubmitActionLabel('تأجيل')
                    ->modalIcon('heroicon-o-clock')
                    ->modalIconColor('warning')
                    ->visible(fn (CollectionPayment $record) => 
                        !$record->collection_date && 
                        (!$record->delay_duration || $record->delay_duration == 0)
                    )
                    ->action(function (CollectionPayment $record, array $data) {
                        $record->postpone($data['delay_duration'], $data['delay_reason']);
                        
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
                    ->modalDescription(fn (CollectionPayment $record) => 
                        "أقر أنا " . auth()->user()->name . " باستلام الدفعة رقم " . 
                        $record->payment_number . " من " . 
                        ($record->tenant?->name ?? 'المستأجر')
                    )
                    ->modalSubmitActionLabel('تأكيد الاستلام')
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalIconColor('success')
                    ->visible(fn (CollectionPayment $record) => 
                        !$record->collection_date
                    )
                    ->action(function (CollectionPayment $record) {
                        $record->markAsCollected();
                        
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
            'index' => Pages\ListCollectionPayments::route('/'),
            'create' => Pages\CreateCollectionPayment::route('/create'),
            'view' => Pages\ViewCollectionPayment::route('/{record}'),
            'edit' => Pages\EditCollectionPayment::route('/{record}/edit'),
        ];
    }

    // البحث الذكي الشامل
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

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['tenant', 'unit', 'property']);
    }

    public static function getGlobalSearchResults(string $search): \Illuminate\Support\Collection
    {
        $search = trim($search);

        // تطبيع البحث العربي - إزالة الهمزات والتاء المربوطة
        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
        $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);

        // إزالة المسافات الزائدة
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);

        return static::getGlobalSearchEloquentQuery()
            ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                // البحث في رقم الدفعة والمراجع
                $query->where('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('payment_reference', 'LIKE', "%{$search}%")
                    ->orWhere('receipt_number', 'LIKE', "%{$search}%")
                    ->orWhere('month_year', 'LIKE', "%{$search}%");

                // البحث في حالة الدفعة - مهم جداً
                $statusSearch = PaymentStatus::options();
                foreach ($statusSearch as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        // استخدم الـ scope المناسب بدلاً من البحث في حقل
                        try {
                            $status = PaymentStatus::from($key);
                            $query->orWhere(function($statusQuery) use ($status) {
                                (new CollectionPayment())->scopeByStatus($statusQuery, $status);
                            });
                        } catch (\ValueError $e) {
                            // تجاهل القيم غير الصالحة
                        }
                    }
                }

                // البحث في المبلغ (حتى لو رقم واحد)
                if (is_numeric($search)) {
                    $query->orWhere('amount', 'LIKE', "%{$search}%")
                        ->orWhere('amount', $search);
                }

                // البحث في الملاحظات
                $query->orWhere('delay_reason', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('late_payment_notes', 'LIKE', "%{$normalizedSearch}%");

                // البحث في مدة التأجيل
                if (is_numeric($search)) {
                    $query->orWhere('delay_duration', $search)
                        ->orWhere('delay_duration', 'LIKE', "%{$search}%");
                }

                // البحث في كل التواريخ
                $query->orWhere('collection_date', 'LIKE', "%{$search}%")
                    ->orWhere('due_date_start', 'LIKE', "%{$search}%")
                    ->orWhere('due_date_end', 'LIKE', "%{$search}%")
                    ->orWhere('paid_date', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%");

                // البحث بالسنة فقط
                if (preg_match('/^\d{4}$/', $search)) {
                    $query->orWhereYear('collection_date', $search)
                        ->orWhereYear('due_date_start', $search)
                        ->orWhereYear('due_date_end', $search)
                        ->orWhereYear('paid_date', $search)
                        ->orWhereYear('created_at', $search);
                }

                // البحث بالشهر والسنة
                if (preg_match('/^\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $month = $parts[0];
                    $year = $parts[1];
                    $query->orWhereMonth('collection_date', $month)->whereYear('collection_date', $year)
                        ->orWhereMonth('due_date_start', $month)->whereYear('due_date_start', $year)
                        ->orWhereMonth('due_date_end', $month)->whereYear('due_date_end', $year);
                }

                // البحث في المستأجر - مع التطبيع
                $query->orWhereHas('tenant', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where(function ($subQuery) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                        $subQuery->where('name', 'LIKE', "%{$normalizedSearch}%")
                            ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$searchWithoutSpaces}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                });

                // البحث في الوحدة
                $query->orWhereHas('unit', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");

                    // البحث في رقم الطابق إذا كان رقم
                    if (is_numeric($search)) {
                        $q->orWhere('floor_number', $search)
                            ->orWhere('rooms_count', $search);
                    }
                });

                // البحث في العقار
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

                return new \Filament\GlobalSearch\GlobalSearchResult(
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

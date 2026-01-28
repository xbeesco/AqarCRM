<?php

namespace App\Filament\Resources;

use App\Models\PropertyContract;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use App\Models\User;
use App\Models\Property;
use App\Filament\Resources\SupplyPaymentResource\Pages\ListSupplyPayments;
use App\Filament\Resources\SupplyPaymentResource\Pages\CreateSupplyPayment;
use App\Filament\Resources\SupplyPaymentResource\Pages\ViewSupplyPayment;
use App\Filament\Resources\SupplyPaymentResource\Pages\EditSupplyPayment;
use App\Filament\Resources\SupplyPaymentResource\Pages;
use App\Models\SupplyPayment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupplyPaymentResource extends Resource
{
    protected static ?string $model = SupplyPayment::class;

    protected static ?string $navigationLabel = 'دفعات الملاك';

    protected static ?string $modelLabel = 'مالك توريد';

    protected static ?string $pluralModelLabel = 'دفعات الملاك';

    protected static ?string $recordTitleAttribute = 'payment_number';

    public static function form(Schema $schema): Schema
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['propertyContract.property.owner', 'owner']);
            })
            ->columns([
                TextColumn::make('owner.name')
                    ->label('المالك')
                    ->searchable(query: function ($query, $search) {
                        return $query->orWhereHas('owner', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })->orWhereHas('propertyContract.property.owner', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(function ($record) {
                        return $record->owner?->name ??
                               $record->propertyContract?->property?->owner?->name ??
                               'غير محدد';
                    }),

                TextColumn::make('propertyContract.property.name')
                    ->label('العقار')
                    ->searchable(query: function ($query, $search) {
                        return $query->orWhereHas('propertyContract.property', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(function ($record) {
                        return $record->propertyContract?->property?->name ?? 'غير محدد';
                    }),

                TextColumn::make('month_year')
                    ->label('الشهر'),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('net_amount')
                    ->label('القيمة')
                    ->money('SAR'),

                TextColumn::make('supply_status_label')
                    ->label('الحالة')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->supply_status_label)
                    ->color(fn ($record) => $record->supply_status_color),

                TextColumn::make('delay_reason')
                    ->label('سبب التأجيل')
                    ->placeholder('—')
                    ->visible(fn () => false)
                    ->toggleable(),

                TextColumn::make('paid_date')
                    ->label('تاريخ التوريد')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('delay_duration')
                    ->label('الملاحظات')
                    ->formatStateUsing(function ($record) {
                        if ($record->delay_duration && $record->delay_duration > 0) {
                            $text = $record->delay_duration.' يوم';
                            if ($record->delay_reason) {
                                $text .= ' - السبب: '.$record->delay_reason;
                            }

                            return $text;
                        }

                        return $record->notes ?? '';
                    })
                    ->placeholder('')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->options(function () {
                        return User::where('type', 'owner')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('property')
                    ->label('العقار')
                    ->options(function () {
                        return Property::with('owner')
                            ->get()
                            ->mapWithKeys(function ($property) {
                                return [$property->id => $property->name.' - '.($property->owner?->name ?? 'بدون مالك')];
                            });
                    })
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            return $query->whereHas('propertyContract.property', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }

                        return $query;
                    }),
            ])
            ->deferFilters();
    }

    public static function getRelations(): array
    {
        // TODO: Re-implement these as custom infolist sections instead of RelationManagers
        // The previous implementation used fake/dummy relationships which is incorrect
        // Data should be fetched via PaymentAssignmentService and SupplyPaymentService
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplyPayments::route('/'),
            'create' => CreateSupplyPayment::route('/create'),
            'view' => ViewSupplyPayment::route('/{record}'),
            'edit' => EditSupplyPayment::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'payment_number',
            'gross_amount',
            'commission_amount',
            'commission_rate',
            'maintenance_deduction',
            'other_deductions',
            'net_amount',
            'due_date',
            'paid_date',
            'bank_transfer_reference',
            'month_year',
            'notes',
            'propertyContract.contract_number',
            'propertyContract.property.name',
            'propertyContract.property.address',
            'owner.name',
            'owner.phone',
            'owner.email',
            'approver.name',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['propertyContract.property', 'owner', 'approver']);
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        $search = trim($search);

        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
        $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);

        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);

        return static::getGlobalSearchEloquentQuery()
            ->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                $query->where('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('bank_transfer_reference', 'LIKE', "%{$search}%")
                    ->orWhere('month_year', 'LIKE', "%{$search}%");

                $approvalOptions = [
                    'approved' => 'موافق',
                    'rejected' => 'غير موافق',
                ];

                foreach ($approvalOptions as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        $query->orWhere('approval_status', $key);
                    }
                }

                if (is_numeric($search)) {
                    $query->orWhere('gross_amount', 'LIKE', "%{$search}%")
                        ->orWhere('commission_amount', 'LIKE', "%{$search}%")
                        ->orWhere('commission_rate', 'LIKE', "%{$search}%")
                        ->orWhere('maintenance_deduction', 'LIKE', "%{$search}%")
                        ->orWhere('other_deductions', 'LIKE', "%{$search}%")
                        ->orWhere('net_amount', 'LIKE', "%{$search}%");
                }

                $query->orWhere('notes', 'LIKE', "%{$normalizedSearch}%");

                $query->orWhere('due_date', 'LIKE', "%{$search}%")
                    ->orWhere('paid_date', 'LIKE', "%{$search}%")
                    ->orWhere('approved_at', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%");

                if (preg_match('/^\d{4}$/', $search)) {
                    $query->orWhereYear('due_date', $search)
                        ->orWhereYear('paid_date', $search)
                        ->orWhereYear('approved_at', $search)
                        ->orWhereYear('created_at', $search);
                }

                if (preg_match('/^\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $month = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $year = $parts[1];
                    $query->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('approved_at', $month)->whereYear('approved_at', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
                    });
                }

                if (preg_match('/^\d{4}[-\/]\d{1,2}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $year = $parts[0];
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $query->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('approved_at', $month)->whereYear('approved_at', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
                    });
                }

                if (preg_match('/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $year = $parts[2];
                    $dateStr = "$year-$month-$day";

                    $query->orWhereDate('due_date', $dateStr)
                        ->orWhereDate('paid_date', $dateStr)
                        ->orWhereDate('approved_at', $dateStr)
                        ->orWhereDate('created_at', $dateStr);
                }

                if (preg_match('/^\d{1,2}[-\/]\d{1,2}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $currentYear = date('Y');

                    $query->orWhere(function ($q) use ($day, $month, $currentYear) {
                        $dateStr = "$currentYear-$month-$day";
                        $q->whereDate('due_date', $dateStr)
                            ->orWhereDate('paid_date', $dateStr)
                            ->orWhereDate('approved_at', $dateStr)
                            ->orWhereDate('created_at', $dateStr);
                    });

                    $query->orWhere(function ($q) use ($month, $currentYear) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $currentYear);
                    })->orWhere(function ($q) use ($month, $currentYear) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $currentYear);
                    });
                }

                $query->orWhereHas('propertyContract', function ($q) use ($search, $searchWithoutSpaces) {
                    $q->where('contract_number', 'LIKE', "%{$search}%")
                        ->orWhere('contract_number', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('notary_number', 'LIKE', "%{$search}%");

                    if (is_numeric($search)) {
                        $q->orWhere('commission_rate', 'LIKE', "%{$search}%")
                            ->orWhere('duration_months', $search)
                            ->orWhere('payment_day', $search);
                    }
                });

                $query->orWhereHas('propertyContract.property', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('address', 'LIKE', "%{$normalizedSearch}%");
                });

                $query->orWhereHas('owner', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });

                $query->orWhereHas('approver', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");
                });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $contract = $record->propertyContract;
                $property = $contract?->property?->name ?? 'غير محدد';
                $owner = $record->owner?->name ?? 'غير محدد';

                $statusLabel = $record->supply_status_label;

                return new GlobalSearchResult(
                    title: $record->payment_number,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'العقار' => $property,
                        'المالك' => $owner,
                        'المبلغ الصافي' => number_format($record->net_amount, 2).' SAR',
                        'الحالة' => $statusLabel,
                        'الشهر' => $record->month_year,
                    ]
                );
            });
    }
}

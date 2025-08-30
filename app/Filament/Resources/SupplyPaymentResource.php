<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplyPaymentResource\Pages;
use App\Models\SupplyPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupplyPaymentResource extends Resource
{
    protected static ?string $model = SupplyPayment::class;

    protected static ?string $navigationLabel = 'دفعات توريد';

    protected static ?string $modelLabel = 'دفعة توريد';

    protected static ?string $pluralModelLabel = 'دفعات توريد';
    
    protected static ?string $recordTitleAttribute = 'payment_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('إضافة دفعة توريد')
                ->columnSpan('full')
                ->schema([
                    // العقد
                    Select::make('property_contract_id')
                        ->label('العقد')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return \App\Models\PropertyContract::with(['property', 'owner'])
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
                    
                    // حالة التوريد
                    Select::make('supply_status')
                        ->label('حالة التوريد')
                        ->required()
                        ->options([
                            'pending' => 'قيد الانتظار',
                            'worth_collecting' => 'تستحق التوريد',
                            'collected' => 'تم التوريد',
                        ])
                        ->default('pending')
                        ->live() // جعلها تفاعلية
                        ->afterStateUpdated(function ($state, callable $set) {
                            // تنظيف الحقول عند تغيير الحالة
                            $set('due_date', null);
                            $set('paid_date', null);
                            $set('approval_status', null);
                        })
                        ->columnSpan(['lg' => 1, 'xl' => 1]),
                    
                    // الحقول الديناميكية حسب حالة التوريد
                    
                    // تاريخ الاستحقاق - يظهر مع "قيد الانتظار" و "تستحق التوريد"
                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->visible(fn ($get) => in_array($get('supply_status'), ['pending', 'worth_collecting']))
                        ->required(fn ($get) => in_array($get('supply_status'), ['pending', 'worth_collecting']))
                        ->default(now()->addDays(7))
                        ->columnSpan(['lg' => 1, 'xl' => 1]),
                    
                    // تاريخ التوريد - يظهر مع "تم التوريد"
                    DatePicker::make('paid_date')
                        ->label('تاريخ التوريد')
                        ->visible(fn ($get) => $get('supply_status') === 'collected')
                        ->required(fn ($get) => $get('supply_status') === 'collected')
                        ->default(now())
                        ->columnSpan(['lg' => 1, 'xl' => 1]),
                    
                    // إقرار ما بعد التوريد - يظهر مع "تم التوريد"
                    \Filament\Forms\Components\Placeholder::make('approval_section')
                        ->label('إقرار ما بعد التوريد')
                        ->content('')
                        ->visible(fn ($get) => $get('supply_status') === 'collected')
                        ->columnSpan(['lg' => 3, 'xl' => 4]),
                    
                    \Filament\Forms\Components\Radio::make('approval_status')
                        ->label('أقر')
                        ->options([
                            'approved' => 'موافق',
                            'rejected' => 'غير موافق',
                        ])
                        ->inline()
                        ->visible(fn ($get) => $get('supply_status') === 'collected')
                        ->required(fn ($get) => $get('supply_status') === 'collected')
                        ->columnSpan(['lg' => 3, 'xl' => 4]),
                    
                    // Hidden fields
                    \Filament\Forms\Components\Hidden::make('owner_id'),
                    \Filament\Forms\Components\Hidden::make('payment_number'),
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
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
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

                TextColumn::make('net_amount')
                    ->label('القيمة')
                    ->money('SAR'),

                TextColumn::make('supply_status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'worth_collecting' => 'info',
                        'collected' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'قيد الانتظار',
                        'worth_collecting' => 'تستحق التوريد',
                        'collected' => 'تم التوريد',
                        default => $state,
                    }),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('paid_date')
                    ->label('تاريخ التوريد')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->options(function () {
                        return \App\Models\User::where('type', 'owner')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('property')
                    ->label('العقار')
                    ->options(function () {
                        return \App\Models\Property::with('owner')
                            ->get()
                            ->mapWithKeys(function ($property) {
                                return [$property->id => $property->name . ' - ' . ($property->owner?->name ?? 'بدون مالك')];
                            });
                    })
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            return $query->whereHas('propertyContract.property', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('supply_status')
                    ->label('حالة التوريد')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'worth_collecting' => 'تستحق التوريد',
                        'collected' => 'تم التوريد',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),

            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->searchable([
                'payment_number',
                'month_year',
                'net_amount',
                'supply_status',
                'notes',
                'propertyContract.contract_number',
                'propertyContract.property.name',
                'propertyContract.property.address',
                'owner.name',
                'owner.phone',
                'owner.email',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyPayments::route('/'),
            'create' => Pages\CreateSupplyPayment::route('/create'),
            'view' => Pages\ViewSupplyPayment::route('/{record}'),
            'edit' => Pages\EditSupplyPayment::route('/{record}/edit'),
        ];
    }
    
    // البحث الذكي الشامل
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
            'owner.commercial_register',
            'owner.tax_number',
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
        
        // تطبيع البحث العربي
        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
        $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);
        
        // إزالة المسافات
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        
        return static::getGlobalSearchEloquentQuery()
            ->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                // البحث في رقم الدفعة والمراجع
                $query->where('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('bank_transfer_reference', 'LIKE', "%{$search}%")
                    ->orWhere('month_year', 'LIKE', "%{$search}%");
                
                // البحث في حالة التوريد
                $statusOptions = [
                    'pending' => 'قيد الانتظار',
                    'worth_collecting' => 'تستحق التوريد',  
                    'collected' => 'تم التوريد',
                ];
                
                foreach ($statusOptions as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        $query->orWhere('supply_status', $key);
                    }
                }
                
                // البحث في حالة الموافقة
                $approvalOptions = [
                    'approved' => 'موافق',
                    'rejected' => 'غير موافق',
                ];
                
                foreach ($approvalOptions as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        $query->orWhere('approval_status', $key);
                    }
                }
                
                // البحث في المبالغ المالية
                if (is_numeric($search)) {
                    $query->orWhere('gross_amount', 'LIKE', "%{$search}%")
                        ->orWhere('commission_amount', 'LIKE', "%{$search}%")
                        ->orWhere('commission_rate', 'LIKE', "%{$search}%")
                        ->orWhere('maintenance_deduction', 'LIKE', "%{$search}%")
                        ->orWhere('other_deductions', 'LIKE', "%{$search}%")
                        ->orWhere('net_amount', 'LIKE', "%{$search}%");
                }
                
                // البحث في الملاحظات
                $query->orWhere('notes', 'LIKE', "%{$normalizedSearch}%");
                
                // البحث في التواريخ
                $query->orWhere('due_date', 'LIKE', "%{$search}%")
                    ->orWhere('paid_date', 'LIKE', "%{$search}%")
                    ->orWhere('approved_at', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%");
                
                // البحث بالسنة فقط (مثل: 2024)
                if (preg_match('/^\d{4}$/', $search)) {
                    $query->orWhereYear('due_date', $search)
                        ->orWhereYear('paid_date', $search)
                        ->orWhereYear('approved_at', $search)
                        ->orWhereYear('created_at', $search);
                }
                
                // البحث بالشهر والسنة (مثل: 01/2024 أو 8-2024)
                if (preg_match('/^\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $month = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $year = $parts[1];
                    $query->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
                    })->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $year);
                    })->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('approved_at', $month)->whereYear('approved_at', $year);
                    })->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
                    });
                }
                
                // البحث بالسنة/الشهر (مثل: 2024/01 أو 2024-8)
                if (preg_match('/^\d{4}[-\/]\d{1,2}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $year = $parts[0];
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $query->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
                    })->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $year);
                    })->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('approved_at', $month)->whereYear('approved_at', $year);
                    })->orWhere(function($q) use ($month, $year) {
                        $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
                    });
                }
                
                // البحث بالتاريخ الكامل (مثل: 01/08/2024 أو 1-8-2024)
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
                
                // البحث بصيغة يوم/شهر فقط (مثل: 01/08 أو 1/8)
                if (preg_match('/^\d{1,2}[-\/]\d{1,2}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $currentYear = date('Y');
                    
                    // البحث في السنة الحالية
                    $query->orWhere(function($q) use ($day, $month, $currentYear) {
                        $dateStr = "$currentYear-$month-$day";
                        $q->whereDate('due_date', $dateStr)
                          ->orWhereDate('paid_date', $dateStr)
                          ->orWhereDate('approved_at', $dateStr)
                          ->orWhereDate('created_at', $dateStr);
                    });
                    
                    // البحث بالشهر فقط (في حالة أن المستخدم يقصد الشهر/السنة الحالية)
                    $query->orWhere(function($q) use ($month, $currentYear) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $currentYear);
                    })->orWhere(function($q) use ($month, $currentYear) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $currentYear);
                    });
                }
                
                // البحث في العقد
                $query->orWhereHas('propertyContract', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('contract_number', 'LIKE', "%{$search}%")
                        ->orWhere('contract_number', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('notary_number', 'LIKE', "%{$search}%");
                    
                    // البحث في أرقام العقد
                    if (is_numeric($search)) {
                        $q->orWhere('commission_rate', 'LIKE', "%{$search}%")
                          ->orWhere('duration_months', $search)
                          ->orWhere('payment_day', $search);
                    }
                });
                
                // البحث في العقار
                $query->orWhereHas('propertyContract.property', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('address', 'LIKE', "%{$normalizedSearch}%");
                });
                
                // البحث في المالك
                $query->orWhereHas('owner', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('commercial_register', 'LIKE', "%{$search}%")
                        ->orWhere('tax_number', 'LIKE', "%{$search}%");
                });
                
                // البحث في المعتمد
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
                
                // تحديد لون وعنوان الحالة
                $statusLabel = match($record->supply_status) {
                    'pending' => 'قيد الانتظار',
                    'worth_collecting' => 'تستحق التوريد',
                    'collected' => 'تم التوريد',
                    default => $record->supply_status,
                };
                
                return new GlobalSearchResult(
                    title: $record->payment_number,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'العقار' => $property,
                        'المالك' => $owner,
                        'المبلغ الصافي' => number_format($record->net_amount, 2) . ' SAR',
                        'الحالة' => $statusLabel,
                        'الشهر' => $record->month_year,
                    ]
                );
            });
    }
}
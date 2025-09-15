<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\Property;
use App\Models\Unit;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Schemas\Components\Utilities\Set;
use Closure;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'النفقات';
    
    protected static ?string $modelLabel = 'نفقة';
    
    protected static ?string $pluralModelLabel = 'النفقات';

    protected static string|\UnitEnum|null $navigationGroup = 'الماليات';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['desc', 'type'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->desc;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('بيانات النفقة')
                ->schema([
                    Textarea::make('desc')
                        ->label('التوصيف')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->schema([
                            Select::make('type')
                                ->label('نوع النفقة')
                                ->required()
                                ->options(Expense::TYPES)
                                ->native(false),

                            Select::make('property_id')
                                ->label('العقار')
                                ->options(Property::query()
                                    ->with('owner')
                                    ->get()
                                    ->mapWithKeys(fn ($property) => [
                                        $property->id => $property->name . ' - ' . $property->owner->name
                                    ])
                                    ->toArray()
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $get, $state) {
                                    // Clear unit selection when property changes
                                    $set('unit_id', null);
                                    // إعادة التحقق من التاريخ عند تغيير العقار
                                    if ($get('date')) {
                                        $set('date', $get('date'));
                                    }
                                })
                                ->native(false),

                            Select::make('expense_for')
                                ->label('النفقة خاصة بـ')
                                ->required()
                                ->options([
                                    'property' => 'العقار ككل',
                                    'unit' => 'وحدة معينة',
                                ])
                                ->default('property')
                                ->live()
                                ->afterStateUpdated(function (Set $set, $get, $state) {
                                    if ($state !== 'unit') {
                                        $set('unit_id', null);
                                    } else {
                                        // إذا اختار وحدة معينة ولكن العقار ليس له وحدات، غيّر الاختيار للعقار ككل
                                        $propertyId = $get('property_id');
                                        if ($propertyId) {
                                            $unitsCount = Unit::where('property_id', $propertyId)->count();
                                            if ($unitsCount === 0) {
                                                $set('expense_for', 'property');
                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('تنبيه')
                                                    ->body('هذا العقار ليس له وحدات، تم تغيير النفقة لتكون للعقار ككل')
                                                    ->send();
                                            }
                                        }
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        if ($value === 'unit' && $get('property_id')) {
                                            $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                            if ($unitsCount === 0) {
                                                $fail('هذا العقار ليس له وحدات، يجب اختيار "العقار ككل"');
                                            }
                                        }
                                    },
                                ])
                                ->native(false),

                            Select::make('unit_id')
                                ->label('الوحدة')
                                ->placeholder(fn ($get): string => 
                                    !$get('property_id') ? 'اختر العقار أولاً' : 'اختر الوحدة'
                                )
                                ->options(function ($get): array {
                                    $propertyId = $get('property_id');
                                    if (!$propertyId) {
                                        return [];
                                    }
                                    $units = Unit::where('property_id', $propertyId)
                                        ->get()
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    
                                    if (empty($units)) {
                                        return ['0' => 'لا توجد وحدات متاحة لهذا العقار'];
                                    }
                                    
                                    return $units;
                                })
                                ->disabled(function ($get): bool {
                                    if ($get('expense_for') !== 'unit') {
                                        return true;
                                    }
                                    if (!$get('property_id')) {
                                        return true;
                                    }
                                    $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                    return $unitsCount === 0;
                                })
                                ->helperText(function ($get): ?string {
                                    if ($get('expense_for') !== 'unit') {
                                        return null;
                                    }
                                    if (!$get('property_id')) {
                                        return 'اختر العقار أولاً';
                                    }
                                    $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                    if ($unitsCount === 0) {
                                        return '⚠️ لا توجد وحدات مضافة لهذا العقار';
                                    }
                                    return 'عدد الوحدات المتاحة: ' . $unitsCount;
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->visible(fn ($get): bool => $get('expense_for') === 'unit')
                                ->required(fn ($get): bool => $get('expense_for') === 'unit' && Unit::where('property_id', $get('property_id'))->exists())
                                ->rules([
                                    fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                        if ($get('expense_for') === 'unit' && !$value) {
                                            $propertyId = $get('property_id');
                                            if ($propertyId && Unit::where('property_id', $propertyId)->exists()) {
                                                $fail('يجب اختيار وحدة');
                                            }
                                        }
                                        // التحقق من أن الوحدة المختارة تنتمي للعقار المختار
                                        if ($value && $value !== '0' && $get('property_id')) {
                                            $unit = Unit::find($value);
                                            if ($unit && $unit->property_id != $get('property_id')) {
                                                $fail('الوحدة المختارة لا تنتمي لهذا العقار');
                                            }
                                        }
                                    },
                                ])
                                ->native(false),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('cost')
                                ->label('الإجمالي')
                                ->required()
                                ->numeric()
                                ->prefix('ريال')
                                ->step(0.01),

                            DatePicker::make('date')
                                ->label('التاريخ')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->live(onBlur: true)
                                ->rules([
                                    'required',
                                    'date',
                                    fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                        $propertyId = $get('property_id');
                                        $unitId = $get('unit_id');
                                        if (!$propertyId || !$value) {
                                            return;
                                        }

                                        $validationService = app(\App\Services\ExpenseValidationService::class);
                                        $excludeId = $record ? $record->id : null;
                                        $expenseFor = $get('expense_for') ?? 'property';

                                        // استخدام نفس الطريقة مع المعاملات الصحيحة
                                        $error = $validationService->validateExpense($expenseFor, $propertyId, $unitId, $value, $excludeId);
                                        if ($error) {
                                            $fail($error);
                                        }
                                    },
                                ])
                                ->validationAttribute('التاريخ'),
                        ]),
                ]),

            Section::make('الإثباتات والوثائق')
                ->schema([
                    Builder::make('docs')
                        ->label('الإثباتات')
                        ->blocks([
                            Builder\Block::make('purchase_invoice')
                                ->label('فاتورة مشتريات')
                                ->icon('heroicon-o-receipt-percent')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('اسم الفاتورة')
                                        ->placeholder('مشتريات السباكة, دهانات المدخل, الخ ...'),
                                                 
                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->prefix('ريال'),
                                    
                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->disk('public')
                                        ->directory('uploads/expenses/invoices')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->columnSpanFull()
                                        ->maxSize(5120)
                                        ->downloadable()
                                        ->openable()
                                        ->previewable()
                                        ->imagePreviewHeight('250')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->helperText('يمكنك رفع ملف PDF أو صورة (الحد الأقصى: 5MB)')
                                        ->required(),

                                ])->columns(2),

                            Builder\Block::make('labor_invoice')
                                ->label('فاتورة عمل يد')
                                ->icon('heroicon-o-wrench-screwdriver')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('نوع العمل')
                                        ->Placeholder('تصليح كهرباء, سباكة, تنظيف, الخ ...'),
                                                                        
                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->prefix('ريال'),
                                    
                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->disk('public')
                                        ->directory('uploads/expenses/invoices')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->columnSpanFull()
                                        ->maxSize(5120)
                                        ->downloadable()
                                        ->openable()
                                        ->previewable()
                                        ->imagePreviewHeight('250')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->helperText('يمكنك رفع ملف PDF أو صورة (الحد الأقصى: 5MB)')
                                        ->required(),
                                ])->columns(2),

                            Builder\Block::make('government_document')
                                ->label('وثيقة حكومية')
                                ->icon('heroicon-o-building-office')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('نوع الوثيقة')
                                        ->placeholder('تسوية ضريبية, فاتورة الكهرباء, إلخ...'),
                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->prefix('ريال'),

                                    TextInput::make('entity')
                                        ->label('الجهة الحكومية'),
                                                                        
                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->disk('public')
                                        ->directory('uploads/expenses/invoices')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->columnSpanFull()
                                        ->maxSize(5120)
                                        ->downloadable()
                                        ->openable()
                                        ->previewable()
                                        ->imagePreviewHeight('250')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->helperText('يمكنك رفع ملف PDF أو صورة (الحد الأقصى: 5MB)')
                                        ->required(),
                                ])->columns(2),
                        ])
                        ->addActionLabel('إضافة إثبات')
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('desc')
                    ->label('التوصيف')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->getStateUsing(fn (Expense $record): string => $record->type_name)
                    ->badge()
                    ->color(fn (Expense $record): string => $record->type_color)
                    ->sortable(),

                TextColumn::make('cost')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('docs_count')
                    ->label('الإثباتات')
                    ->getStateUsing(fn (Expense $record): string => $record->docs_count . ' إثبات')
                    ->badge()
                    ->color('info'),

                TextColumn::make('subject_name')
                    ->label('مرتبطة بـ')
                    ->getStateUsing(function (Expense $record): string {
                        return $record->subject_name;
                    })
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('property_and_unit')
                    ->label('العقار')
                    ->form([
                        Grid::make(2)->schema([
                            Select::make('property_id')
                                ->label('العقار')
                                ->options(Property::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('unit_id', null)),
                            
                            Select::make('unit_id')
                                ->label('خاص بـ')
                                ->native(true)
                                ->placeholder('العقار نفسه')
                                ->options(function ($get) {
                                    $propertyId = $get('property_id');
                                    if (!$propertyId) {
                                        return [];
                                    }
                                    $units = Unit::where('property_id', $propertyId)
                                        ->pluck('name', 'id')
                                        ->toArray();

                                    return $units;
                                })
                                ->visible(fn ($get) => (bool)$get('property_id')),
                        ]),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        if (isset($data['property_id']) && $data['property_id']) {
                            if (isset($data['unit_id']) && $data['unit_id']) {
                                // نفقات وحدة محددة
                                $query->where('subject_type', 'App\\Models\\Unit')
                                      ->where('subject_id', $data['unit_id']);
                            } else {
                                // نفقات العقار ككل فقط (ليس الوحدات)
                                $query->where('subject_type', 'App\\Models\\Property')
                                      ->where('subject_id', $data['property_id']);
                            }
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['property_id']) && $data['property_id']) {
                            $property = Property::find($data['property_id']);
                            if ($property) {
                                if (isset($data['unit_id']) && $data['unit_id']) {
                                    $unit = Unit::find($data['unit_id']);
                                    if ($unit) {
                                        $indicators['filter'] = 'العقار: ' . $property->name . ' - خاص بـ: ' . $unit->name;
                                    }
                                } else {
                                    $indicators['filter'] = 'العقار: ' . $property->name . ' (العقار ككل)';
                                }
                            }
                        }
                        return $indicators;
                    }),

                // الصف الثاني: نوع النفقة
                SelectFilter::make('type')
                    ->label('نوع النفقة')
                    ->options(Expense::TYPES)
                    ->multiple(),

                // الصف الثالث: الفترة (الشهر والسنة)
                Filter::make('period')
                    ->label('الفترة')
                    ->form([
                        Grid::make(2)->schema([
                            Toggle::make('this_month')
                                ->label('هذا الشهر')
                                ->inline(false),
                            Toggle::make('this_year')
                                ->label('هذا العام')
                                ->inline(false),
                        ]),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        if (isset($data['this_month']) && $data['this_month']) {
                            $query->thisMonth();
                        }
                        if (isset($data['this_year']) && $data['this_year']) {
                            $query->thisYear();
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['this_month']) && $data['this_month']) {
                            $indicators['this_month'] = 'هذا الشهر';
                        }
                        if (isset($data['this_year']) && $data['this_year']) {
                            $indicators['this_year'] = 'هذا العام';
                        }
                        return $indicators;
                    }),

                // الصف الرابع: نطاق التاريخ (من - إلى)
                Filter::make('date_range')
                    ->label('نطاق التاريخ')
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('from_date')
                                ->label('من تاريخ'),
                            DatePicker::make('to_date')
                                ->label('إلى تاريخ'),
                        ]),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['from_date']) && $data['from_date']) {
                            $indicators['from_date'] = 'من: ' . \Carbon\Carbon::parse($data['from_date'])->format('Y-m-d');
                        }
                        if (isset($data['to_date']) && $data['to_date']) {
                            $indicators['to_date'] = 'إلى: ' . \Carbon\Carbon::parse($data['to_date'])->format('Y-m-d');
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Schemas\Components\Utilities\Get;
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
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // Clear unit selection when property changes
                                    $set('unit_id', null);
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
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
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
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
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
                                ->placeholder(fn (Get $get): string => 
                                    !$get('property_id') ? 'اختر العقار أولاً' : 'اختر الوحدة'
                                )
                                ->options(function (Get $get): array {
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
                                ->disabled(function (Get $get): bool {
                                    if ($get('expense_for') !== 'unit') {
                                        return true;
                                    }
                                    if (!$get('property_id')) {
                                        return true;
                                    }
                                    $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                    return $unitsCount === 0;
                                })
                                ->helperText(function (Get $get): ?string {
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
                                ->visible(fn (Get $get): bool => $get('expense_for') === 'unit')
                                ->required(fn (Get $get): bool => $get('expense_for') === 'unit' && Unit::where('property_id', $get('property_id'))->exists())
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
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
                                ->native(false),
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
                                        ->directory('expenses/invoices')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->columnSpanFull()
                                        ->maxSize(5120)
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
                                        ->directory('expenses/invoices')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->columnSpanFull()
                                        ->maxSize(5120)
                                        ->required(),
                                ])->columns(2),

                            Builder\Block::make('government_document')
                                ->label('وثيقة حكومية')
                                ->icon('heroicon-o-building-office')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('نوع الوثيقة')
                                        ->placeholder('تسوية ضريبية, فاتورة الكهرباء, إلخ...'),
                                    
                                    TextInput::make('entity')
                                        ->label('الجهة الحكومية'),
                                                                        
                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->directory('expenses/invoices')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->columnSpanFull()
                                        ->maxSize(5120)
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
                    ->searchable()
                    ->sortable(),

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
                SelectFilter::make('type')
                    ->label('نوع النفقة')
                    ->options(Expense::TYPES)
                    ->multiple(),

                Filter::make('date_range')
                    ->label('نطاق التاريخ')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('من تاريخ'),
                        DatePicker::make('to_date')
                            ->label('إلى تاريخ'),
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
                    }),

                Filter::make('this_month')
                    ->label('هذا الشهر')
                    ->query(fn (EloquentBuilder $query): EloquentBuilder => $query->thisMonth()),

                Filter::make('this_year')
                    ->label('هذا العام')
                    ->query(fn (EloquentBuilder $query): EloquentBuilder => $query->thisYear()),

                SelectFilter::make('expense_for')
                    ->label('نوع الارتباط')
                    ->options([
                        'property' => 'نفقة عامة للعقار',
                        'unit' => 'نفقة خاصة بوحدة',
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        
                        return match ($data['value']) {
                            'property' => $query->where('subject_type', 'App\\Models\\Property'),
                            'unit' => $query->where('subject_type', 'App\\Models\\Unit'),
                            default => $query,
                        };
                    }),

                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->options(Property::pluck('name', 'id'))
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }
                        
                        return $query->where(function ($query) use ($data) {
                            $query->where('subject_type', 'App\\Models\\Property')
                                  ->where('subject_id', $data['value'])
                                  ->orWhereHas('subject', function ($subQuery) use ($data) {
                                      $subQuery->where('property_id', $data['value']);
                                  });
                        });
                    })
                    ->searchable()
                    ->preload(),
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
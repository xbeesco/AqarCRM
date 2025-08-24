<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitContractResource\Pages;
use App\Models\UnitContract;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;

class UnitContractResource extends Resource
{
    protected static ?string $model = UnitContract::class;

    protected static ?string $navigationLabel = 'تعاقدات المستأجرين';

    protected static ?string $modelLabel = 'تعاقد مستأجر';

    protected static ?string $pluralModelLabel = 'تعاقدات المستأجرين';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('بيانات العقد')
                    ->schema([
                        Select::make('property_id')
                            ->label('العقار')
                            ->required()
                            ->searchable()
                            ->relationship('property', 'name')
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('unit_id', null))
                            ->columnSpan(4),

                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->required()
                            ->native(false)
                            ->placeholder(fn (callable $get): string => 
                                !$get('property_id') ? 'اختر العقار أولاً' : 'اختر الوحدة'
                            )
                            ->options(function (callable $get) {
                                $propertyId = $get('property_id');
                                if (!$propertyId) {
                                    return [];
                                }
                                $units = Unit::where('property_id', $propertyId)
                                    ->pluck('name', 'id');
                                
                                if ($units->isEmpty()) {
                                    return ['0' => 'لا توجد وحدات متاحة لهذا العقار'];
                                }
                                
                                return $units;
                            })
                            ->disabled(function (callable $get): bool {
                                if (!$get('property_id')) {
                                    return true;
                                }
                                $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                return $unitsCount === 0;
                            })
                            ->helperText(function (callable $get): ?string {
                                if (!$get('property_id')) {
                                    return 'اختر العقار أولاً';
                                }
                                $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                if ($unitsCount === 0) {
                                    return '⚠️ لا توجد وحدات مضافة لهذا العقار';
                                }
                                return 'عدد الوحدات المتاحة: ' . $unitsCount;
                            })
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state && $state !== '0') {
                                    $unit = Unit::find($state);
                                    if ($unit) {
                                        $set('monthly_rent', $unit->rent_price ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(4),

                        Select::make('tenant_id')
                            ->label('المستأجر')
                            ->required()
                            ->searchable()
                            ->relationship('tenant', 'name')
                            ->options(User::where('type', 'tenant')->pluck('name', 'id'))
                            ->columnSpan(4),

                        DatePicker::make('start_date')
                            ->label('تاريخ بداية العقد')
                            ->required()
                            ->default(now())
                            ->columnSpan(3),

                        TextInput::make('monthly_rent')
                            ->label('قيمة الإيجار بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('SAR')
                            ->columnSpan(3),

                        TextInput::make('duration_months')
                            ->label('مدة التعاقد بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('شهر')
                            ->columnSpan(3),

                        Select::make('payment_frequency')
                            ->label('التحصيل كل')
                            ->required()
                            ->searchable()
                            ->options([
                                'monthly' => 'شهر',
                                'quarterly' => 'ربع سنة',
                                'semi_annually' => 'نصف سنة',
                                'annually' => 'سنة',
                            ])
                            ->default('monthly')
                            ->columnSpan(3),

                        FileUpload::make('contract_file')
                            ->label('صورة العقد')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('unit-contracts')
                            ->preserveFilenames()
                            ->maxSize(10240)
                            ->columnSpan(6),

                        Textarea::make('notes')
                            ->label('ملاحظات اخري')
                            ->rows(3)
                            ->columnSpan(6),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('تاريخ العقد')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('duration_months')
                    ->label('مدة التعاقد')
                    ->sortable()
                    ->suffix(' شهر')
                    ->alignCenter(),

                TextColumn::make('payment_frequency')
                    ->label('سداد الدفعات')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'monthly' => 'شهري',
                            'quarterly' => 'ربع سنوي',
                            'semi_annually' => 'نصف سنوي',
                            'annually' => 'سنوي',
                            default => $state,
                        };
                    })
                    ->badge(),

                TextColumn::make('monthly_rent')
                    ->label('الإيجار الشهري')
                    ->money('SAR')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable(),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->relationship('tenant', 'name')
                    ->searchable(),

                SelectFilter::make('payment_frequency')
                    ->label('سداد الدفعات')
                    ->options([
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                    ]),
            ])
            //->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                //EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUnitContracts::route('/'),
                        'create' => Pages\CreateUnitContract::route('/create'),
            'view' => Pages\ViewUnitContracts::route('/{record}'),
            'edit' => Pages\EditUnitContract::route('/{record}/edit'),
        ];
    }
}
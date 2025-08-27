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
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Closure;

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
                            ->columnSpan(3),

                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->required()
                            ->native( false )
                            ->placeholder(function (callable $get): ?string {
                                if (! $get('property_id')) {
                                    return 'اختر عقار يحتوي علي وحدات اولا';
                                }

                                $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                if ( is_numeric($unitsCount) && $unitsCount >  0) {
                                    return 'اختر عقار' ;
                                }

                                return 'لا توجد وحدات لهذا العقار'; // bug in Filament
                            })
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
                            ->helperText('' )
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state && $state !== '0') {
                                    $unit = Unit::find($state);
                                    if ($unit) {
                                        $set('monthly_rent', $unit->rent_price ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(3),

                        TextInput::make('monthly_rent')
                            ->label(label: 'قيمة الإيجار بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->postfix('ريال')
                            ->columnSpan(3),

                        Select::make('tenant_id')
                            ->label('المستأجر')
                            ->required()
                            ->searchable()
                            ->relationship('tenant', 'name')
                            ->options(User::where('type', 'tenant')->pluck('name', 'id'))
                            ->columnSpan(3),
                        DatePicker::make('start_date')
                            ->label('تاريخ بداية العمل بالعقد')
                            ->required()
                            ->default(now())
                            ->columnSpan(3),

                        TextInput::make('duration_months')
                            ->label('مدة التعاقد بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('شهر')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                                $set('payments_count', $count);
                            })
                            ->rules([
                                fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $frequency = $get('payment_frequency') ?? 'monthly';
                                    if (!\App\Services\PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                                        $periodName = match($frequency) {
                                            'quarterly' => 'ربع سنة',
                                            'semi_annually' => 'نصف سنة',
                                            'annually' => 'سنة',
                                            default => $frequency,
                                        };
                                        
                                        $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                    }
                                },
                            ])
                            ->validationAttribute('مدة التعاقد')
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
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $duration = $get('duration_months') ?? 0;
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                $set('payments_count', $count);
                            })
                            ->rules([
                                fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $duration = $get('duration_months') ?? 0;
                                    if (!\App\Services\PropertyContractService::isValidDuration($duration, $value ?? 'monthly')) {
                                        $periodName = match($value) {
                                            'quarterly' => 'ربع سنة',
                                            'semi_annually' => 'نصف سنة',
                                            'annually' => 'سنة',
                                            default => $value,
                                        };
                                        $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                    }
                                },
                            ])
                            ->validationAttribute('تكرار التحصيل')
                            ->columnSpan(3),
                        TextInput::make('payments_count')
                            ->label('عدد الدفعات')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($get) {
                                $duration = $get('duration_months') ?? 0;
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $result = \App\Services\PropertyContractService::calculatePaymentsCount($duration, $frequency);
                                return $result;
                            })
                            ->columnSpan(3),


                        FileUpload::make('contract_file')
                            ->label('ملف العقد')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('unit-contracts')
                            ->preserveFilenames()
                            ->maxSize(10240)
                            ->columnSpan(6),

                        Textarea::make('notes')
                            ->label('الملاحظات')
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
                    ->searchable(),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable(),

                TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('تاريخ العقد')
                    ->date('d/m/Y'),

                TextColumn::make('duration_months')
                    ->label('مدة التعاقد')
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
                    ->color(fn (string $state): string => match ($state) {
                        'monthly' => 'primary',
                        'quarterly' => 'success',
                        'semi_annually' => 'info',
                        'annually' => 'danger',
                        default => 'gray',
                    })
                    ->badge(),

                TextColumn::make('monthly_rent')
                    ->label('الإيجار الشهري')
                    ->money('SAR')
                    ->alignEnd(),

// تم إزالة عمود حالة العقد من الجدول لأن التوليد يتم تلقائياً دائماً

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
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

// تم إزالة فلتر حالة العقد لأن التوليد يتم تلقائياً دائماً
            ])
            //->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
// تم إزالة زر التوليد اليدوي لأن التوليد يتم تلقائياً دائماً عند إنشاء العقد
                    
                Action::make('viewPayments')
                    ->label('عرض الدفعات')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.collection-payments.index', [
                        'unit_contract_id' => $record->id
                    ]))
                    ->visible(fn ($record) => $record->payments()->exists()),
                    
                EditAction::make()
                    ->visible(fn () => auth()->user()?->type === 'super_admin'),
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
            'edit' => Pages\EditUnitContract::route('/{record}/edit'), // Only accessible by super_admin
        ];
    }
    
    /**
     * Only super_admin can edit contracts
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        return $user && $user->type === 'super_admin';
    }
    
    /**
     * Only super_admin can delete contracts
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        return $user && $user->type === 'super_admin';
    }
    
    /**
     * Only admins and employees can create contracts
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->type, ['super_admin', 'admin', 'employee']);
    }
    
    /**
     * Filter records based on user type
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if ($user) {
            switch ($user->type) {
                case 'owner':
                    // Owners see contracts for their properties only
                    return $query->whereHas('property', function ($q) use ($user) {
                        $q->where('owner_id', $user->id);
                    });
                    
                case 'tenant':
                    // Tenants see only their own contracts
                    return $query->where('tenant_id', $user->id);
            }
        }
        
        return $query;
    }
}
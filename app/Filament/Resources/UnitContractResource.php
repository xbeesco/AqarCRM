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
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class UnitContractResource extends Resource
{
    protected static ?string $model = UnitContract::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'عقود الإيجار';

    protected static ?string $modelLabel = 'عقد إيجار';

    protected static ?string $pluralModelLabel = 'عقود الإيجار';

    protected static string|\UnitEnum|null $navigationGroup = 'Contracts Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('أطراف العقد / Contract Parties')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('tenant_id')
                                    ->label('المستأجر / Tenant')
                                    ->required()
                                    ->searchable()
                                    ->relationship('tenant', 'name')
                                    ->options(User::role('tenant')->pluck('name', 'id')),

                                Select::make('unit_id')
                                    ->label('الوحدة / Unit')
                                    ->required()
                                    ->searchable()
                                    ->relationship('unit', 'unit_number')
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $unit = Unit::find($state);
                                            if ($unit) {
                                                $set('property_id', $unit->property_id);
                                                $set('monthly_rent', $unit->rent_amount ?? 0);
                                            }
                                        }
                                    }),

                                Select::make('property_id')
                                    ->label('العقار / Property')
                                    ->required()
                                    ->searchable()
                                    ->relationship('property', 'name')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('الشروط المالية / Financial Terms')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('monthly_rent')
                                    ->label('الإيجار الشهري / Monthly Rent')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->prefix('SAR'),

                                TextInput::make('security_deposit')
                                    ->label('التأمين / Security Deposit')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('SAR')
                                    ->helperText('عادة ما يكون شهر واحد'),

                                Select::make('payment_frequency')
                                    ->label('دورية الدفع / Payment Frequency')
                                    ->required()
                                    ->default('monthly')
                                    ->options([
                                        'monthly' => 'شهري / Monthly',
                                        'quarterly' => 'ربع سنوي / Quarterly',
                                        'semi_annually' => 'نصف سنوي / Semi-Annual',
                                        'annually' => 'سنوي / Annual',
                                    ]),

                                Select::make('payment_method')
                                    ->label('طريقة الدفع / Payment Method')
                                    ->required()
                                    ->default('bank_transfer')
                                    ->options([
                                        'bank_transfer' => 'تحويل بنكي / Bank Transfer',
                                        'cash' => 'نقد / Cash',
                                        'check' => 'شيك / Check',
                                        'online' => 'دفع إلكتروني / Online Payment',
                                    ]),
                            ]),
                    ]),

                Section::make('فترة العقد / Contract Period')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('تاريخ بدء العقد / Contract Start Date')
                                    ->required()
                                    ->default(now())
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        $duration = $get('duration_months');
                                        if ($state && $duration) {
                                            $endDate = \Carbon\Carbon::parse($state)->addMonths($duration)->subDay();
                                            $set('end_date', $endDate);
                                        }
                                    }),

                                TextInput::make('duration_months')
                                    ->label('مدة العقد بالشهور / Contract Duration (Months)')
                                    ->numeric()
                                    ->required()
                                    ->default(12)
                                    ->minValue(1)
                                    ->maxValue(120)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        $startDate = $get('start_date');
                                        if ($startDate && $state) {
                                            $endDate = \Carbon\Carbon::parse($startDate)->addMonths($state)->subDay();
                                            $set('end_date', $endDate);
                                        }
                                    }),

                                DatePicker::make('end_date')
                                    ->label('تاريخ انتهاء العقد / Contract End Date')
                                    ->required()
                                    ->disabled()
                                    ->helperText('يتم حسابه تلقائياً / Auto-calculated'),
                            ]),
                    ]),

                Section::make('الشروط الإضافية / Additional Terms')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('grace_period_days')
                                    ->label('فترة السماح (أيام) / Grace Period (Days)')
                                    ->numeric()
                                    ->required()
                                    ->default(5)
                                    ->minValue(0)
                                    ->maxValue(30),

                                TextInput::make('late_fee_rate')
                                    ->label('نسبة غرامة التأخير % / Late Fee Rate %')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%'),

                                TextInput::make('evacuation_notice_days')
                                    ->label('فترة إشعار الإخلاء / Evacuation Notice (Days)')
                                    ->numeric()
                                    ->required()
                                    ->default(30)
                                    ->minValue(1)
                                    ->maxValue(365),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('utilities_included')
                                    ->label('المرافق مشمولة / Utilities Included')
                                    ->default(false),

                                Toggle::make('furnished')
                                    ->label('مفروش / Furnished')
                                    ->default(false),
                            ]),
                    ]),

                Section::make('الشروط والأحكام / Terms and Conditions')
                    ->collapsible()
                    ->schema([
                        Textarea::make('terms_and_conditions')
                            ->label('الشروط والأحكام / Terms and Conditions')
                            ->rows(5),

                        Textarea::make('special_conditions')
                            ->label('الشروط الخاصة / Special Conditions')
                            ->rows(3),

                        Textarea::make('notes')
                            ->label('ملاحظات / Notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract_number')
                    ->label('رقم العقد / Contract Number')
                    ->searchable()
                    ->sortable()
                    ->width('150px'),

                TextColumn::make('tenant.name')
                    ->label('المستأجر / Tenant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('العقار / Property')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.unit_number')
                    ->label('رقم الوحدة / Unit Number')
                    ->sortable(),

                TextColumn::make('monthly_rent')
                    ->label('الإيجار الشهري / Monthly Rent')
                    ->money('SAR')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('start_date')
                    ->label('تاريخ البدء / Start Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء / End Date')
                    ->date('d/m/Y')
                    ->sortable(),

                BadgeColumn::make('contract_status')
                    ->label('الحالة / Status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'expired',
                        'danger' => 'terminated',
                        'info' => 'renewed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'draft' => 'مسودة / Draft',
                            'active' => 'نشط / Active',
                            'expired' => 'منتهي / Expired',
                            'terminated' => 'مفسوخ / Terminated',
                            'renewed' => 'مجدد / Renewed',
                            default => $state,
                        };
                    }),

                BadgeColumn::make('payment_frequency')
                    ->label('دورية الدفع / Payment Frequency')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'monthly' => 'شهري / Monthly',
                            'quarterly' => 'ربع سنوي / Quarterly',
                            'semi_annually' => 'نصف سنوي / Semi-Annual',
                            'annually' => 'سنوي / Annual',
                            default => $state,
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('contract_status')
                    ->label('الحالة / Status')
                    ->options([
                        'draft' => 'مسودة / Draft',
                        'active' => 'نشط / Active',
                        'expired' => 'منتهي / Expired',
                        'terminated' => 'مفسوخ / Terminated',
                        'renewed' => 'مجدد / Renewed',
                    ]),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر / Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable(),

                SelectFilter::make('property_id')
                    ->label('العقار / Property')
                    ->relationship('property', 'name')
                    ->searchable(),

                SelectFilter::make('payment_frequency')
                    ->label('دورية الدفع / Payment Frequency')
                    ->options([
                        'monthly' => 'شهري / Monthly',
                        'quarterly' => 'ربع سنوي / Quarterly',
                        'semi_annually' => 'نصف سنوي / Semi-Annual',
                        'annually' => 'سنوي / Annual',
                    ]),

                Filter::make('rent_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('rent_from')
                                    ->label('من / From')
                                    ->numeric()
                                    ->prefix('SAR'),
                                TextInput::make('rent_to')
                                    ->label('إلى / To')
                                    ->numeric()
                                    ->prefix('SAR'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['rent_from'],
                                fn (Builder $query, $rent): Builder => $query->where('monthly_rent', '>=', $rent),
                            )
                            ->when(
                                $data['rent_to'],
                                fn (Builder $query, $rent): Builder => $query->where('monthly_rent', '<=', $rent),
                            );
                    })
                    ->label('نطاق الإيجار / Rent Range'),

                Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_from')
                                    ->label('من تاريخ / From Date'),
                                DatePicker::make('start_until')
                                    ->label('إلى تاريخ / To Date'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    })
                    ->label('فترة العقد / Contract Period'),

                Filter::make('expiring_soon')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->expiring(30))
                    ->label('ينتهي قريباً / Expiring Soon'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (UnitContract $record) => in_array($record->contract_status, ['draft', 'active'])),
                // Add custom actions like payments, renew, terminate here
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
            'view' => Pages\ViewUnitContract::route('/{record}'),
            'edit' => Pages\EditUnitContract::route('/{record}/edit'),
        ];
    }
}
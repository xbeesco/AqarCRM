<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyContractResource\Pages;
use App\Models\PropertyContract;
use App\Models\User;
use App\Models\Property;
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

class PropertyContractResource extends Resource
{
    protected static ?string $model = PropertyContract::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'عقود الملاك';

    protected static ?string $modelLabel = 'عقد ملكية';

    protected static ?string $pluralModelLabel = 'عقود الملاك';

    protected static string|\UnitEnum|null $navigationGroup = 'Contracts Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('تفاصيل العقد / Contract Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('owner_id')
                                    ->label('المالك / Owner')
                                    ->required()
                                    ->searchable()
                                    ->relationship('owner', 'name')
                                    ->options(User::role('owner')->pluck('name', 'id'))
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('property_id', null)),

                                Select::make('property_id')
                                    ->label('العقار / Property')
                                    ->required()
                                    ->searchable()
                                    ->relationship('property', 'name')
                                    ->options(function (callable $get) {
                                        $ownerId = $get('owner_id');
                                        if ($ownerId) {
                                            return Property::where('owner_id', $ownerId)->pluck('name', 'id');
                                        }
                                        return Property::pluck('name', 'id');
                                    })
                                    ->reactive(),

                                TextInput::make('commission_rate')
                                    ->label('نسبة العمولة % / Commission Rate %')
                                    ->numeric()
                                    ->required()
                                    ->default(5)
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->suffix('%'),
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

                Section::make('شروط العقد / Contract Terms')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('payment_day')
                                    ->label('يوم دفع العمولة / Commission Payment Day')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(28)
                                    ->helperText('اليوم من كل شهر لدفع العمولة'),

                                TextInput::make('notice_period_days')
                                    ->label('فترة الإشعار (أيام) / Notice Period (Days)')
                                    ->numeric()
                                    ->required()
                                    ->default(30)
                                    ->minValue(1)
                                    ->maxValue(365),

                                TextInput::make('notary_number')
                                    ->label('رقم الكاتب العدل / Notary Number')
                                    ->maxLength(100),
                            ]),

                        Toggle::make('auto_renew')
                            ->label('التجديد التلقائي / Auto Renewal')
                            ->default(false),
                    ]),

                Section::make('الشروط الإضافية / Additional Terms')
                    ->collapsible()
                    ->schema([
                        Textarea::make('terms_and_conditions')
                            ->label('الشروط والأحكام / Terms and Conditions')
                            ->rows(5),

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

                TextColumn::make('owner.name')
                    ->label('المالك / Owner')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('العقار / Property')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('commission_rate')
                    ->label('العمولة % / Commission %')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->alignCenter(),

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
                        'warning' => 'suspended',
                        'danger' => fn ($state) => in_array($state, ['expired', 'terminated']),
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'draft' => 'مسودة / Draft',
                            'active' => 'نشط / Active',
                            'suspended' => 'معلق / Suspended',
                            'expired' => 'منتهي / Expired',
                            'terminated' => 'مفسوخ / Terminated',
                            default => $state,
                        };
                    }),

                TextColumn::make('duration_months')
                    ->label('المدة (شهر) / Duration (Months)')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('contract_status')
                    ->label('الحالة / Status')
                    ->options([
                        'draft' => 'مسودة / Draft',
                        'active' => 'نشط / Active',
                        'suspended' => 'معلق / Suspended',
                        'expired' => 'منتهي / Expired',
                        'terminated' => 'مفسوخ / Terminated',
                    ]),

                SelectFilter::make('owner_id')
                    ->label('المالك / Owner')
                    ->relationship('owner', 'name')
                    ->searchable(),

                Filter::make('commission_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('commission_from')
                                    ->label('من / From')
                                    ->numeric()
                                    ->suffix('%'),
                                TextInput::make('commission_to')
                                    ->label('إلى / To')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['commission_from'],
                                fn (Builder $query, $commission): Builder => $query->where('commission_rate', '>=', $commission),
                            )
                            ->when(
                                $data['commission_to'],
                                fn (Builder $query, $commission): Builder => $query->where('commission_rate', '<=', $commission),
                            );
                    })
                    ->label('نطاق العمولة % / Commission Range %'),

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
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (PropertyContract $record) => in_array($record->contract_status, ['draft', 'active'])),
                // Add custom actions like renew, terminate here
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
            'index' => Pages\ListPropertyContracts::route('/'),
            'create' => Pages\CreatePropertyContract::route('/create'),
            'view' => Pages\ViewPropertyContract::route('/{record}'),
            'edit' => Pages\EditPropertyContract::route('/{record}/edit'),
        ];
    }
}